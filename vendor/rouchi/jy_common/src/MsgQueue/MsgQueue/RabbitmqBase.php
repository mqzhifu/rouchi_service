<?php
namespace Jy\Common\MsgQueue\MsgQueue;

use Jy\Common\MsgQueue\Exception\RejectMsgException;
use Jy\Common\MsgQueue\Exception\RetryException;

use Jy\Common\MsgQueue\Contract\AmqpBaseInterface;

use Jy\Common\MsgQueue\MsgQueue\RabbitmqPhpExt;
use Jy\Common\MsgQueue\MsgQueue\RabbitmqComposerLib;


//use Jy\Log\Facades\Log;

class RabbitmqBase implements AmqpBaseInterface {
//    private $_exchange = "many.header.delay";
    private $_exchange = "test.header.delay";

    //调试模式,0:关闭，1：只输出到屏幕 2：只记日志 3：输出到屏幕并记日志
    protected $_debug = 0;//注：开启 日志模式，记得引入log包
    public  $_extProvider = null;//具体实现的类
    private $_extType = 2;//
    private $_conn = null;//SOCK FD
    private $_channel = null;//channel
//    private $_conf =['host' => '127.0.0.1', 'port' => 5672, 'user' => 'root', 'pwd' => 'root', 'vhost' => '/',];
//    private $_conf =['host' => '172.19.113.249', 'port' => 5672, 'user' => 'root', 'pwd' => 'sfat324#43523dak&', 'vhost' => '/',];
    private $_conf = null;//连接，配置信息
    private $_confKey = array('host','port','user','pwd','vhost');//连接，配置信息，KEY值

    private $_consumerStopLoop = 0;//用户取消了绑定，结束守护
    private $_retryTime  = array(1,5,10);//重试 次数及时间
    private $_userCallbackFuncExecTimeout = 20;//用户回调函数，超时时间

    private $_loopDeadConsumerTag = "";//跟consumerTag值相同，用于发消息给rabbitmq 取消订阅

    public $_pidPath = "/tmp/php_consumer_rabbitmq.pid";
    public $_signalStopLoop = 0;//收到结束信号，立即停止 守护模式
    public $_timeOutCallback = null;//超时，回调函数

    private $_ignoreSendNoQueueReceive = false;
    private $_ignoreSendNoQueueReceivePool = [];

    //一条消息最大值，KB为单位
    private $_messageMaxLength = 1024;

    private $_userCallBackType = array('ack','nack','rabbitmqReturnErr','shutdown','serverBackConsumer',"serverBackConsumerRetry");
    private $_userShutdownCallback = "";//consumer进程结束守护时，回调用户自定义<收尾>函数
    private $_userCallBack = null;

    //无奈PHP序列化不支持闭包，借此方法，暂存子类的FUNC 吧
    public $_userRegFunc = array();

    public $_header = null;

    public $_consumerBindQueueName = "";
    private $_codeErrMessageByExt = array(
        '404'=>"NOT_FOUND - no exchange",
    );


