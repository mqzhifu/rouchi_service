<?php
namespace Jy\Common\MsgQueue\MsgQueue;

class RabbitmqBean extends \Jy\Common\MsgQueue\MsgQueue\RabbitmqBase{
    private $_exchange = "many.header.delay";
    private $_deadLetterExchange = "test.header.delay.dead_ex";
    //消息分为：header+body+arguments   . arguments是约束消息，header 是补充约束(还可以自定义)
    private $_header = null;
//    private $_callbackUserAck = null;
//    private $_callbackUserNAck = null;
    private $_callbackUserConsumer = null;
    private $_mode = 0;//0为普通模式 1为确认模式 2为事务模式，一但设置了确认模式或者事务模式就不能再变更，这两种模式是互斥的
    private $_modeDesc = array(0=>'普通模式',1=>'确认模式',2=>'事务模式');
    //业务类的名称，主要用于binding header exchange
    private $_childClassName = "";
    //每个consumer最大同时可处理消息数
    private $_consumerQos = 0;
    private $_defaultConsumerQos = 5;


    private $_userBeanAckCallback = array();
    private $_userBeanNAckCallback = array();

    //以下暂不用
    private $_redisMsgStatusManager = 0;//帮业务人员，开启消息一致性
//    private $_roleDesc = array(1=>'product',2=>'consumer');
//    private $_role = 0;

    function __construct($conf ,$debug  ){
        if(!$conf){
            $this->throwException(515);
        }
        $this->checkConfigFormat($conf);

        if($debug){
            parent::setDebug($debug);
        }


        parent::__construct($conf);
        $this->init();

    }

    function init(){
        $this->initBase();
        $this->regDefaultAllCallback();
    }
    //基类就这一个实例化，但是 生产者  消费都 都 在用，且还要区分header
    //此方法，就是每次执行之前，需要 设置的  header 也就是child class name
    function _outInit($flag){
        $this->setClassFlag($flag);
        $this->setDefaultHeader();

        return $this;
    }

    function publishToBase($msgBody ,$exchangeName,$routingKey = '',$header = null,$arguments = null){
        $this->publish($msgBody ,$exchangeName,$routingKey ,$header,$arguments);
    }

    function setClassFlag($flag = false){
        if($flag){
            $this->_childClassName = $flag;
        }else{
            $this->_childClassName = get_called_class();
        }
    }
    //默认情况下，把用户自定义的类 类名，当做关键字，绑定到header exchange 上
    function setDefaultHeader(){
        $this->_header = array($this->_childClassName=>$this->_childClassName,"x-match"=>'any');
    }
    //事务开启
    function  transactionStart(){
        $this->getChannel()->tx_select();
    }
    //事务提交
    function  transactionCommit(){
        $this->getChannel()->tx_commit();
    }
    //事务回滚
    function  transactionRollback(){
        $this->getChannel()->tx_rollback();
    }
    //setExchangeName
    function setTopicName($name){
        $this->_exchange = $name;
    }
    //发送一条消息给路由器
    //$msgBody:发送消息体，可为json object string
    //$arguments:对消息体的一些属性约束
    //$header:主要是发送延迟队列时，使用
    function send($msgBody,$arguments = null,$header = null){
        if(!$msgBody)
            $this->throwException(500);


        if(is_bool($msgBody))
            $this->throwException(501);

        //校验 延迟队列 的时间值
        if(isset($arguments['x-delay']) && $arguments['x-delay']){
            $delayTime = (int)$arguments['x-delay'];
            if(!$delayTime ){
                $this->throwException(517);
            }

            if($delayTime < 1000){
                $this->throwException(518);
            }

            $day = 7 * 24 * 60 *60 * 1000;
            if( $arguments['x-delay'] > $day){
                $this->throwException(519);
            }
        }

        $msgId = $this->createUniqueMsgId();
        $arguments = $this->setCommonHeader($arguments,$msgId,$msgBody);
        //主要是给，延迟队列
        $rabbitHeader = $this->_header;
        if($header ){
            $rabbitHeader = array_merge($rabbitHeader,$header);
        }


        $publishServerRs = $this->publish($msgBody,$this->_exchange,"",$rabbitHeader,$arguments);
        if($this->_mode == 1){
            $this->waitReturnListener();
        }

        return $msgId;
    }

