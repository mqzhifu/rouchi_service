<?php
namespace Jy\Common\MsgQueue\MsgQueue;
use Jy\Common\MsgQueue\Facades\MsgQueue;


abstract class MessageQueue{
    //一但设置了确认模式或者事务模式就不能再变更，这两种模式是互斥的
    private $_debug = 0;//调试模式
    private $_topicName = "";//分类名
    private $_mode = 0;//如下
    private $_modeDesc = array(0=>'普通模式',1=>'确认模式',2=>'事务模式');
    private $_childClassName = "";//子类继承标识
    private $_retry = null;//重试机制
    //每个consumer最大同时可处理消息数
    private $_consumerQos = 1;
    //consumer 类型 ,描述如下
    private $_consumerSubscribeType = 0;
    //consumer 类型描述
    private $_consumerSubscribeTypeDesc = array(1=>'直接bean类开启consumer,监听一个bean',2=>'同时监听多个bean',3=>'同时监听多个bean,使用工具类自动生成');

    //业务类的名称(ID标识)，主要用于 绑定header exchange ，做路由分发
    private $_consumerName = "";//消费者名称，也可以理解为消费者ID
    //保存用户 监听的bean 实例化的 类
    private $_userBeanClassCollection = [];
    //以组 模式 开启监听，设置的 重试机制
//    private $_groupSubscribeRetryTime = [];
    private $_customBindBean = [];
    protected $_listenManyBeanType = 1;//1用户自己创建类并继承操作 2由工具类帮助用户完成注册过程

    function __construct($provide = "rabbitmq",$conf = null,$debugFlag = 0,$extType = 2,$mode = 1){
        $this->_debug = $debugFlag;

        $info = null;
        if($conf){
            $info = json_encode($conf);
        }
        $this->out(" construct provide:$provide debugFlag:$debugFlag extType:$extType conf:$info");

        //子类名，即：传输协议标识ID
        $this->setClassFlag();
        MsgQueue::getInstance($provide,$conf,$debugFlag,$extType);
        MsgQueue::getInstance()->regUserCallback($this->_childClassName,array($this,"serverCallback"));

        if($mode){
            //默认开启确认模式
            $this->setMode($mode);
        }
//        MsgQueue::_outInit($this->_flag);

    }
    //调试模式,0:关闭，1：只输出到屏幕 2：只记日志 3：输出到屏幕并记日志
    //注：开启 日志模式，记得引入log包
    function setDebug($flag){
        $this->_debug = $flag;
        return MsgQueue::setDebug($flag);
    }

    function throwException($code){
        MsgQueue::getInstance()->throwException($code);
    }

    //生产者，设定当前脚本和rabbitmq Server 交互模式  1普通 2确认模式 3事务模式  注：2 跟 3 互斥
    //默认为普通模式，加速性能
    //设置 模式
    function setMode(int $mode){
        if(!in_array($mode,array_flip($this->_modeDesc))){
            MsgQueue::getInstance()->throwException(504);
        }

        if($this->_mode && $this->_mode != $mode){
            MsgQueue::getInstance()->throwException(505);
        }

        if( $this->_mode == $mode){
            return true;
        }

        if($mode == 1){
            MsgQueue::getInstance()->confirmSelectMode();
        }

        $this->_mode = $mode;
    }

    //开启一个事务
    function  transactionStart(){
        $this->setMode(2);
        return MsgQueue::getInstance()->txSelect();
    }
    //提交一个事务
    function  transactionCommit(){
        $this->setMode(2);
        return MsgQueue::getInstance()->txCommit();
    }
    //回滚一个
    function  transactionRollback(){
        $this->setMode(2);
        return MsgQueue::getInstance()->txRollback();
    }

    function getTopicName(){
        return $this->_topicName;
    }

    function setTopicName($name){
        $this->_topicName = $name;
        MsgQueue::getInstance()->setTopicName($name);
    }
    //生产者可以DIY，消费者也可以DIY，以最后设置的为准。
    //也可以不设置，父类里有默认值
    function setRetryTime(array $retry){
        $this->_retry = $retry;
    }
    //获取类重试机制
    function getRetryTime(){
        return $this->_retry;
    }
    //发送者，发送消息体，最大值
    function setMessageMaxLength(int $num){
        MsgQueue::getInstance()->setMessageMaxLength($num);
    }

