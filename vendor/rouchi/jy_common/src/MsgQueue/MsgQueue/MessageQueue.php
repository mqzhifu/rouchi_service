<?php
namespace Jy\Common\MsgQueue\MsgQueue;
use Jy\Common\MsgQueue\Facades\MsgQueue;


abstract class MessageQueue{
    private $_providerName  = null;
    private $_flag = "";

    private  $_queueName= "";
    private $_customTagName = "";
    private $_queueAutoDel = true;
    private $_queueMessageDurable = true;

    private $_customBindBean = [];




    function __construct()
    {
        //子类名，即是 协议，即是 标识
        $this->_flag = get_called_class();
        MsgQueue::_outInit($this->_flag);
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

    //快速开启 一个consumer订阅一个队列
    function groupSubscribe($userCallback,$consumerTag = "",$autoDel = false,$durable = true,$noAck =false){
        return MsgQueue::_outInit($this->_flag)->groupSubscribe($userCallback,$consumerTag ,$autoDel ,$durable ,$noAck);
    }
    //一个consumer监听多个bean
    function setListenerBean($beanName,$callback){
        return MsgQueue::_outInit($this->_flag)->setListenerBean($beanName,$callback);
    }
    //设定当前脚本模式  1普通 2确认模式 3事务模式  注：2 跟 3 互斥
    function setMode(int $num){
        return MsgQueue::setMode($num);
    }

    function setDebug($flag){
        return MsgQueue::setDebug($flag);
    }
    //注册用户ACK回调
    function regUserCallbackAck($callback){
        return MsgQueue::_outInit($this->_flag)->regUserCallbackAck($callback);
    }
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
    //一个consumer同时可处理的消息最大数
    function setBasicQos(int $num){
        return MsgQueue::setBasicQos($num);
    }

    function setQueueName(string $queueName){
        $this->_queueName = $queueName;
    }

    function setCustomTagName(string $customTagName){
        $this->_customTagName = $customTagName;
    }

    function setQueueAutoDel(bool $flag){
        $this->_queueAutoDel = $flag;
    }

    function setQueueMessageDurable(bool $flag){
        $this->_queueMessageDurable = $flag;
    }

//    //创建一个队列
//    function consumerInitQueue($queueName,$arguments = null,$durable= null,$autoDel= null,$bindingBeans){
//        MsgQueue::_outInit($this->_flag)->consumerInitQueue($queueName,$arguments,$durable,$autoDel,$bindingBeans);
//    }

    //消费者 - 想监听 - 多个事件 的时候，需要 初始化 队列 信息
    function subscribe(){
        if(!$this->_customTagName){
            MsgQueue::throwException(508);
        }

        if(!$this->_queueName){
            MsgQueue::throwException(510);
        }

        if(!MsgQueue::queueExist($this->_queueName)){
            MsgQueue::setQueue($this->_queueName,$this->_queueMessageDurable,$this->_queueAutoDel);
        }

        if(!$this->_customBindBean){
            MsgQueue::getInstance()->throwException(522);
        }

        $header = array("x-match"=>'any');
        foreach ($this->_customBindBean as $k=>$v) {
            $header[] = array($v=>$v);
        }

        MsgQueue::getInstance()->bindQueue($this->_queueName,MsgQueue::getInstance()->getTopicName(),null,$header);
        MsgQueue::getInstance()->subscribe($this->_queueName,$this->_customTagName);
    }

    function setRetryTime(array $time){
        MsgQueue::getInstance()->setRetryTime($time);
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

    function setUserCallbackFuncExecTimeout(int $time){
        MsgQueue::getInstance()->setUserCallbackFuncExecTimeout($time);
    }

    function consumerStopWait(bool $flag){
        MsgQueue::getInstance()->setStopListenerWait($flag);
    }

    function regShutdown($func){

    }
}