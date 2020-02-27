<?php
namespace Jy\Common\MsgQueue\MsgQueue;

use Jy\Common\MsgQueue\Contract\AmqpBaseInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Wire\AMQPTable;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitmqBase implements AmqpBaseInterface {
    private $_consumerStopWait = 0;
    public $_retryTime  = array(1,5,10);
    private $_debug = 0;
    private $_conn = null;
    private $_channel = null;
    private $_confKey = array('host','port','user','pwd','vhost');
    protected $_conf = null;
//    private $_conf =['host' => '127.0.0.1', 'port' => 5672, 'user' => 'root', 'pwd' => 'root', 'vhost' => '/',];
//    private $_conf =['host' => '172.19.113.249', 'port' => 5672, 'user' => 'root', 'pwd' => 'sfat324#43523dak&', 'vhost' => '/',];


    private $_codeErrMessage = array(
        400=>'code is null',
        401=>'code not is key',
        500=>"msgBody is null",
        501=>"msgBody is bool",
        502=>"<message_id> key value: must null",
        503=>"<type> key value: must null",

        504=>"mode value is error.",
        505=>"confirm mode or  tx mode just have use one,is mutex -1",
        506=>"N-Ack {0}",
        507=>"return_listener {0}",
        508=>"consumerName is null",
        509=>"conn failed",
        510=>" queue name is null",
        511=>"exchange name is null",
        512=>"<timestamp> key value: must null",
        513=>"user diy bean not match rabbitmq server back header.",
        514=>"beanName is not object",
        515=>"config get :rabbitmq key  is null",
        516=>"config key err.",
        517=>" delayTime must int.",
        518=>" delayTime must > 1000",
        519=>" delayTime must <= 7 days.",
        520=>"rabbitmq return ack not include header",

        600=>"NOT_FOUND - no exchange",
        601=>"PRECONDITION_FAILED - cannot switch from confirm to tx mode",
        602=>"AMQP-rabbit doesn't define data of type []",
        603=>"NOT_FOUND - no queue",
        604=>"PRECONDITION_FAILED - inequivalent arg 'x-dead-letter-exchange' for queue",
        605=>":NO_ROUTE",
    );


    function __construct($conf){
        $this->_conf = $conf;
    }

    function initBase(){
        $this->initConn();
        $this->initChannel();
    }

    function setConf($conf){
        $this->_conf = $conf;
    }

    function setDebug($flag){
        $this->_debug =  $flag;
    }

    function initConn(){
        $this->_conn = $this->getConn();
        return $this->_conn;
    }

    function getConn(){
        if($this->_conn){
            return $this->_conn;
        }

        $conf = $this->_conf;
        $conn = new AMQPStreamConnection( //建立生产者与mq之间的连接
            $conf['host'], $conf['port'], $conf['user'], $conf['pwd'], $conf['vhost']
        );

//        $insist = false,
//        $login_method = 'AMQPLAIN',
//        $login_response = null,
//        $locale = 'en_US',
//        $connection_timeout = 3.0,
//        $read_write_timeout = 3.0,
//        $context = null,
//        $keepalive = false,
//        $heartbeat = 0

        $this->out("connect rabbit config;".json_encode($conf));
        if(!$conn->isConnected()){
            $this->throwException(509);
        }
        $this->_conn = $conn;
        return $this->_conn;
    }

    function checkConfigFormat($config = null ){
        if(!$config){
            $config = $this->_conf;
        }

        foreach ( $this->_confKey as $k=>$v) {
            $f = 0;
            foreach ($config as $k2=>$v2) {
                if($v == $k2 && $v2){
                    $f = 1;
                    break;
                }
            }
            if(!$f){
                $this->throwException(516);
            }
        }
    }

    function resetConn(){
        $this->out("reset connect");
        $this->_conn = null;
        $this->_channel = null;
        $this->initChannel();
    }

    function initChannel(){
        if(!$this->_conn){
            $this->initConn();
        }
        $this->_channel = $this->_conn->channel(); //在已连接基础上建立生产者与mq之间的通道
    }

    function getChannel() : AMQPChannel{
        if(!$this->_channel){
            $this->initChannel();
        }
        return $this->_channel;
    }

    function throwException($code,$replace = ""){
        if(!$code){
            throw new \Exception($this->_codeErrMessage[400]);
        }

        if(!isset($this->_codeErrMessage[$code]) || !$this->_codeErrMessage[$code]){
            throw new \Exception($this->_codeErrMessage[401]);
        }
        if(!$replace){
            throw new \Exception($this->_codeErrMessage[$code]);
        }else{
            $message = $this->_codeErrMessage[$code];
            foreach ($replace as $key => $v) {
                $message = str_replace("{" . $key ."}",$v,$message);
            }

            throw new \Exception($message);
        }
    }

    function getRetryMax(){
        return count($this->_retryTime);
    }

    function getRetryTime(){
        return $this->_retryTime;
    }

    function confirmSelectMode(){
        $this->out("start confirm_select mode:");
        $this->getChannel()->confirm_select();
    }
    //开启一个事务
    function txSelect(){
        $this->out("txSelect");
        $this->getChannel()->tx_select();
    }

    function txCommit(){
        $this->out("txCommit");
        $this->getChannel()->tx_commit();
    }

    function txRollback(){
        $this->out("rollback");
        $this->getChannel()->tx_rollback();
    }

    function createUniqueMsgId(){
        return uniqid(time());
    }

    function getReturnRabbitmqAckTypeDesc(){
        return array("recover"=>'异常1','nack'=>'异常2',"reject"=>'异常3');
    }

    //队列相关===============================
    function setQueue($queueName,$arguments = null,$durable = true,$autoDelete = false){
        if(!$queueName){
            $this->throwException(510);
        }

        $this->out("setQueue $queueName , arguments:".json_encode($arguments) . " durable : $durable , autoDelete : $autoDelete");
//        try{
//            $this->getChannel()->queue_declare($queueName,true,false,false,false,false);
//            $this->out(" ok exist");
//        }catch (Exception $e){
//            $this->out($e->getMessage());
//            $this->out("not exist :".$e->getMessage());
//            $this->resetConn();
        $table = null;
        if($arguments){
            $table = new AMQPTable($arguments);
        }
        $this->getChannel()->queue_declare($queueName,false,$durable,false,$autoDelete,true,$table);
        $this->baseWait();
//        }
    }

    function bindQueue($queueName,$exchangeName,$routingKey = '',$header = null){
        if($header){
            $header = new AMQPTable($header);
        }
        $this->out("bindQueue $queueName $exchangeName");
        try{
            $this->getChannel()->queue_bind($queueName,$exchangeName,$routingKey,true,$header);
        }catch (Exception $e){
            $this->out($e->getMessage());
            exit;
        }
    }


    function queueExist($queueName,$arguments= null,$durable= null,$autoDel= null){
        if($arguments){
            $arguments = new AMQPTable($arguments);
        }
        try{
            $this->getChannel()->queue_declare($queueName,true,$durable,false,$autoDel,false,$arguments);
            return 1;
        }catch (\Exception $e){
            $this->resetConn();
            return 0;
        }
    }

    function deleteQueue($queueName){
        $this->getChannel()->queue_delete($queueName);
    }
    //队列相关 end=============================================

    //exchange  相关start ==============================================
    function setExchange($exchangeName,$type,$arguments = null){
        if(!$exchangeName){
            $this->throwException(511);
        }
        $this->out("setExchange $exchangeName , type:$type , arguments:".json_encode($arguments));
//        try{
//            $this->getChannel()->exchange_declare($exchangeName,$type,true,false,true,false,false);
//            $this->out(" ok exist");
//        }catch (Exception $e){
//            $this->out("not exist :".$e->getMessage());
//            $this->resetConn();

        $table = null;
        if($arguments) {
            $table = new AMQPTable($arguments);
        }

        $this->getChannel()->exchange_declare($exchangeName,$type,false,true,false,false,false,$table);
//            $this->out("create exchange .");
//        }
    }

    function unbindExchangeQueue($exchangeName,$queueName,$routingKey = "",$arguments = null){
        if($arguments){
            $arguments = new AMQPTable($arguments);
        }
        $this->getChannel()->queue_unbind($queueName,$exchangeName,$routingKey,$arguments);
    }

    function deleteExchange($exchangeName){
        $this->getChannel()->exchange_delete($exchangeName);
    }
    //exchange 相关end========================================================


    function publish($msgBody ,$exchangeName,$routingKey = '',$header = null,$arguments = null){
        $info = "publish  ex:$exchangeName , route key:".$routingKey ;
        if($header){
            $info .= " . header:".json_encode($header);
        }
        if($arguments){
            $info .= " . arguments:".json_encode($arguments);
        }
        $this->out($info);
        $finalArguments = [];
        if($header){
            $header =  new AMQPTable($header);
            $finalArguments['application_headers'] = $header;
        }

        if($arguments){
            $finalArguments = array_merge($finalArguments,$arguments);
        }
        $AMQPMessage = new AMQPMessage($msgBody,$finalArguments);
        $this->getChannel()->basic_publish($AMQPMessage,$exchangeName,$routingKey,true);
        $this->baseWait();
    }

    function baseWait(){
        $this->getChannel()->wait_for_pending_acks_returns();
    }

    function retry($attr,$body,$exchange,$msg){
        $retryCount = 0;
        if (isset($attr['header']['x-retry-count'])) {
            $retryCount = $attr['header']['x-retry-count'];
        }

        $this->out("delivery_tag:".$msg->delivery_info['delivery_tag']);
        $this->out("attr:".json_encode($attr));

        if ($retryCount >= $this->getRetryMax()) {
            $this->out("$retryCount > getRetryMax  . $retryCount>= ".$this->getRetryMax());
            $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], false);
            $this->out("reject msg ".$msg->delivery_info['delivery_tag']);
            return true;
        }

        $this->out("retry count:$retryCount");