    private $_codeErrMessage = array(
        400=>'code is null',
        401=>'code not is key',
        500=>"msgBody is null",
        501=>"msgBody is bool",
        502=>"<message_id> key value: must null",
        503=>"<type> key value: must null",

        504=>" set mode value : is error.",
        505=>"confirm mode or  tx mode just have use one,is mutex -1",
        506=>"N-Ack {0}",
        507=>"send msg is not route Queue, server_err_return_listener {0}",
        508=>"consumerTag is null",
        509=>"conn failed",
        510=>"queue name is null",
        511=>"exchange name is null",
        512=>"<timestamp> key value: must null",
        513=>"user diy bean not match rabbitmq server back header.",
        514=>"beanName is not object",
        515=>"config get :rabbitmq key  is null",
        516=>"config key err.",
        517=>"delayTime must int.",
        518=>"delayTime must > 1000",
        519=>"delayTime must <= 7 days.",
        520=>"rabbitmq return ack not include header",
        521=>'set bean must obj',
        522=>'please setSubscribeBean',
        523=>'setSubscribeBean: please set {0} method',
        524=>"retry time <= 1 days.",
        525=>"retry time must is int.",
        526=>"timeout is null.",
        527=>"timeout min 10",
        528=>"timeout max 600",
        529=>"exec user program timeout ",
        530=>"message length > {0}",
        531=>"userBeanClassCollection is null",
        532=>"qos <= 0",
        533=>'no reg serverBackConsumerRetry callback',
        534=>'_extType value is err',

        //类库直接抛出异常，程序停止
        600=>"NOT_FOUND - no exchange",
        601=>"PRECONDITION_FAILED - cannot switch from confirm to tx mode",
        602=>"AMQP-rabbit doesn't define data of type []",
        604=>"PRECONDITION_FAILED - inequivalent arg 'x-dead-letter-exchange' for queue",

        603=>"NOT_FOUND - no queue",
        //运行时异常
        312=>"NO_ROUTE - exchange test.header.delay , routingKey",


        800=>'user callback function runtime exception ,alarm!',

    );

//    private $_userExceptionCodeDesc = array(
//        900=>"user temporary drop msg, msg requeue to Rabbitmq server ,sometime retry",
//        901=>"user final drop msg",
//    );


    function __construct(){}

    function init(){
        if(!$this->_conf){
            $this->throwException(515);
        }

        if($this->_extType == 2){
            $this->_extProvider = new RabbitmqPhpExt();
        }elseif($this->_extType == 1){
            $this->_extProvider = new RabbitmqComposerLib();
        }else{
            $this->throwException(534);
        }

        $this->getConn();//创建连接
        $this->testENV();
        $this->regDefaultAllCallback();
    }

    function setConf($conf){
        if(!$conf){
            $this->throwException(515);
        }
        $this->checkConfigFormat($conf);
        $this->_conf = $conf;
    }

    function setTopicName($topName){
        $this->_exchange = $topName;
    }

    function setExtType($type){
        $this->_extType = $type;
    }

    function setRabbitmqAckCallback($ackFunc,$nackFunc){
        $this->_extProvider->setRabbitmqAckCallback($ackFunc,$nackFunc);
    }

    function setRabbitmqErrCallback($clientReturnListener){
        $this->_extProvider->setRabbitmqErrCallback($clientReturnListener);
    }

