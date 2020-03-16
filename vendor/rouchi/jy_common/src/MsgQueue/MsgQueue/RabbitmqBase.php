<?php
namespace Jy\Common\MsgQueue\MsgQueue;

use Jy\Common\MsgQueue\Contract\AmqpBaseInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Wire\AMQPTable;
use PhpAmqpLib\Message\AMQPMessage;
use Jy\Log\Facades\Log;

class RabbitmqBase implements AmqpBaseInterface {
    //调试模式,0:关闭，1：只输出到屏幕 2：只记日志 3：输出到屏幕并记日志
    protected $_debug = 0;//注：开启 日志模式，记得引入log包
    private $_conn = null;//SOCK FD
    private $_channel = null;//channel
//    private $_conf =['host' => '127.0.0.1', 'port' => 5672, 'user' => 'root', 'pwd' => 'root', 'vhost' => '/',];
//    private $_conf =['host' => '172.19.113.249', 'port' => 5672, 'user' => 'root', 'pwd' => 'sfat324#43523dak&', 'vhost' => '/',];
    private $_conf = null;//连接，配置信息
    private $_confKey = array('host','port','user','pwd','vhost');//连接，配置信息，KEY值

    private $_consumerStopWait = 0;//用户取消了绑定，结束守护
    private $_retryTime  = array(1,5,10);//重试 次数及时间
    private $_userCallbackFuncExecTimeout = 20;//用户回调函数，超时时间

    private $_startLoopDead = false;//已开始 死循环  守护模式
    private $_loopDeadCustomerTag = "";//跟consumerTag值相同，用于发消息给rabbitmq 取消订阅
    private $_userShutdownCallback = "";//consumer进程结束守护时，回调用户自定义<收尾>函数

    public $_pidPath = "/tmp/php_consumer_rabbitmq.pid";
    public $_signalStopLoop = 0;//收到结束信号，立即停止 守护模式
    public $_timeOutCallback = null;//超时，回调函数

    //一条消息最大值，KB为单位
    private $_messageMaxLength = 1024;

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
        530=>"message length > {0}",
        531=>"userBeanClassCollection is null",

        600=>"NOT_FOUND - no exchange",
        601=>"PRECONDITION_FAILED - cannot switch from confirm to tx mode",
        602=>"AMQP-rabbit doesn't define data of type []",
        603=>"NOT_FOUND - no queue",
        604=>"PRECONDITION_FAILED - inequivalent arg 'x-dead-letter-exchange' for queue",
        605=>":NO_ROUTE",