//        try{
//            $this->txSelect();

        $baseRetryCnt = $this->getRetryTime();
        $this->out("baseRetryCnt:".json_encode($baseRetryCnt));
        $arguments = $attr;
        $header = $attr['header'];
        unset($arguments['header']);
        $header['x-delay'] = $baseRetryCnt[$retryCount] * 1000;
        $header['x-retry-count'] = $retryCount+1;
        $this->out("header:".json_encode($header));

        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        $this->out("ack:".$msg->delivery_info['delivery_tag']);
        $this->publish($body,$exchange,"",$header,$arguments);
//            $this->txCommit();
//        }catch (\Exception $e){
//            $this->txRollback();
//            $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'],true);
//        }
    }

    function subscribeCallback($msg,$userCallback,$exchange,$noAck){
        $this->out("im in base subscribeCallback");
        $body = self::getBody($msg);
        $attr = self::getReceiveAttr($msg);
        $recall = array("AMQPMessage" => $msg, 'body' => $body, 'attr' => $attr);
        if($noAck){
            $this->out(" no ack ");
            call_user_func($userCallback,$recall);
        }else{
            try{
                $this->out(" exec user callback function");
                $rs = call_user_func($userCallback,$recall);
                if(!$rs || !isset($rs['return']) || !$rs['return'] || ! in_array($rs['return'],array_flip($this->getReturnRabbitmqAckTypeDesc()) ) ){
                    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag'] );
                    return true;
                }
//                $rs=['return'=>'reject'];

                $this->out("user trigger retry:".$rs['return']);
                if($rs['return'] == 'reject' && isset($rs['requeue']) && $rs['requeue'] ){
                    $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], false);
                    return true;
                }
                $this->retry($attr,$body,$exchange,$msg);
//                $callbackRabbitmqServerAckFunc = $rs['return'];
//                $this->$callbackRabbitmqServerAckFunc($recall['AMQPMessage']);
            }catch (\Exception $e) {
                $info = $e->getMessage();
                $this->out("subscribeCallback exception retry:".$info);
                $this->retry($attr,$body,$exchange,$msg);
            }
        }
    }


