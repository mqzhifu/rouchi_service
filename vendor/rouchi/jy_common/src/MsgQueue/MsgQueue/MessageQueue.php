<?php
namespace Jy\Common\MsgQueue\MsgQueue;
use Jy\Common\MsgQueue\Facades\MsgQueue;

abstract class MessageQueue{
    private $_providerName  = null;
    private $_flag = "";
    function __construct()
    {
        //子类名，即是 协议，即是 标识
        $this->_flag = get_called_class();
        MsgQueue::getInstance()->_outInit($this->_flag);
    }
    //发送一条普通消息给mq
    function send(){
        return MsgQueue::getInstance()->_outInit($this->_flag)->send($this);
    }
    //发送一条延迟消息
    function sendDelay(int $msTime ){
        $arr = array('x-delay'=>$msTime);
        return MsgQueue::getInstance()->_outInit($this->_flag)->send($this,$arr);
    }
    //一个consumer订阅一个队列
    function subscribe($queueName, $consumerTag = "",$noAck = false){
        return MsgQueue::getInstance()->_outInit($this->_flag)->subscribe($queueName, $consumerTag ,$noAck );
    }
    //快速开启 一个consumer订阅一个队列
    function groupSubscribe($userCallback,$consumerTag = "",$autoDel = false,$durable = true,$noAck =false){
        return MsgQueue::getInstance()->_outInit($this->_flag)->groupSubscribe($userCallback,$consumerTag ,$autoDel ,$durable ,$noAck);
    }
    //创建一个队列
    function createQueue($queueName,$arguments = null,$durable= null,$autoDel= null){
        MsgQueue::getInstance()->_outInit($this->_flag)->createQueue($queueName,$arguments,$durable,$autoDel);
    }
    //一个consumer监听多个bean
    function setListenerBean($beanName,$callback){
        return MsgQueue::getInstance()->_outInit($this->_flag)->setListenerBean($beanName,$callback);
    }
    //设定当前脚本模式  1普通 2确认模式 3事务模式  注：2 跟 3 互斥
    function setMode(int $num){
        return MsgQueue::getInstance()->setMode($num);
    }

    function setDebug($flag){
        return MsgQueue::getInstance()->setDebug($flag);
    }
    //注册用户ACK回调
    function regUserCallbackAck($callback){
        return MsgQueue::getInstance()->_outInit($this->_flag)->regUserCallbackAck($callback);
    }
    //开启一个事务
    function  transactionStart(){
        return MsgQueue::getInstance()->transactionStart();
    }
    //提交一个事务
    function  transactionCommit(){
        return MsgQueue::getInstance()->transactionCommit();
    }
    //回滚一个
    function  transactionRollback(){
        return MsgQueue::getInstance()->transactionRollback();
    }
    //一个consumer同时可处理的消息最大数
    function setBasicQos(int $num){
        return MsgQueue::getInstance()->setBasicQos($num);
    }
}