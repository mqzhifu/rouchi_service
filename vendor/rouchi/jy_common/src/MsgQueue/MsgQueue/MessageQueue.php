<?php
namespace Jy\Common\MsgQueue\MsgQueue;
use Jy\Common\MsgQueue\Facades\MsgQueue;


abstract class MessageQueue{
    private $_flag = "";//子类标识ID

    private $_consumerName = "";//消费者名称，也可以理解为消费者ID
    private $_queueMessageDurable = true;//队列-消息是否持久化
    private $_customBindBean = [];
    private $_retry = null;//重试机制
    private $_topicName = "";

    function __construct($provide = "",$conf = null,$debugFlag = 0){
        //子类名，即：传输协议标识ID
        $this->_flag = get_called_class();
        MsgQueue::getInstance($provide,$conf,$debugFlag);
        MsgQueue::setDebug($debugFlag);
//        MsgQueue::init();
        MsgQueue::_outInit($this->_flag);

    }
    //调试模式,0:关闭，1：只输出到屏幕 2：只记日志 3：输出到屏幕并记日志
    //注：开启 日志模式，记得引入log包
    function setDebug($flag){
        return MsgQueue::setDebug($flag);
    }
    //生产者可以DIY，消费者也可以DIY，以最后设置的为准。
    //也可以不设置，父类里有默认值
    function setRetryTime(array $retry){
        $this->_retry = $retry;
    }

    function setTopicName($topicName ){
        return MsgQueue::getInstance()->setTopicName($topicName);
    }

    function getRetryTime(){
        return $this->_retry;
    }
    //生产者，设定当前脚本和rabbitmq Server 交互模式  1普通 2确认模式 3事务模式  注：2 跟 3 互斥
    //默认为普通模式，加速性能
    function setMode(int $num){
        return MsgQueue::setMode($num);
    }
    //生产者，注册ACK回调函数 注：得开启 <确认模式>
    function regUserCallbackAck($callback){
        return MsgQueue::_outInit($this->_flag)->regUserCallbackAck($callback);
    }
    //发送一条普通消息给mq
    function send(){
        return MsgQueue::_outInit($this->_flag)->send($this);
    }
    //发送一条延迟消息
    function sendDelay(int $msTime ){
        $arr = array('x-delay'=>$msTime);
        return MsgQueue::_outInit($this->_flag)->send($this,null,$arr);
    }
    //一个consumer同时可处理的消息最大数
    function setReceivedServerMsgMaxNumByOneTime(int $num){
        return MsgQueue::setReceivedServerMsgMaxNumByOneTime($num);
    }


    //========================================以上是生产者相关，以下是消费者相关


    //消费者 - 想监听 - 多个事件 的时候，需要 初始化 队列 信息
    function subscribe($consumerName = ""){
        if(!$consumerName){
            if(!$this->_consumerName){
                $consumerName = $this->_flag;
            }else{
                $consumerName = $this->_consumerName;
            }
        }

        $queueName = $consumerName;
//        if(!$this->_queueName){
//            $queueName = $consumerName;
//        }else{
//            $queueName = $this->_queueName;
//        }

        if(!MsgQueue::getInstance()->queueExist($queueName)){
            MsgQueue::getInstance()->setQueue($queueName,null,$this->_queueMessageDurable);
        }

        if(!$this->_customBindBean){
            MsgQueue::getInstance()->throwException(522);
        }

        $header = array("x-match"=>'any');
        foreach ($this->_customBindBean as $k=>$v) {
            $header[$v] = $v;
        }

        $topicName = $this->_topicName;
        if(!$topicName){
            $topicName = MsgQueue::getInstance()->getTopicName();
        }

        MsgQueue::getInstance()->bindQueue($queueName,$topicName,null,$header);
        MsgQueue::getInstance()->subscribe($queueName,$consumerName);
    }

    function setSubscribeBean(array $beans){
        foreach ($beans as $k=>$bean) {
            if(!is_object($bean)){
                MsgQueue::getInstance()->throwException(521);
            }
            $beanClassName =get_class($bean);
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
            $this->_customBindBean[] =  $beanClassName;
        }
    }
    //快速开启 一个consumer订阅一个队列
    function groupSubscribe($userCallback,$consumerTag = "",$durable = true,$noAck =false){
        if(!$consumerTag){
            $consumerTag = $this->_flag;
        }
        return MsgQueue::_outInit($this->_flag)->groupSubscribe($userCallback,$consumerTag ,false ,$durable ,$noAck,$this->_retry);
    }
    //一个consumer监听多个bean
    function setListenerBean($beanName,$callback){
        return MsgQueue::_outInit($this->_flag)->setListenerBean($beanName,$callback);
    }
    //设置消费者ID
    function setConsumerName(string $name){
        $this->_consumerName = $name;
    }
    //设置消息是否持久化
    function setQueueMessageDurable(bool $flag){
        $this->_queueMessageDurable = $flag;
    }
    //设置一个消费者守护进程，同时接收server最大消息数
    function setUserCallbackFuncExecTimeout(int $time){
        MsgQueue::getInstance()->setUserCallbackFuncExecTimeout($time);
    }
    //消费者开启守护模式，即是死循环，如果有特殊情况想退出，可以使用此方法
    //调用此函数后，原<执行代码空间>，后面的代码即可执行
    function quitConsumerDemon(bool $flag){
        MsgQueue::getInstance()->setStopListenerWait($flag);
    }
    //进程意外退出，如：超时，会执行此函数。类似析构函数
    //但是：如果shell 里直接kill pid  ,或 ctrl+c ，信号可以捕捉到，但不会执行此方法
    //如果用户态 可执行 quitConsumerDemon，就没必要执行此方法了。
    function regConsumerShutdownCallback($func){

    }

    function setMessageMaxLength(int $num){
        MsgQueue::getInstance()->setMessageMaxLength($num);
    }

    //给单元测试工具类使用
    function getProvider(){
        return MsgQueue::getInstance();
    }



//    private $_queueAutoDel = true;//当没有consumer时，会自动 删除队列
//    function setQueueAutoDel(bool $flag){
//        $this->_queueAutoDel = $flag;
//    }
    //开启一个事务
    function  transactionStart(){
        return MsgQueue::transactionStart();
    }
    //提交一个事务
    function  transactionCommit(){
        return MsgQueue::transactionCommit();
    }
    //回滚一个
    function  transactionRollback(){
        return MsgQueue::transactionRollback();
    }


}