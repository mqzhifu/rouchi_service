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
    private $_debug = 1;
    private $_conn = null;
    private $_channel = null;
    private $_conf =['host' => '127.0.0.1', 'port' => 5672, 'user' => 'root', 'pwd' => 'root', 'vhost' => '/',];
//    private $_conf =['host' => '172.19.113.249', 'port' => 5672, 'user' => 'root', 'pwd' => 'sfat324#43523dak&', 'vhost' => '/',];

    function __construct(){
        $this->initConn();
        $this->initChannel();
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

        $this->out("connect rabbit config;".json_encode($conf));
        if(!$conn->isConnected()){
            $this->throwException("conn failed");
        }
        $this->_conn = $conn;
        return $this->_conn;
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

    function throwException($msg){
        throw new \Exception($msg);
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
            $this->throwException(" queue name is null");
        }

        $this->out("setQueue $queueName , arguments:".json_encode($arguments));
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

    function queueExist($queue){
        try{
            $this->getChannel()->queue_declare($queue,true);
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
            $this->throwException(" exchange name is null");
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
        $this->out("publish  ex:$exchangeName , route key:".$routingKey);
        $finalArguments = [];
        if($header){
            $header =  new AMQPTable($header);
            $finalArguments['application_headers'] = $header;
        }

        if($arguments){
            $finalArguments = array_merge($finalArguments,$arguments);
        }
        $AMQPMessage = new AMQPMessage($msgBody,$finalArguments);
        $this->getChannel()->basic_publish($AMQPMessage,$exchangeName,$routingKey,false);
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
            call_user_func($userCallback,$recall);
        }else{
            try{
                $rs = call_user_func($userCallback,$recall);
                if(!$rs || !isset($rs['return']) || !$rs['return'] || ! in_array($rs['return'],array_flip($this->getReturnRabbitmqAckTypeDesc()) ) ){
                    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag'] );
                    return true;
                }
                $rs=['return'=>'reject'];

                $this->out("user trigger retry:".$rs['return']);
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