    function setClassFlag($flag = false){
        if($flag){
            $this->_childClassName = $flag;
            $this->out(" setClassFlag by arguments :".$flag);
        }else{
            $this->_childClassName = get_called_class();
            $this->out(" setClassFlag by get_called_class:".get_called_class());
        }
    }

    //==============================以上是公共方法

    //===============================以下是生产者相关

    //生产者-注册ACK回调
    function regUserCallbackAck($callback){
        $this->out("regUserCallbackAck");
        MsgQueue::getInstance()->regUserFunc($this->_childClassName,"ack",$callback);
    }
    //生产者-注册N-ACK回调
    function regUserCallBackNAck($callback){
        $this->out("regUserCallBackNAck");
        MsgQueue::getInstance()->regUserFunc($this->_childClassName,"nack",$callback);
    }
    //rabbitmq server 有任何回调，会先调base类，base再调用此方法，统一入口
    function serverCallback($type,$data){
        $this->out("serverCallback type:".$type);
        if($type == 'ack'){
            if(!isset($data['headers']) ||  !$data['headers']){
//                if(isset($attr['application_headers']) &&  $attr['application_headers']){
//                foreach ($attr['application_headers'] as $k=>$v) {
                $this->throwException(520);
            }

            $body = $this->transcodingMsgBody($data['body'],2,$data['content_type']);
            $func = MsgQueue::getInstance()->getRegUserFunc($this->_childClassName,'ack');
            if($func){
                call_user_func($func,$body);
            }else{
                $this->out(" user not register callback func.");
            }

        }elseif($type == "nack"){
            $this->out("nack");
        }elseif($type == 'rabbitmqReturnErr' ){
            $this->out("rabbitmqReturnErr");
        }elseif($type == 'serverBackConsumer' || $type == 'serverBackConsumerRetry'){
            $body = $this->transcodingMsgBody($data['body'],2,$data['content_type']);
            $func = MsgQueue::getInstance()->getRegUserFunc($this->_childClassName,'serverBackConsumer');
            call_user_func($func,$body);
        }
        else{
            $this->out("serverCallback type err.");
        }
    }

    /*
     *  发送一条消息给路由器
        $msgBody:发送消息体，可为json object string
        $arguments:对消息体的一些属性约束
        $header:主要是发送延迟队列时，使用
    */
    function send($arguments = null,$header = null,$isRetry = 0){
//        if(!$msgBody)
//            $this->throwException(500);

//        if(is_bool($msgBody))
//            $this->throwException(501);

        $msgBody = $this;
        $msgId = $this->createUniqueMsgId();
        $arguments = $this->setCommonArguments($arguments,$msgId,$msgBody);
        $msg = $this->transcodingMsgBody($msgBody,1);
        $msgBody = $msg['msg'];
        $arguments['content_type'] = $msg['content_type'];
//        $rabbitHeader = $this->preProcessHeader($header);

        MsgQueue::getInstance()->_outInit($this->_childClassName)->publish($msgBody,$this->_topicName,null,$header,$arguments);
//        $this->publish($msgBody,$this->_exchange,"",$rabbitHeader,$arguments);
        if($this->_mode == 1){
            MsgQueue::getInstance()->baseWait();
        }

        return $msgId;
    }
    //生成消息唯一ID
    function createUniqueMsgId(){
        return uniqid(time());
    }
    //设置消费者ID
    function setConsumerName(string $name){
        $this->_consumerName = $name;
    }
    //发送一条延迟消息
    function sendDelay(int $msTime ){
        $arr = array('x-delay'=>$msTime);
        $this->send(null,$arr);
    }
    //一个consumer最多可同时接收rabbitmq 消费数
    function setReceivedServerMsgMaxNumByOneTime(int $num){
        $this->out("setReceivedServerMsgMaxNumByOneTime :  $num");
        $this->_consumerQos = $num;
        return MsgQueue::getInstance()->setReceivedServerMsgMaxNumByOneTime($num);
    }
    //进程意外退出，如：超时，会执行此函数。类似析构函数
    //但是：如果shell 里直接kill pid  ,或 ctrl+c ，信号可以捕捉到，但不会执行此方法
    //如果用户态 可执行 quitConsumerDemon，就没必要执行此方法了。
    function regConsumerShutdownCallback($func){
        MsgQueue::getInstance()->regUserCallbackShutdown($func);
    }
    //设置一个消费者守护进程，同时接收server最大消息数
    function setUserCallbackFuncExecTimeout(int $time){
        MsgQueue::getInstance()->setUserCallbackFuncExecTimeout($time);
    }
    //消费者开启守护模式，即是死循环，如果有特殊情况想退出，可以使用此方法
    //调用此函数后，原<执行代码空间>，后面的代码即可执行
    function quitConsumerDemon(){
        MsgQueue::getInstance()->quitConsumerDemon();
    }
    //给单元测试工具类使用 - 忽略
    function getProvider(){
        return MsgQueue::getInstance();
    }