//    function redis(){
//        if (!isset($attr['message_id']) || !$attr['message_id']) {
//            $this->_consumer->throwException("message_id key must exist if open ackAck");
//        }
//
//        $messageId = $recall['attr']['message_id'];
//        $sendMsgStatus = RedisMsgStatusAck::redisGetMsgStatusAck($messageId);
//        if($sendMsgStatus['status'] == RedisMsgStatusAck::$_statusProductWait || $sendMsgStatus['status'] ==RedisMsgStatusAck::$_statusConsumerFinish ){
//            $this->_consumer->reject($recall['AMQPMessage'],false);
//            return true;
//        }
//
//        if($sendMsgStatus['status'] == RedisMsgStatusAck::$_statusConsumerProcessing){
//            if(time() - $sendMsgStatus['time'] < $this->_msgProcessionTimeout){
//                $this->_consumer->reject($recall['AMQPMessage'],true);
//                return true;
//            }
//        }
//
//        RedisMsgStatusAck::redisSetMsgStatusAck($messageId,RedisMsgStatusAck::$_statusConsumerProcessing);
//        try{
//            $this->callUserRegAckCallback($recall);
//        }catch (Exception $e){
//            RedisMsgStatusAck::redisSetMsgStatusAck($messageId,RedisMsgStatusAck::$_statusConsumerException,$e->getMessage());
//            $this->_consumer->reject($recall['AMQPMessage'],false);
//            return true;
//        }
//
//        RedisMsgStatusAck::redisSetMsgStatusAck($messageId,"consumerFinish");
//        $this->_consumer->ack($recall['AMQPMessage']);
//    }

    //consumer 订阅 一个队列
    function baseSubscribe($exchangeName,$queueName,$consumerTag = "" ,$userCallback,$noAck = false){
        $this->out("set new Consume : queue:$queueName , consumerTag:$consumerTag ,noAck: $noAck , consumerTag : $consumerTag. ");
        if(!$consumerTag){
            $consumerTag = $queueName . time();
        }

        $self = $this;
        $baseCallback = function($msg) use($userCallback,$self,$exchangeName,$noAck){
            $rs = $self->subscribeCallback($msg,$userCallback,$exchangeName,$noAck);
            return true;
        };

        $this->getChannel()->basic_consume($queueName,$consumerTag,false,$noAck,false,false,$baseCallback);
    }

    function startListenerWait(){
        $this->out(" start Listener Wait... ");
        while (1){
            if($this->_consumerStopWait){
                $this->out(" cancel consumer.");
                break;
            }
            $this->getChannel()->wait();
        }
    }

    function setStopListenerWait($flag){
        $this->_consumerStopWait = $flag;
    }

    function listenerCancel($consumerTag){
        $this->getChannel()->basic_cancel($consumerTag);
    }


    function out($msg ,$br = 1){
        if(!$this->_debug){
            return -1;
        }
        if($br){
            if (preg_match("/cli/i", php_sapi_name())){
                echo $msg . "\n";
            }else{
                echo $msg . "<br/>";
            }
        }else{
            echo $msg;
        }
    }

    static function getReceiveAttr( $AMQPMessage){
        $attr = $AMQPMessage->get_properties();
        foreach ($attr as $k=>$v) {
            if($k == 'application_headers'){
                $attr['header'] = $v->getNativeData();
                unset($attr[$k]);
            }
        }

        return $attr;
    }

    static function getBody($AMQPMessage){
        $attr = self::getReceiveAttr($AMQPMessage);
        $body = $AMQPMessage->getBody();
//    var_dump($body);
        if(isset($attr['content_type']) &&  $attr['content_type']){
//        out("content_type:".$attr['content_type']);
            switch ($attr['content_type']){
                case "application/json":
                    $body = json_decode($body,true);
                    break;
                case "application/serialize":
                    $body = unserialize($body);
                    break;
                default:
                    break;
            }
        }

        return $body;
    }

    static function getReceiveHeader(AMQPMessage $AMQPMessage){
        $header = $AMQPMessage->get("application_headers");
        $data = $header->getNativeData();
//        $data['x-death'][0]['reason']
        return $data;
    }

    static function debugMergeInfo($attr){
        $info = "";
        foreach ($attr as $k=>$v) {
            if($k == 'header'){
                $list = null;
                foreach ($v as $k2=>$v2) {
                    $list = $k2 . " " . $v2;
                }
                $info .= " application_headers :" .$list . " ";
            }else{
                $info .= $k . ":" .$v . " ";
            }
        }
        return $info;
    }




}