    function testENV(){
        $os = $this->getOs();
        $this->out("os:".$os);
        if($os === "WIN"){
            $this->out("notice : linux OS is very good.");
        }

        if(!$this->supportsPcntlSignals()){
            $this->out("notice : php ext <pcntl> ,not supports. OS signal will be lose...");
        }

        if(!extension_loaded('posix')){
            $this->out("notice : php ext <posix> ,not supports. kill process is unknow");
        }

        if(!$this->is_cli()){
            $this->out("warning : exec env not CLI ,please set_time_limit(0) | max_execution_time(0)  if  u r  consumer");
        }

        if(ini_get("max_execution_time")){
            $this->out("warning : please set max_execution_time = 0 ");
        }

        if($this->_extType == 1){
            if(!class_exists("\PhpAmqpLib\Channel\AMQPChannel")){
                $this->out("warning :  PhpAmqpLib composer lib ,not supports ");
            }

            if(!extension_loaded('sockets')){
                $this->out("warning : php ext <sockets>,not supports.");
            }
        }else{
            if(!extension_loaded('amqp')){
                $this->out("warning : php ext <amqp> ,not supports ");
            }
        }

        //php-amqp 2.11 以后需要
//        phpseclib/phpseclib
//        if(!extension_loaded('mbstring')){
//            $this->out("warning :php ext: sockets ,not supports.");
//        }

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
        $conn = $this->_extProvider->connect($this->_conf);

        $conf = $this->_conf;
        $conf['pwd'] = "********";
        $this->out("connect rabbit config;".json_encode($conf));

        if(!$this->_extProvider->isConnected()){
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
        $this->init();
    }

//    function getChannel(){
//        if(!$this->_channel){
//            $this->out("create new channel.");
//            //在已连接基础上建立生产者与mq之间的通道
//            $this->getConn();
//            $this->_channel = $this->_extProvider->getChannel();
//        }
//
//        return $this->_channel;
//    }

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

    function setDebug($flag){
        $this->_debug =  $flag;
    }

    function setBasicQos(int $num){
        $this->_extProvider->setBasicQos($num);
    }
    //进程结束后回调函数
    function regUserCallbackShutdown($func){
        $this->_userShutdownCallback = $func;
    }
//    function regUserCallbackRabbitmqReturnErr(){
//
//    }

    function regUserCallback($key,$func){
        $this->out(" regUserCallback key:".$key);
        $this->_userCallBack[$key] = $func;
    }

    function regUserFunc($key,$type,$func){
        $this->out("regUserFunc ". $key . " type:".$type);
        $this->_userRegFunc[$key][$type] = $func;
    }

    function getRegUserFunc($key = null,$type = null){
        if($key && $type){
            if(isset($this->_userRegFunc[$key][$type]) && $this->_userRegFunc[$key][$type]){
                return $this->_userRegFunc[$key][$type];
            }else{
                $this->out("notice : getRegUserFunc($key , $type) is null");
                return false;
            }

        }
        return $this->_userRegFunc;
    }

    function userCallbackExec($type,$data){
        $this->out("userCallbackExec type:".$type);
        if($this->_userCallBack){
            $f = 0;
            foreach ($data['headers'] as $k=>$v) {
                if(isset($this->_userCallBack[$k]) && $this->_userCallBack[$k]){
                    call_user_func($this->_userCallBack[$k],$type,$data);
                    $f = 1;
                    break;
                }
                //重试的消息,有点复杂
                if( $k == $this->_consumerBindQueueName ){
                    if(!isset($this->_userCallBack[$k]) || !$this->_userCallBack[$k]){
                        $this->throwException(533);
                    }
                    call_user_func($this->_userCallBack[$k],'serverBackConsumerRetry',$data);
                    $f = 1;
                    break;
                }
            }
            if( !$f ){
                $this->out("userCallbackExec type($type): no match headers" . json_encode($data['headers']));
            }
        }else{
            $this->out(" _userCallback: not set");
        }
    }

    function quitConsumerDemon(){
        $this->_consumerStopLoop = 1;
    }

    function setRetryTime(array $time){
        $this->out(" setRetryTime ". json_encode($time));
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
        $this->_extProvider->confirmSelectMode();
    }
    //开启一个事务
    function txSelect(){
        $this->out("txSelect");
        $this->_extProvider->txSelect();
    }

    function txCommit(){
        $this->out("txCommit");
        $this->_extProvider->txCommit();
    }

    function txRollback(){
        $this->out("rollback");
        $this->_extProvider->txRollback();
    }

    //创建一个队列
    function setQueue($queueName,$arguments = null,$durable = true,$autoDelete = false){
        if(!$queueName){
            $this->throwException(510);
        }

        $this->out("setQueue $queueName , arguments:".json_encode($arguments) . " durable : $durable , autoDelete : $autoDelete");

        $this->_extProvider->queueDeclare($queueName,false,$durable,false,$autoDelete,true,$arguments);
//        $this->_extProvider->baseWait();
    }
    //绑定一个队列
    function bindQueue($queueName,$exchangeName = null,$routingKey = '',$header = null){
        if(!$exchangeName){
            if($this->_exchange){
                $exchangeName = $this->_exchange;
            }else{
                $this->throwException(511);
            }
        }
        $this->out("bindQueue  queueName:$queueName exchangeName:$exchangeName ");
        if(!$header){
            $header = $this->_header;
        }
        if($header){
            foreach ($header as $k=>$v) {
                $this->out(" header ".$v);
            }
        }
        try{
            $this->_extProvider->queueBind($queueName,$exchangeName,$routingKey,true,$header);
        }catch (Exception $e){
            $this->out( "bindQueue failed.");
            $this->out($e->getMessage());
            exit;
        }
        $this->out(" binding end.");
    }
    //判断队列是否已经存在
    function queueExist($queueName,$arguments= null,$durable= null,$autoDel= null){
        $this->out("test queue exist ......");
        try{
            $this->_extProvider->queueDeclare($queueName,true,$durable,false,$autoDel,false,$arguments);
            $this->out("queue exist : true");
            return 1;
        }catch (\Exception $e){
            $this->resetConn();
            $this->out("queue exist : false");
            return 0;
        }
    }

    function deleteQueue($queueName){
        $this->_extProvider->deleteQueue($queueName);
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

        $this->_extProvider->exchangeDeclare($exchangeName,$type,$arguments);
//            $this->out("create exchange .");
//        }
    }

    function unbindExchangeQueue($exchangeName,$queueName,$routingKey = "",$arguments = null){
        $this->_extProvider->queue_unbind($queueName,$exchangeName,$routingKey,$arguments);
    }

    function deleteExchange($exchangeName){
        $this->_extProvider->deleteExchange($exchangeName);
    }
    //exchange 相关end========================================================

    function setReceivedServerMsgMaxNumByOneTime(int $num){
        if(!$num || $num <= 0){
            $this->throwException(532);
        }
        $this->setBasicQos($num);
    }

    //发布一条消息
    function publish($msgBody ,$exchangeName,$routingKey = '',$header = null,$arguments = null){
        if(!$exchangeName){
            if(!$this->_exchange){
                $this->throwException(511);
            }
            $exchangeName = $this->_exchange;
        }

        if($header){
            $header = array_merge($this->_header,$header);
        }else{
            $header =  $this->_header;
        }

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
            $preProcessHeader = $this->preProcessHeader($header);
//            $finalArguments['application_headers'] = $preProcessHeader;
            $finalArguments['headers'] = $preProcessHeader;
        }

        if($arguments){
            $finalArguments = array_merge($finalArguments,$arguments);
        }

        if( strlen($msgBody) / 1024 >= $this->_messageMaxLength){
            $this->throwException(530,array($this->_messageMaxLength . " kb "));
        }

        return $this->_extProvider->basicPublish($exchangeName,$routingKey,$msgBody,$finalArguments);
    }