    function setIgnoreSendNoQueueReceive(int $flag){
        MsgQueue::getInstance()->setIgnoreSendNoQueueReceive($flag);
    }
    //type:1 encode 2 decode
    function transcodingMsgBody($msgBody,$type = 1,$contentType = ""){
        $rs = array("msg"=> $msgBody,"content_type"=>"");
        if($type == 1){
            if(is_object($msgBody)){
                $rs['msg'] = serialize($msgBody);
                $rs['content_type'] = "application/serialize";
            }
            elseif(is_array($msgBody) ){
                $rs['msg'] = json_encode($msgBody);
                $rs['content_type'] = "application/serialize";
            }
        }elseif($type == 2){
            if($contentType == "application/serialize"){
                $rs = unserialize($msgBody);
            }elseif($contentType == "application/json"){
                $rs = json_encode($msgBody,true);
            }
        }

        return $rs;
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


    //========================================以上是生产者相关，以下是消费者相关

    private $_queueMessageDurable = true;//队列-消息是否持久化
    //设置消息是否持久化
    function setQueueMessageDurable(bool $flag){
        $this->_queueMessageDurable = $flag;
    }



    //消费者 开启 订阅 监听
    //消费者 - 想监听 - 多个事件 的时候，需要 初始化 队列 信息
    function subscribe($consumerTag ,$noAck = false,$listenManyBeanType = 1){
        if(!$consumerTag){
            $this->throwException(508);
        }

        $queueName = $this->_flag ."_". $consumerTag;

        if(!MsgQueue::getInstance()->queueExist($queueName)){
            MsgQueue::getInstance()->setQueue($queueName,null,$this->_queueMessageDurable);
        }

        if(!$this->_customBindBean){
            MsgQueue::getInstance()->throwException(522);
        }

        $header = array("x-match"=>'any',$queueName=>$queueName);
        foreach ($this->_customBindBean as $k=>$v) {
            $header[$v] = $v;
        }

        $topicName = $this->_topicName;
        if(!$topicName){
            $topicName = MsgQueue::getInstance()->getTopicName();
        }

        MsgQueue::getInstance()->bindQueue($queueName,$topicName,null,$header);

//        $this->out(" rabbitmqBean subscribe start: queueName:$queueName consumerTag:$consumerTag noAck:$noAck");
        $consumerCallback = function($recall) use ($noAck){
            if (!$noAck) {
                return $this->mappingBeanCallbackSwitch($recall);
            }
        };

//        $this->bindQueue($queueName,$this->_exchange,null,$this->_header);
//        if(!$consumerTag){
//            $consumerTag = $queueName .__CLASS__;
//        }

        if(!$this->_consumerQos){
            $this->setReceivedServerMsgMaxNumByOneTime($this->_consumerQos);
        }

        $this->_consumerSubscribeType = 2;

        MsgQueue::getInstance()->_outInit($this->_childClassName)->baseSubscribe($this->_exchange,$queueName, $consumerTag, $consumerCallback,$noAck);
//        MsgQueue::getInstance()->startListenerWait();
    }



//    function groupSubscribe($userCallback , $consumerTag , $durable = true){
//
//        return MsgQueue::getInstance()->_outInit($this->_flag)->groupSubscribe($userCallback,$consumerTag ,false ,$durable ,false,$this->_retry);
//    }

//    function getGroupSubscribeRetryTime(){
//        return $this->_groupSubscribeRetryTime;
//    }
    //快速开启 一个consumer订阅一个队列
    function groupSubscribe($userCallback,$consumerTag ,$autoDel = false,$durable = true,$noAck =false,$retry = []){
        $this->out("start groupSubscribe autoDel:$autoDel , durable:$durable , noAck:$noAck ");
        if(!$consumerTag){
            MsgQueue::getInstance()->throwException(508);
        }

        $queueName = $this->_childClassName . "_".$consumerTag;
        $this->out(" queueName:$queueName ");

        if(!MsgQueue::getInstance()->queueExist($queueName)){
            MsgQueue::getInstance()->setQueue($queueName,null,$durable,$autoDel);
        }

        $this->setReceivedServerMsgMaxNumByOneTime($this->_consumerQos);
        $this->_consumerSubscribeType = 1;
//        $this->_groupSubscribeRetryTime = $retry;

        if($retry){
            $this->out(" set retryTimes By arguments  ");
            MsgQueue::getInstance()->setRetryTime($retry);
        }else{
            if($this->_retry){
                $this->out(" set retryTimes By member variable ");
                MsgQueue::getInstance()->setRetryTime($this->_retry);
            }else{
                $this->out(" no set retryTimes times.");
            }
        }

        MsgQueue::getInstance()->regUserCallback($queueName,array($this,"serverCallback"));

        $header = array($queueName,$this->_childClassName);
        MsgQueue::getInstance()->_outInit($header);

        MsgQueue::getInstance()->regUserFunc($this->_childClassName,"serverBackConsumer",$userCallback);
//        MsgQueue::getInstance()->regUserFunc($this->_childClassName,"serverBackConsumerRetry",$userCallback);

        MsgQueue::getInstance()->bindQueue($queueName);
        MsgQueue::getInstance()->baseSubscribe(null,$queueName,$consumerTag,null,$noAck);
//        MsgQueue::getInstance()->startListenerWait();
    }
    //一个consumer监听多个bean
    //给消费，注册 监听 多个事件
    function setListenerBean($beanObj,$callback){
//        return MsgQueue::_outInit($this->_flag)->setListenerBean($beanName,$callback);
        if(!is_object($beanObj)){
            $this->throwException(514);
        }

        $className = get_class($beanObj);


        $info = " no info";
        if(is_array($callback)){
            $callbackClassName = get_class($callback[0]);
            $callbackClassMethod = $callback[1];

            $info = " $callbackClassName -> $callbackClassMethod ()";
        }


        $this->_userBeanClassCollection[] = $beanObj;

        $this->out("setListenerBean className:$className  callbackInfo:" .$info );

        $this->_header[$name] = $className;
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
            return false;
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

    function setSubscribeBean(array $beans , array $callbackFunc = null){
        foreach ($beans as $k=>$bean) {
            if(!is_object($bean)){
                MsgQueue::getInstance()->throwException(521);
            }
            $beanClassName =get_class($bean);

            if($this->_listenManyBeanType == 1){
                $tmpClassName  =  explode('\\',$beanClassName);
                $realClassName = $tmpClassName[count($tmpClassName) - 1];


                $relClass = new \ReflectionClass(get_called_class());
                $methods = $relClass->getMethods();
                $f = 0;
                foreach ($methods as $k=>$v) {
                    if($v->getName() == "handle" .$realClassName ){
                        $f = 1;
                        break;
                    }
                }
                if(!$f){
                    MsgQueue::throwException(523,array("handle".$realClassName));
                }


                $this->setListenerBean($bean,array($this,"handle".$realClassName));
            }else{
                $this->setListenerBean($bean,$callbackFunc[$k]);
            }
            $this->_customBindBean[] =  $beanClassName;
        }
    }

    function getClassFinalName($className = ""){
        if(!$className){
            $className = __CLASS__;
        }
        $class = explode("\\",$className);
        return $class[count($class) -1];
    }
    function getOs(){
        $os = strtoupper(substr(PHP_OS,0,3));
        return $os;
    }

    function out($info ,$br = 1){
        $msg = $this->getClassFinalName(__CLASS__) . "$$ ".$info;
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


//    //创建一个队列
//    function createQueue($queueName,$arguments= null,$durable= null,$autoDel= null){
//        if($this->queueExist($queueName)){
//            return true;
//        }else{
//            $this->setQueue($queueName,$arguments,$durable,$autoDel);
//        }
//    }
//
//    //绑定一个队列
//    function setBindQueue($queueName,$exchange,$routingKey,$header){
//        $this->bindQueue($queueName,$exchange,$routingKey,$header);
//        $this->baseWait();
//    }
//    function publishToBase($msgBody ,$exchangeName,$routingKey = '',$header = null,$arguments = null){
//        $this->publish($msgBody ,$exchangeName,$routingKey ,$header,$arguments);
//    }
//    //用户注册N-ACK回调
//    function callbackUser($callback,$argc){
//        if($callback){
//            return call_user_func($callback,$argc);
//        }
//    }
//
//    function setListenerBean($beanName,$callback){
//
//    }



}