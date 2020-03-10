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
    private $_userCallbackFuncExecTimeout = 60;
    protected $_conf = null;
    private $_startLoopDead = false;
    private $_loopDeadCustomerTag = "";
    private $_userShutdownCallback = "";
//    private $_conf =['host' => '127.0.0.1', 'port' => 5672, 'user' => 'root', 'pwd' => 'root', 'vhost' => '/',];
//    private $_conf =['host' => '172.19.113.249', 'port' => 5672, 'user' => 'root', 'pwd' => 'sfat324#43523dak&', 'vhost' => '/',];
    private $_pidPath = "/tmp/rabbitmq.pid";
    private $_nowStop = 0;
    private $_timeOutCallback = null;


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
        521=>'set bean must obj',
        522=>'please setSubscribeBean',
        523=>'setSubscribeBean: please set {0} method',
        524=>" retry time <= 1 days.",
        525=>" retry time must is int.",
        526=>" timeout is null.",
        527=>" timeout min 10",
        528=>" timeout max 600",
        529=>"exec user program timeout ",

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
        $this->initConn();//创建连接
        $this->initChannel();//获取管道
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

    function setRetryTime(array $time){
        foreach ($time as $k=>$v) {
            $int = (int) $v;
            if(!$int || $int < 0){
                $this->throwException(525);
            }
            if($v > 24 * 60 * 60){
                $this->throwException(524);
            }
        }

        $this->_retryTime = $time;
    }

    function setUserCallbackFuncExecTimeout(int $second){
        if(!$second){
            $this->throwException(526);
        }

        if($second < 10){
            $this->throwException(527);
        }

        if($second > 600){
            $this->throwException(528);
        }

        $this->_userCallbackFuncExecTimeout = $second ;
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
        return array(
//            "recover"=>'异常1',
//            'nack'=>'异常2',
            "reject"=>'异常3'
        );
    }

    //创建一个队列
    function setQueue($queueName,$arguments = null,$durable = true,$autoDelete = false){
        if(!$queueName){
            $this->throwException(510);
        }

        $this->out("setQueue $queueName , arguments:".json_encode($arguments) . " durable : $durable , autoDelete : $autoDelete");
        $table = null;
        if($arguments){
            $table = new AMQPTable($arguments);
        }
        $this->getChannel()->queue_declare($queueName,false,$durable,false,$autoDelete,true,$table);
        $this->baseWait();
    }
    //绑定一个队列
    function bindQueue($queueName,$exchangeName,$routingKey = '',$header = null){
        $outInfo = " header : ";
        if($header)
            $outInfo .= json_encode($header);
        else
            $outInfo .=" null";

        if($header){
            $header = new AMQPTable($header);
        }

        $this->out("bindQueue  queueName:$queueName exchangeName:$exchangeName $outInfo");
        try{
            $this->getChannel()->queue_bind($queueName,$exchangeName,$routingKey,true,$header);
        }catch (Exception $e){
            $this->out($e->getMessage());
            exit;
        }
    }
    //判断队列是否已经存在
    function queueExist($queueName,$arguments= null,$durable= null,$autoDel= null){
        $this->out("test queue exist :",0);
        if($arguments){
            $arguments = new AMQPTable($arguments);
        }
        try{
            $this->getChannel()->queue_declare($queueName,true,$durable,false,$autoDel,false,$arguments);
            $this->out("true");
            return 1;
        }catch (\Exception $e){
            $this->resetConn();
            $this->out("false");
            return 0;
        }
    }

    function deleteQueue($queueName){
        $this->getChannel()->queue_delete($queueName);
    }

    //创建一个exchange
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


    //发布一条消息
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
    //等待rabbitmq 返回内容
    function baseWait(){
        $this->getChannel()->wait_for_pending_acks_returns();
    }
    //重试机制
    function retry($attr,$body,$exchange,$msg){
        if(!$this->getRetryTime()){
            $this->out(" no set retry");
            return true;
        }

        //重复-已发送次数
        $retryCount = 0;
        if (isset($attr['header']['x-retry-count'])) {
            $retryCount = $attr['header']['x-retry-count'];
        }

        $this->out("delivery_tag:".$msg->delivery_info['delivery_tag']);
        $this->out("attr:".json_encode($attr));
        //判断 是否 超过 最大投递次数
        if ($retryCount >= $this->getRetryMax()) {
            $this->out("$retryCount > getRetryMax  . $retryCount>= ".$this->getRetryMax());
            $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], false);
            $this->out("reject msg ".$msg->delivery_info['delivery_tag']);
            return true;
        }

        $this->out("retry count:$retryCount");

        //原消息不要了，重新 再发送一条 延迟消息

//        try{
//            $this->txSelect();

        $baseRetryCnt = $this->getRetryTime();
