<?php
namespace Jy\Common\MsgQueue\MsgQueue;

class RabbitmqBean extends \Jy\Common\MsgQueue\MsgQueue\RabbitmqBase{
    private $_exchange = "many.header.delay";
    private $_header = null;
    //一但设置了确认模式或者事务模式就不能再变更，这两种模式是互斥的
    private $_mode = 0;
    private $_modeDesc = array(0=>'普通模式',1=>'确认模式',2=>'事务模式');
    //业务类的名称，主要用于binding header exchange
    private $_childClassName = "";
    //每个consumer最大同时可处理消息数
    private $_consumerQos = 0;
    private $_defaultConsumerQos = 1;
    //生产者，注册 ACK 回调函数-集合
    private $_userBeanAckCallback = array();
    //生产者，注册 N-ACK 回调函数-集合
    private $_userBeanNAckCallback = array();
    private $_userBeanClassCollection = [];
    function __construct($conf ,$debug = 0  ){
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

    //初始化
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

    function getTopicName(){
       return $this->_exchange;
    }


    /*
     *  发送一条消息给路由器
        $msgBody:发送消息体，可为json object string
        $arguments:对消息体的一些属性约束
        $header:主要是发送延迟队列时，使用
    */
    function send($msgBody,$arguments = null,$header = null,$isRetry = 0){
        if(!$msgBody)
            $this->throwException(500);


        if(is_bool($msgBody))
            $this->throwException(501);

        $msgId = $this->createUniqueMsgId();
        $arguments = $this->setCommonArguments($arguments,$msgId,$msgBody);
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

        $rabbitHeader = $this->preProcessHeader($header);

        $this->publish($msgBody,$this->_exchange,"",$rabbitHeader,$arguments);
        if($this->_mode == 1){
            $this->waitReturnListener();
        }

        return $msgId;
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
    //设置 默认 参数值
    function setCommonArguments($arguments,$msgId){
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
        }elseif($this->_mode == 2){//事务模式
            $arguments['type'] = "tx";
        }else{
            $arguments['type'] = "normal";
        }

        return $arguments;
    }
    //设置 模式
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
    //用户注册ACK回调
    function regUserCallbackAck($callback){
        $this->_userBeanAckCallback[$this->_childClassName] = $callback;
    }
    function regUserCallBackNAck($callback){
        $this->_userBeanNAckCallback[$this->_childClassName] = $callback;
    }
    //用户注册N-ACK回调
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
//                            $recall = array("AMQPMessage"=>$AMQPMessage,'body'=>$body,'attr'=>$attr);
                            $this->callbackUser($v2,$body);
                            break;
                        }
                    }
                }
            }else{
                $this->throwException(520);
            }

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


//            $recall = array("AMQPMessage"=>$AMQPMessage,'body'=>$body,'attr'=>$attr);
            $this->callbackUser($this->_callbackUserNAck,$body);

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
    //开启 - 消费者 - 监听
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
//    //消费者 - 想监听 - 多个事件 的时候，需要 初始化 队列 信息
//    function consumerInitQueue($queueName,$arguments= null,$durable= null,$autoDel= null,$bindingBeans){
//        if(!$this->queueExist($queueName)){
//            $this->setQueue($queueName,$arguments,$durable,$autoDel);
//        }
//
//        $header = array("x-match"=>'any');
//        foreach ($bindingBeans as $k=>$v) {
//            $header[] = array($v=>$v);
//        }
//
//        $this->bindQueue($queueName,$this->_exchange,null,$header);
//    }

    //创建一个队列
    function createQueue($queueName,$arguments= null,$durable= null,$autoDel= null){
        if($this->queueExist($queueName)){
            return true;
        }else{
            $this->setQueue($queueName,$arguments,$durable,$autoDel);
        }
    }
    //一个consumer最多可同时接收rabbitmq 消费数
    function setReceivedServerMsgMaxNumByOneTime(int $num){
        $this->out("setReceivedServerMsgMaxNumByOneTime :  $num");
        $this->_consumerQos = $num;
        $this->getChannel()->basic_qos(null,$num,null);
    }
    //绑定一个队列
    function setBindQueue($queueName,$exchange,$routingKey,$header){
        $this->bindQueue($queueName,$exchange,$routingKey,$header);
        $this->waitReturnListener();
    }
    //给消费，注册 监听 多个事件
    function setListenerBean($beanName,$callback){
        if(!is_object($beanName)){
            $this->throwException(514);
        }

        $name = get_class($beanName);


        $info = " no info";
        if(is_array($callback)){
            $callbackClassName = get_class($callback[0]);
            $callbackClassMethod = $callback[1];

            $info = " $callbackClassName -> $callbackClassMethod ()";
        }


        $this->_userBeanClassCollection[] = $beanName;

        $this->out("setListenerBean className:$name  callbackInfo:" .$info );

        $this->_header[$name] = $name;
        $this->_bean[$name][] = $callback;
    }
    //rabbitmq push consumer 时，将消息分发给 不同的bean
    function mappingBeanCallbackSwitch($body){
        $this->out("im mappingBeanCallbackSwitch func.");
//        $header = $recall['attr']['header'];
        $className = get_class($body);

        if(!isset($this->_bean[$className]) || ! $this->_bean[$className]){
            $this->throwException(513);
        }
//        $callback = 0;
//        foreach ($this->_bean as $k=>$v) {
//            if(isset($header[$k]) && $header[$k]){
//                $callback  = $v;
//                break;
//            }
//        }

//        if(!$callback){
//            $this->throwException(513);
//        }
        $callback = $this->_bean[$className];
        foreach ($callback as $k=>$v) {
//            var_dump($v);
            return call_user_func($v,$body);
        }

    }

    function getBeanRetry($bean){
        $className = get_class($bean);
        if(!$this->_userBeanClassCollection ){
            $this->throwException(531);
        }

        foreach ($this->_userBeanClassCollection as $k=>$v) {
            $userBeanClassName = get_class($v);
            if($userBeanClassName == $className){
                $retryTime = $v->getRetryTime();
                return $retryTime;
            }
        }

        return false;

    }

    //消费者 开启 订阅 监听
    function subscribe($queueName, $consumerTag = "",$noAck = false){
        $this->out(" rabbitmqBean subscribe start: queueName:$queueName consumerTag:$consumerTag noAck:$noAck");
        $consumerCallback = function($recall) use ($noAck){
            if (!$noAck) {
                return $this->mappingBeanCallbackSwitch($recall);
            }
        };

//        $this->bindQueue($queueName,$this->_exchange,null,$this->_header);
        if(!$consumerTag){
            $consumerTag = $queueName .__CLASS__;
        }

        if(!$this->_consumerQos){
            $this->setBasicQos($this->_defaultConsumerQos);
        }

        $this->baseSubscribe($this->_exchange,$queueName, $consumerTag, $consumerCallback,$noAck);
        $this->startListenerWait();
    }

    function waitReturnListener(){
        $this->getChannel()->wait_for_pending_acks_returns(100);
    }
}