        800=>'user callback function runtime exception ,alarm!',

    );

    private $_userExceptionCodeDesc = array(
        900=>"user temporary drop msg, msg requeue to Rabbitmq server ,sometime retry",
        901=>"user final drop msg",
    );


    function __construct($conf){
        $this->checkConfigFormat($conf);
        $this->_conf = $conf;
    }

    function initBase(){
        $this->getConn();//创建连接
        $this->getChannel();//创建管道
    }

    function testENV(){
        $os = $this->getOs();
        $this->out("os:".$os);
        if($os === "WIN"){
            $this->out("notice:  linux OS is very good.");
        }

        if(!$this->supportsPcntlSignals()){
            $this->out("notice : php ext:pcntl ,not supports. OS signal will be lose...");
        }

        if(!extension_loaded('posix')){
            $this->out("notice :php ext: posix ,not supports. kill process is unknow");
        }

        if(!$this->is_cli()){
            $this->out("warning : exec env not CLI ,please set_time_limit(0) | max_execution_time(0)  if  u r  consumer");
        }

        if(ini_get("max_execution_time")){
            $this->out("warning : please set max_execution_time = 0 ");
        }

//        if(class_exists("Jy\Log\Facades\Log")){
//            $this->out("notice : depend on :Log composer bag");
//        }
    }

    function is_cli(){
        return preg_match("/cli/i", php_sapi_name()) ? true : false;
    }

    function getConn(){
        if($this->_conn){
            //虽然 连接有数据，但可能 连接已经断了
            if($this->_conn->isConnected()){
                return $this->_conn;
            }

            $this->resetConn();
        }

        $insist = false;
        $login_method = 'AMQPLAIN';
        $login_response = null;
        $locale = 'en_US';


        $connection_timeout = 3.0;
        $read_write_timeout = 5.0;
        $context = null;
        $keepAlive = false;
        $heartbeat = 0;


        $conf = $this->_conf;
        $conn = new AMQPStreamConnection( //建立生产者与mq之间的连接
            $conf['host'], $conf['port'], $conf['user'], $conf['pwd'], $conf['vhost'],
            $insist,$login_method,$login_response,$locale,
            $connection_timeout,$read_write_timeout,$context,$keepAlive,$heartbeat
        );

        $this->out("connect rabbit config;".json_encode($conf));
        if(!$conn->isConnected()){
            $this->throwException(509);
        }
        $this->_conn = $conn;
        return $this->_conn;
    }
    //检查配置文件格式是否正确
    function checkConfigFormat($config ){
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
        $this->initBase();
    }

    function getChannel() : AMQPChannel{
        if(!$this->_channel){
            $this->out("create new channel.");
            //在已连接基础上建立生产者与mq之间的通道
            $this->_channel = $this->getConn()->channel();
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

    function setConf($conf){
        $this->_conf = $conf;
    }

    function setDebug($flag){
        $this->_debug =  $flag;
    }

    function setBasicQos(int $num){
        $this->getChannel()->basic_qos(null,$num,null);
    }

    //进程结束后回调函数
    function regUserShutdownCallback($func){
        $this->_userShutdownCallback = $func;
    }

    function setStopListenerWait($flag){
        $this->_consumerStopWait = $flag;
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

    function setMessageMaxLength(int $num){
        $this->_messageMaxLength = $num;
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
        $this->out("test queue exist :");
        if($arguments){
            $arguments = new AMQPTable($arguments);
        }
        try{
            $this->getChannel()->queue_declare($queueName,true,$durable,false,$autoDel,false,$arguments);
            $this->out("queue exist : true");
            return 1;
        }catch (\Exception $e){
            $this->resetConn();
            $this->out("queue exist : false");
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

        if( strlen($msgBody) / 1024 >= $this->_messageMaxLength){
            $this->throwException(530,array($this->_messageMaxLength . " kb "));
        }

        $AMQPMessage = new AMQPMessage($msgBody,$finalArguments);
        $this->getChannel()->basic_publish($AMQPMessage,$exchangeName,$routingKey,true);
        $this->baseWait();
    }

    //等待rabbitmq 返回内容
    function baseWait(){
        $this->getChannel()->wait_for_pending_acks_returns();
    }

    function reject($msg){
        $this->out("reject msg delivery_tag:".$msg->delivery_info['delivery_tag']);
        $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], false);
    }

    function getRetryPolicy($body){
        $this->out("get retry  policy");
        $beanRetry = $body->getRetryTime();
        if(!$beanRetry){
            if($this->_subscribeType == 2){
                $beanRetry = $this->getBeanRetry($body);
                if($beanRetry){
                    $this->out(" level 2: many bean diy set");
                }
            }else{
                $beanRetry = $this->getGroupSubscribeRetryTime();
                if($beanRetry){
                    $this->out(" level 3: groupSubscribe diy set");
                }
            }
        }else{
            $this->out(" level 1 : msg has  ");
        }

        if(!$beanRetry){
            $beanRetry = $this->getRetryTime();
            if(!$beanRetry){
                $this->out(" no set retry");
                return false;
            }else{
                $this->out("level 4 : used system default RabbitmqBase Retry:");
            }
        }

        return $beanRetry;
    }


    //重试机制
    function retry($attr,$body,$exchange,$msg){
        $beanRetry = $this->getRetryPolicy($body);
        if(!$beanRetry){
            return false;
        }

        $this->out(json_encode($beanRetry));

        //重复-已发送次数
        $retryCount = 0;
        if (isset($attr['header']['x-retry-count'])) {
            $retryCount = $attr['header']['x-retry-count'];
        }

        $retryMax = count($beanRetry);

        $this->out("delivery_tag:".$msg->delivery_info['delivery_tag']);
        $this->out("attr:".json_encode($attr));
        //判断 是否 超过 最大投递次数
        if ($retryCount >=  $retryMax ) {
            $this->out("$retryCount > getRetryMax ( ".$retryMax." )");
            $this->reject($msg);

            return true;
        }

        $this->out("retry count:$retryCount");

        //原消息不要了，重新 再发送一条 延迟消息

//        try{
//            $this->txSelect();

//        $baseRetryCnt = $this->getRetryTime();
//        $this->out("baseRetryCnt:".json_encode($baseRetryCnt));
        $arguments = $attr;//原 消息属性得保留，如 msg_id
        $header = $attr['header'];
        unset($arguments['header']);
        //延迟时间
        $header['x-delay'] = $beanRetry[$retryCount] * 1000;
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


    function execUserShutdownCallback(){
        if($this->_userShutdownCallback){
            $this->out("  execUserShutdownCallback");
            call_user_func($this->_userShutdownCallback);
        }else{
            $this->out(" no exec user shutdown");
        }
    }

    function regSignals(){
        $self = $this;
        $this->_timeOutCallback = function () {
            $this->out("oh no~my name is nightmare ,exec time out! start shutdown process:");
            $this->execUserShutdownCallback();
            fopen($this->_pidPath,"w");

            if (extension_loaded('posix')) {
                $this->out("posix_kill pid:".getmypid());
                posix_kill(getmypid(), SIGKILL);
            }
            exit(" ok 529");
        };

        if($this->supportsPcntlSignals()){

            pcntl_async_signals(true);

            pcntl_signal(SIGTERM, function(){
                Log::info("receive system signal call:SIGTERM  ".time());
//                $this->_timeOutCallback;
                echo (" SIGTERM , no goods 1.\n");
            });

            pcntl_signal(SIGINT, function() {
//                $this->_timeOutCallback;
                Log::info("receive system signal call:SIGINT  ".time());
                echo(" SIGINT  , no goods 2.\n");
            });
        }
    }

    function setTimeoutSignal(){
        if(!$this->supportsPcntlSignals()){
            return false;
        }
        $this->out("set timeout signal " . $this->_userCallbackFuncExecTimeout);
        pcntl_signal(SIGALRM, $this->_timeOutCallback);
        pcntl_alarm($this->_userCallbackFuncExecTimeout);
    }

    function cancelTimeoutSignal(){
        if(!$this->supportsPcntlSignals()){
            return false;
        }
        $this->out("exec OK! cancel timeout signal ");
        pcntl_alarm(0);
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
                $this->setTimeoutSignal();
                $this->out(" exec user callback function");
                call_user_func($userCallback,$body);
                $this->cancelTimeoutSignal();

                $this->out(" return rabbitmq ack . loop for waiting msg...");
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag'] );
                return true;
            }catch (\Exception $e) {
                $info = $e->getMessage();
                $code = $e->getCode();
                $this->out("subscribeCallback exception retry, code:" .$code . " , info".$info);
                if(in_array($code,array_flip($this->_userExceptionCodeDesc))){
                    if($code == 900){
                        $this->reject($msg);
                    }elseif($code == 901){
                        $this->retry($attr,$body,$exchange,$msg);
                    }
                }else{
                    $this->out("runtime err");
                    $this->reject($msg);
                    //用户运行时错误
                }
            }
        }
    }


    //consumer 订阅 一个队列
    function baseSubscribe($exchangeName,$queueName,$consumerTag = "" ,$userCallback,$noAck = false){
        $this->out("rqbbitmqBase set new Consume : queue:$queueName , consumerTag:$consumerTag ,noAck: $noAck , exchangeName : $exchangeName. ");
        if(!$consumerTag){
            $consumerTag = $queueName . time();
        }

        $this->out(" Subscribe type:".$this->_subscribeType );

        $self = $this;
        $this->out("set basic callback func ");
        $baseCallback = function($msg) use($userCallback,$self,$exchangeName,$noAck){
            $this->out(__CLASS__ . " baseCallback");
            $self->subscribeCallback($msg,$userCallback,$exchangeName,$noAck);
            return true;
        };


        $this->_loopDeadCustomerTag = $consumerTag;

        $this->getChannel()->basic_consume($queueName,$consumerTag,false,$noAck,false,false,$baseCallback);
    }

    function getOs(){
        $os = strtoupper(substr(PHP_OS,0,3));
        return $os;
    }

    //消费者开启 守护 状态
    function startListenerWait(){
        $this->out(" start Listener Wait... ");
        $this->_startLoopDead = 1;

        if($this->getOs() !== "WIN"){
            if($this->supportsPcntlSignals()){
                $fd = fopen($this->_pidPath,"w");
                fwrite($fd,getmypid());
            }
        }

//        var_dump();exit;
        $this->regSignals();

        while (1){
            if($this->_consumerStopWait){
                $this->cancelRabbitmqConsumer();
                $this->_timeOutCallback;
                break;
            }

//            if($this->_signalStopLoop){
//                $this->cancelRabbitmqConsumer();
//                $this->_timeOutCallback;
//                exit("529");
//            }

            $this->getChannel()->wait();
        }
    }

    function cancelRabbitmqConsumer(){
        $this->out(" cancel consumer.");
        $this->listenerCancel($this->_loopDeadCustomerTag);
    }


    function listenerCancel($consumerTag){
        $this->getChannel()->basic_cancel($consumerTag);
    }


    function out($msg ,$br = 1){
        if(!$this->_debug){
            return -1;
        }
        if($br){
            $os = $this->getOs();
            if (preg_match("/cli/i", php_sapi_name())){
                if($os == "WIN"){
                    $msg .= "\r\n";
                }else{
                    $msg .= "\n";
                }

            }else{
                $msg .=  "<br/>";
            }
        }

        if($this->_debug == 1 ||  $this->_debug == 3){
            echo $msg;
        }

        if($this->_debug == 2 ||  $this->_debug == 3){
            Log::notice($msg);
        }

        return true;
    }

    function supportsPcntlSignals(){
        return extension_loaded('pcntl');
    }
    //将rabbitmq push的消息属性值，解析成数组，删掉Header
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

    //将rabbitmq push的消息体，根据不同头类型，解析成不同类型。
    //实际大部分是 将序列化的<字符串>转成obj
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
    //将rabbitmq push的消息属性值，解析成数组，只取Header
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