    //预处理，头信息
    function preProcessHeader($header = null){
        //校验 延迟队列 的时间值
        if(isset($header['x-delay']) && $header['x-delay']){
            $delayTime = (int)$header['x-delay'];
            if(!$delayTime ){
                $this->throwException(517);
            }

            if($delayTime < 1000){
                $this->throwException(518);
            }

            $day = 7 * 24 * 60 *60 * 1000;
            if( $delayTime > $day){
                $this->throwException(519);
            }
        }

        //主要是给，延迟队列
        $rabbitHeader = $this->_header;
        if($header ){
            $rabbitHeader = array_merge($rabbitHeader,$header);
        }
        return $rabbitHeader;
    }

    function baseWait(){
        $this->_extProvider->baseWait();
    }

    function reject($msg){
        $this->out("reject msg delivery_tag:".$msg->delivery_info['delivery_tag']);
        $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], false);
    }

    function getRetryPolicy($msgRetry = null){
        $this->out("get retry  policy");
//        $beanRetry = $obj->getRetryTime();
        if($msgRetry){
            $this->out(" level 1 : msg has  ".json_encode($msgRetry));
            return $msgRetry;
        }
        $beanRetry = $this->getRetryTime();
        if(!$beanRetry){
            $this->out("no set retry times");
            return false;
        }
        $this->out(" level 2 : used system default RabbitmqBase Retry .".json_encode($beanRetry));
        return $beanRetry;

    }


    //重试机制
    function retry($exchange,$backData,$backQueue,$msgRetry = null){
        $parseBackData = $this->_extProvider->parseBackDataToUniteArr($backData);
        $beanRetry = $this->getRetryPolicy($msgRetry);
        if(!$beanRetry){
            return false;
        }
        $retryMax = count($beanRetry);
        $this->out(" retryMax: ".$retryMax);

        //重复-已发送次数
        $retryCount = 0;
        if (isset($parseBackData['headers']['x-retry-count']) && $parseBackData['headers']['x-retry-count']) {
            $retryCount = $parseBackData['headers']['x-retry-count'];
        }
        $this->out("now retry count:$retryCount");
//        $this->out("delivery_tag:".$msg->delivery_info['delivery_tag']);
        //判断 是否 超过 最大投递次数
        if ($retryCount >=  $retryMax ) {
            $this->out("$retryCount > getRetryMax ( ".$retryMax." )");
            $this->_extProvider->reject($backData,$backQueue);

            return false;
        }

        //原消息不要了，重新 再发送一条 延迟消息(body:一样)

//        try{
//            $this->txSelect();

        $body = $parseBackData['body'];
        unset($parseBackData['header']);
        unset($parseBackData['body']);

        $header = array("match"=>'any');
        //这里注意一下，新的retry，防止一条消息重新 发送，指定到该消息的队列，不给其它队列发送
        $header[$this->_consumerBindQueueName] = $this->_consumerBindQueueName;
        //延迟时间
        $header['x-delay'] = $beanRetry[$retryCount] * 1000;
        $header['x-retry-count'] = $retryCount+1;
        $parseBackData['headers'] = $header;

        //原消息不要了，回复 确认
        $this->_extProvider->ack($backData,$backQueue);

//        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
//        $this->out("ack old msg:".$msg->delivery_info['delivery_tag']);
        //发送新 消息
//        $msgBody = serialize($body);
//        function publish($msgBody ,$exchangeName,$routingKey = '',$header = null,$arguments = null){


//        $this->out("basicPublish body".json_encode($body));
//        $this->out("retry basicPublish parseBackData ".json_encode($parseBackData));
        $this->_extProvider->basicPublish($exchange,"",$body,$parseBackData);
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
            $this->out("oh no~ nightmare !!!,exec time out , start shutdown process:");
            $this->execUserShutdownCallback();
            if($this->getOs() !== "WIN"){
                if($this->supportsPcntlSignals()){
                    unlink($this->_pidPath);
                }
            }

            if (extension_loaded('posix')) {
                $this->out("posix_kill pid:".getmypid());
                posix_kill(getmypid(), SIGKILL);
            }
            exit(" script done.");
        };

        if($this->supportsPcntlSignals()){
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, function(){
                $this->execUserShutdownCallback();
                echo (" SIGTERM , no goods 1.\n");
            });

            pcntl_signal(SIGINT, function() {
                $this->execUserShutdownCallback();
                echo(" SIGINT  , no goods 2.\n");
            });
        }
    }

    function setTimeoutSignal(){
        $this->out("set timeout signal ",0);
        if(!$this->supportsPcntlSignals()){
            $this->out(" not support");
            return false;
        }
        $this->out(" timeout "  . $this->_userCallbackFuncExecTimeout);
        pcntl_signal(SIGALRM, $this->_timeOutCallback);
        pcntl_alarm($this->_userCallbackFuncExecTimeout);
    }

    function cancelTimeoutSignal(){
        $this->out("cancel timeout signal " , 0);
        if(!$this->supportsPcntlSignals()){
            $this->out(" not support");
            return false;
        }
        $this->out(" ok ");

        pcntl_alarm(0);
    }

    //消费者 - 回调
    function subscribeCallback($backData,$backQueueObj = null ,$exchange,$noAck){
        $this->out("im in base subscribeCallback");
//        $msgArr = $this->_extProvider->parseBackDataToUniteArr($backData);
//        $body = $this->transcodingMsgBody($msgArr['body'],2,$msgArr['content_type']);
//        $info = $this->_extProvider->debugMergeInfo($backData);

//        $this->out("rabbitmq return msg arrt:".json_encode($info));
//        $recall = array("AMQPMessage" => $msg, 'body' => $body, 'attr' => $attr);
        $parseBackData = $this->_extProvider->parseBackDataToUniteArr($backData);
//        $this->out(json_encode($parseBackData));
        if($noAck){
            //非确认机制，不需要走重试机制
            $this->out(" no ack ");

            $this->userCallbackExec('serverBackConsumer',$parseBackData);
        }else{
            try{
                $this->setTimeoutSignal();
                $this->out(" exec user callback function");
                $this->userCallbackExec("serverBackConsumer",$parseBackData);
                $this->cancelTimeoutSignal();

                $this->_extProvider->ack($backData,$backQueueObj);
                $this->out(" return rabbitmq ack . loop for waiting msg...");
            }catch (RejectMsgException $e){
                $this->out("subscribeCallback RejectException");
                $this->_extProvider->reject($backData,$backQueueObj);
            }catch (RetryException $e){
                $msgRetry = $e->getRetry();
                $this->out("subscribeCallback RetryException");
                $this->retry($exchange,$backData,$backQueueObj,$msgRetry);
            }catch (\Exception $e) {
                $exceptionInfo = $e->getMessage();
                $code = $e->getCode();
                $this->out("subscribeCallback runtime exception code:" .$code . " , exceptionInfo".$exceptionInfo);
                $this->_extProvider->reject($backData);
            }
        }
    }


    //consumer 订阅 一个队列
    function baseSubscribe($exchangeName,$queueName,$consumerTag = "" ,$noAck = false){
        if(!$exchangeName){
            if($this->_exchange){
                $exchangeName = $this->_exchange;
            }else{
                $this->throwException(511);
            }
        }

        $this->out("Subscribe a  new Consume : queue:$queueName , consumerTag:$consumerTag ,noAck: $noAck , exchangeName : $exchangeName. ");
        if(!$consumerTag){
            $consumerTag = $queueName . time();
        }

        $this->_consumerBindQueueName = $queueName;
        $this->_loopDeadConsumerTag = $consumerTag;

        $self = $this;
        $this->out("set basic consumer callback func ");
        $baseCallback = function($msg,$backQueueObj = null) use($self,$exchangeName,$noAck){
            $this->out( " basic consumer receive msg");
            $self->subscribeCallback($msg,$backQueueObj,$exchangeName,$noAck);
            return false;
        };

        $this->startListenerWait($queueName,$consumerTag,$baseCallback,$noAck);

    }

    function getOs(){
        $os = strtoupper(substr(PHP_OS,0,3));
        return $os;
    }

    //消费者开启 守护 状态
    function startListenerWait($queueName,$consumerTag,$baseCallback,$noAck){
        $this->out(" start Listener Wait... ");

        if($this->getOs() !== "WIN"){
            if($this->supportsPcntlSignals()){
                $fd = fopen($this->_pidPath,"w");
                fwrite($fd,getmypid());
            }
        }

//        var_dump();exit;
        $this->regSignals();

        while (1){
            if($this->_consumerStopLoop){
                $this->cancelRabbitmqConsumer();
                $this->execUserShutdownCallback();
                break;
            }

//            if($this->_signalStopLoop){
//                $this->cancelRabbitmqConsumer();
//                $this->_timeOutCallback;
//                exit("529");
//            }

            $this->_extProvider->basicConsume($queueName,$consumerTag,false,$noAck,false,false,$baseCallback);
            $this->_extProvider->listenerCancel($consumerTag);
        }
    }

    function cancelRabbitmqConsumer(){
        $this->out(" cancel consumer.");
        $this->listenerCancel($this->_loopDeadConsumerTag);
    }


    function listenerCancel($consumerTag){
        $this->_extProvider->listenerCancel($consumerTag);
    }
    //基类就这一个实例化，但是 生产者  消费都 都 在用，且还要区分header
    //此方法，就是每次执行之前，需要 设置的  header 也就是child class name
    function _outInit($flag){
        $header = null;
        if(is_array($flag)){
            foreach ($flag as $k=>$v) {
                $this->out("set flag:".$v);
                $header[$v] = $v;
            }
        }else{
            $this->out("set flag:".$flag);
            $header[$flag] = $flag;
        }

        //默认情况下，把用户自定义的类 类名，当做关键字，绑定到header exchange 上
        $this->_header = array_merge(array("x-match"=>'any'),$header);
        return $this;
    }

    function getClassFinalName($className = ""){
        if(!$className){
            $className = __CLASS__;
        }
        $class = explode("\\",$className);
        return $class[count($class) -1];
    }

    function out($info ,$br = 1){
        $msg = $this->getClassFinalName() . "$$ ".$info;
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
//            Log::notice($msg);
        }

        return true;
    }

    function supportsPcntlSignals(){
        return extension_loaded('pcntl');
    }
    function setIgnoreSendNoQueueReceive(int $flag){
        $this->_ignoreSendNoQueueReceive = $flag;
    }
    //初始化，创建3个默认回调函数
    function regDefaultAllCallback(){
        $this->out("regDefaultAllCallback : ack n-ack server_return_listener");
        $clientAck = function ($backData){
            $this->out("Rabbitmq Server callback Producer ConfirmMode ack ,DeliveryTag=$backData ");
//            $body = $this->_extProvider->getMsgBody($backData);
//            $attr = $this->_extProvider->getMsgAttr($backData);
//            $info = $this->_extProvider->debugMergeInfo($attr);

            $data = $this->_extProvider->getMsgByDeliveryTag($backData);
            if(!$data){
                $this->out("notice: getMsgByDeliveryTag($backData) is null ");
            }
            if($this->_ignoreSendNoQueueReceive){
                if(in_array($data['message_id'],$this->_ignoreSendNoQueueReceivePool)){
                    $this->out(" msgId in _ignoreSendNoQueueReceivePool ");
                    return true;
                }
            }

            $this->out(" destroyDeliveryPool ");
            $this->_extProvider->destroyDeliveryPool($backData);

            $this->userCallbackExec('ack',$data);
            return false;
        };

        $clientNAck = function ($backData){
            $this->out("Rabbitmq Server callback Producer ConfirmMode N-ack ");
            $data = $this->_extProvider->getMsgByDeliveryTag($backData);
            $this->userCallbackExec('nack',$data);
            $this->throwException(506,array(json_encode($data)));
            return false;
        };

//        $clientReturnListener = function ($code,$errMsg,$exchange,$routingKey,$AMQPMessage) use ($clientAck){
//            $this->out("callback return:");
//            $info = "return error info:   code:$code , err_msg:$errMsg , exchange $exchange , routingKey : $routingKey body:".$AMQPMessage->body ."";
//            $this->out($info);
        $clientReturnListener = function ($code, $errMsg,$exchange,$routing_key,$properties,$body) use ($clientAck){
            $data = $this->_extProvider->parseBackDataAttrToUniteArr($properties);
            $this->out("rabbitmq server callback clientReturnListener ". json_encode($data));
//            $body = RabbitmqBase::getBody($AMQPMessage);
//            $attr = RabbitmqBase::getReceiveAttr($AMQPMessage);
//            $info = RabbitmqBase::debugMergeInfo($attr);
//
//            $attr = $this->_factory->getMsgAttrByAMQPBasicProperties($properties);
//            $body = $this->_factory->getMsgBodyByHeader($body,$attr);
//            $info = $this->_factory->debugMergeInfo($attr);


            if($code == 312 ){
                if($this->_ignoreSendNoQueueReceive){
                    $this->_ignoreSendNoQueueReceivePool[] = $data['message_id'];
                    $this->out(" ignoreSendNoQueueReceive ");
                    return false;
                }
                //这里实际上是一个兼容，延迟插件不支持mandatory flag

//                $attr = RabbitmqBase::getReceiveAttr($AMQPMessage);
//                if(isset($data['application_headers']) && $data['application_headers']){
                if(isset($data['headers']) && $data['headers']){
//                    foreach ($data['application_headers'] as $k=>$v) {
                    foreach ($data['headers'] as $k=>$v) {
                        if($k  == 'x-delay'){//除了正常延迟消息外，还有重试的延迟消息
                            $this->out(" delayed plugin no ack ,but return notice ");
                            if($k == 'x-retry-count'){
                                $this->out(" this msg is retry . ");
                            }
                            return false;
                        }
                    }
                }
            }

//            $this->userCallbackExec('rabbitmqReturnErr',$data);
            $this->throwException(507,array(json_encode($data)));
        };


        $this->setRabbitmqAckCallback($clientAck,$clientNAck);
        $this->setRabbitmqErrCallback($clientReturnListener);

    }
}