    function setCommonHeader($arguments,$msgId,$msgBody){
        if($arguments){
            if(isset($arguments['message_id']) && $arguments['message_id']){
                $this->throwException(502);
            }

            if(isset($arguments['type']) && $arguments['type']){
                $this->throwException(503);
            }

            $arguments['message_id'] = $msgId;
        }else{
            $arguments = array( "message_id"=>$msgId);
        }

        if(isset($arguments['timestamp']) && $arguments['timestamp']){
            $this->throwException(512);
        }

        $arguments['timestamp'] = time();
        $arguments['delivery_mode'] = 2;


        if($this->_mode == 1){//确认模式
            $arguments['type'] = "confirm";
//            if($this->_redisMsgStatusManager){
//                RedisMsgStatusAck::redisSetMsgStatusAck($msgId,RedisMsgStatusAck::$_statusProductWait);
//            }
        }elseif($this->_mode == 2){//事务模式
            $arguments['type'] = "tx";
        }else{
            $arguments['type'] = "normal";
        }

        if(is_object($msgBody)){
            if(!$arguments){
                $arguments = ['content_type'=>'application/serialize'];
            }else{
                $arguments['content_type'] = 'application/serialize';
            }
            $msgBody = serialize($msgBody);
        }
        elseif(is_array($msgBody) ){
            if(!$arguments){
                $arguments = ['content_type'=>'application/json'];
            }else{
                $arguments['content_type'] = 'application/json';
            }
            $msgBody = json_encode($msgBody);
        }

        return $arguments;
    }


    function setMode(int $mode){
        if(!in_array($mode,array_flip($this->_modeDesc))){
            $this->throwException(504);
        }

        if($this->_mode && $this->_mode != $mode){
            $this->throwException(505);
        }

        if( $this->_mode == $mode){
            return true;
        }

        if($mode == 1){
            $this->confirmSelectMode();
        }

        $this->_mode = $mode;
    }


    function regUserCallbackAck($callback){
        $this->_userBeanAckCallback[$this->_childClassName] = $callback;
    }
    function regUserCallBackNAck($callback){
        $this->_userBeanNAckCallback[$this->_childClassName] = $callback;
    }

    function callbackUser($callback,$argc){
        if($callback){
            return call_user_func($callback,$argc);
        }
    }
    //初始化，创建3个默认回调函数
    function regDefaultAllCallback(){
        $this->out("regDefaultAllCallback : ack n-ack return_listener");
        $clientAck = function ($AMQPMessage){
            $this->out("callback ack info:",0);
            $body = RabbitmqBase::getBody($AMQPMessage);
            $attr = RabbitmqBase::getReceiveAttr($AMQPMessage);
            $info = RabbitmqBase::debugMergeInfo($attr);


            if(isset($attr['header']) &&  $attr['header']){
                foreach ($attr['header'] as $k=>$v) {
                    foreach ($this->_userBeanAckCallback as $k2=>$v2) {
                        if($k == $k2){
                            $recall = array("AMQPMessage"=>$AMQPMessage,'body'=>$body,'attr'=>$attr);
                            $this->callbackUser($v2,$recall);
                            break;
                        }
                    }
                }
            }else{
                $this->throwException(520);
            }

//            if($this->_mode == 1){
//                if($this->_redisMsgStatusManager){
//                    $msgStatus = RedisMsgStatusAck::redisGetMsgStatusAck($attr['message_id']);
//                    RedisMsgStatusAck::redisSetMsgStatusAck($attr['message_id'],RedisMsgStatusAck::$_statusProductOk);
//                    $info = " sendStatus:".$msgStatus['status'] . " sendTime:".date("Y-m-d H:i:s",$msgStatus['time']);
//                    $this->out($info);
//                }
//            }

            if(is_array($body)){
                $body = json_encode($body);
            }elseif(is_object($body)){
                $body = serialize($body);
            }

            $this->out(" body : ".($body) . " , $info");
            return true;
        };

        $clientNAck = function ($AMQPMessage){
            $this->out("callback N-ack info:",0);
            $body = RabbitmqBase::getBody($AMQPMessage);
            $attr = RabbitmqBase::getReceiveAttr($AMQPMessage);
            $info = RabbitmqBase::debugMergeInfo($attr);


            $recall = array("AMQPMessage"=>$AMQPMessage,'body'=>$body,'attr'=>$attr);
            $this->callbackUser($this->_callbackUserNAck,$recall);

            $this->out(" body : $body ".json_encode($body) . " , $info");
            $this->throwException(506,array($info));
            return true;
        };

        $clientReturnListener = function ($code,$errMsg,$exchange,$routingKey,$AMQPMessage) use ($clientAck){
            $this->out("callback return:");
            if($code == 312 ){
                //这里实际上是一个兼容，延迟插件不支持mandatory flag
                $attr = RabbitmqBase::getReceiveAttr($AMQPMessage);
                if(isset($attr['header']) && $attr['header']){
                    foreach ($attr['header'] as $k=>$v) {
                        if($k  == 'x-delay'){
                            $this->out(" delayed plugin compatible");
//                            $clientAck($AMQPMessage);
                            return true;
                        }
                    }
                }
            }
            $info = "return error info:   code:$code , err_msg:$errMsg , exchange $exchange , routingKey : $routingKey body:".$AMQPMessage->body ."";
            $this->out($info);
            $this->throwException(507,array($info));
        };

        $this->getChannel()->set_return_listener($clientReturnListener);
        $this->getChannel()->set_nack_handler($clientNAck);
        $this->getChannel()->set_ack_handler($clientAck);
    }