//        $this->out("baseRetryCnt:".json_encode($baseRetryCnt));
        $arguments = $attr;//原 消息属性得保留，如 msg_id
        $header = $attr['header'];
        unset($arguments['header']);
        //延迟时间
        $header['x-delay'] = $baseRetryCnt[$retryCount] * 1000;
        $header['x-retry-count'] = $retryCount+1;
//        $this->out("header:".json_encode($header));

        //原消息不要了，回复 确认 机制
        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        $this->out("ack old msg:".$msg->delivery_info['delivery_tag']);
        //发送新 消息
        $msgBody = serialize($body);
        $this->publish($msgBody,$exchange,"",$header,$arguments);
//            $this->txCommit();
//        }catch (\Exception $e){
//            $this->txRollback();
//            $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'],true);
//        }
    }


    //进程结束后回调函数
    function regUserShutdownCallback($func){
        $this->_userShutdownCallback = $func;
    }

    function execUserShutdownCallback(){
        if($this->_userShutdownCallback){
            call_user_func($this->_userShutdownCallback);
        }
    }

    function regSignals(){
        $self = $this;
        $this->_timeOutCallback = function () use($self){
            $this->execUserShutdownCallback();
            fopen($this->_pidPath,"w");

            if (extension_loaded('posix')) {
                posix_kill(getmypid(), SIGKILL);
            }
            exit("529");
        };

        if($this->supportsPcntlSignals()){

            pcntl_async_signals(true);
            pcntl_signal(SIGALRM, $this->_timeOutCallback);
            pcntl_alarm($this->_userCallbackFuncExecTimeout);

            pcntl_signal(SIGTERM, function(){
                $this->_nowStop = 1;
            });
        }
    }


    //消费者 - 回调
    function subscribeCallback($msg,$userCallback,$exchange,$noAck){
        $this->out("im in base subscribeCallback");
        $body = self::getBody($msg);
        $attr = self::getReceiveAttr($msg);
        $this->out("rabbitmq return msg arrt:".json_encode($attr));
//        $recall = array("AMQPMessage" => $msg, 'body' => $body, 'attr' => $attr);
        if($noAck){
            //非确认机制，不需要走重试机制
            $this->out(" no ack ");
            call_user_func($userCallback,$body);
        }else{
            try{
                $this->regSignals();

                $this->out(" exec user callback function");
                $rs = call_user_func($userCallback,$body);

//                $info = " no info";
//                if($rs){
//                    $info = json_encode($info);
//                }
                $this->out(" user callback function return info:".$rs);
                if($rs){
                    $this->out(" return rabbitmq ack!");
                    $this->out(" loop for waiting msg...");
                    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag'] );
                    return true;
                }
//                if(!$rs || !isset($rs['return']) || !$rs['return'] || ! in_array($rs['return'],array_flip($this->getReturnRabbitmqAckTypeDesc()) ) ){
//                    $this->out(" return rabbitmq ack!");
//                    $this->out(" loop for waiting msg...");
//                    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag'] );
//                    return true;
//                }
//                $rs=['return'=>'reject'];
//                $this->out("user trigger retry:".$rs['return']);
//                if($rs['return'] == 'reject' && isset($rs['requeue']) && $rs['requeue'] ){
//                    $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], false);
//                    return true;
//                }
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


    //consumer 订阅 一个队列
    function baseSubscribe($exchangeName,$queueName,$consumerTag = "" ,$userCallback,$noAck = false){
        $this->out("rqbbitmqBase set new Consume : queue:$queueName , consumerTag:$consumerTag ,noAck: $noAck , exchangeName : $exchangeName. ");
        if(!$consumerTag){
            $consumerTag = $queueName . time();
        }

        $self = $this;
        $this->out("set basic callback func ");
        $baseCallback = function($msg) use($userCallback,$self,$exchangeName,$noAck){
            $self->subscribeCallback($msg,$userCallback,$exchangeName,$noAck);
            return true;
        };


        $this->_loopDeadCustomerTag = $consumerTag;

        $this->getChannel()->basic_consume($queueName,$consumerTag,false,$noAck,false,false,$baseCallback);
    }
    //消费者开启 守护 状态
    function startListenerWait(){
        $this->out(" start Listener Wait... ");
        $this->_startLoopDead = 1;

        if(strtoupper(substr(PHP_OS,0,3))!=='WIN'){
            if($this->supportsPcntlSignals()){
                $fd = fopen($this->_pidPath,"w");
                fwrite($fd,getmypid());
            }
        }

        while (1){
            if($this->_consumerStopWait){
                $this->cancelRabbitmqConsumer();
                $this->_timeOutCallback;
                break;
            }

            if($this->_nowStop){
                $this->cancelRabbitmqConsumer();
                $this->_timeOutCallback;
                break;
            }

            $this->getChannel()->wait(array("func a","func b"));
        }
    }

    function cancelRabbitmqConsumer(){
        $this->out(" cancel consumer.");
        $this->listenerCancel($this->_loopDeadCustomerTag);
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

    function supportsPcntlSignals()
    {
        return extension_loaded('pcntl');
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