    function groupSubscribe($userCallback,$consumerTag = "",$autoDel = false,$durable = true,$noAck =false){
        $this->out("start groupSubscribe consumerTag:$consumerTag");
        if(!$consumerTag){
            $this->throwException(508);
        }
        $queueName = $this->_childClassName . "_".$consumerTag;
        if(!$this->queueExist($queueName)){
            $this->createQueue($queueName,null,$durable,$autoDel);
        }else{
            $this->out(" queue exist :".$queueName);
        }

        if(!$this->_consumerQos){
            $this->setBasicQos($this->_defaultConsumerQos);
        }

        $this->setBindQueue($queueName,$this->_exchange,null,$this->_header);
        $this->baseSubscribe($this->_exchange,$queueName,$consumerTag,$userCallback,$noAck);
        $this->startListenerWait();
    }

    //consumer  =====================================================

    function setHeader(){

    }

    function setQueueName(){

    }

    function createQueue($queueName,$arguments= null,$durable= null,$autoDel= null){
        if($this->queueExist($queueName)){
            return true;
        }else{
            $this->setQueue($queueName,$arguments,$durable,$autoDel);
        }

    }

    function createTopic(){

    }

    function setBasicQos(int $num){
        $this->out("setBasicQos $num");
        $this->_consumerQos = $num;
        $this->getChannel()->basic_qos(null,$num,null);
    }

    function setBindQueue($queueName,$exchange,$routingKey,$header){
        $this->bindQueue($queueName,$exchange,$routingKey,$header);
        $this->waitReturnListener();
    }

    function setListenerBean($beanName,$callback){
        if(!is_object($beanName)){
            $this->throwException(514);
        }
//        $finalBeanName = substr($beanName,0,strlen($beanName) - 4);
//        $this->_header[$finalBeanName] = $finalBeanName;
//        $this->_bean[$finalBeanName][] = $callback;

        $name = get_class($beanName);

        $this->_header[$name] = $name;
        $this->_bean[$name][] = $callback;
    }

    function mappingBeanCallbackSwitch($recall){
        $this->out("im mappingBeanCallbackSwitch func.");
        $header = $recall['attr']['header'];
        $callback = 0;
        foreach ($this->_bean as $k=>$v) {
//            var_dump($header);
//            var_dump($v);
//            $productName  = substr($k,0,strlen($k)-4);
//            if(isset($header[$productName]) && $header[$productName]){
            if(isset($header[$k]) && $header[$k]){
                $callback  = $v;
                break;
            }
        }

        if(!$callback){
            $this->throwException(513);
        }

        foreach ($callback as $k=>$v) {
            call_user_func($v,$recall);
        }

//        return call_user_func($callback,$recall);
    }

    function subscribe($queueName, $consumerTag = "",$noAck = false){
        $consumerCallback = function($recall) use ($noAck){
            if (!$noAck) {
                return $this->mappingBeanCallbackSwitch($recall);
            }
        };

        $this->bindQueue($queueName,$this->_exchange,null,$this->_header);
        if(!$consumerTag){
            $consumerTag = $queueName .__CLASS__;
        }


        if(!$this->_consumerQos){
            $this->setBasicQos($this->_defaultConsumerQos);
        }

        $this->baseSubscribe($this->_exchange,$queueName, $consumerTag, $consumerCallback,$noAck);

        $this->startListenerWait();
    }

//    function waitAckReturnListener(){
//        $this->waitAck(100);
//        $this->waitReturnListener(100);
//    }

//    function waitAck(){
//        $this->getChannel()->wait_for_pending_acks(100);
//    }

    function waitReturnListener(){
        $this->getChannel()->wait_for_pending_acks_returns(100);
    }
}