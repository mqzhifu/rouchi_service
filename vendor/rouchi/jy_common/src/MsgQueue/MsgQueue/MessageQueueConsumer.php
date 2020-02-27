<?php
namespace Jy\Common\MsgQueue\MsgQueue;
use Jy\Common\MsgQueue\Facades\MsgQueue;

abstract class MessageQueueConsumer{
    private $_providerName  = null;
    private $_provider = null;
    private $_flag = "";
    function __construct()
    {
        $this->_flag = get_called_class();
        $this->_provider = new RabbitmqBean();
        MsgQueue::getInstance()->_outInit($this->_flag);
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
    function setDebug($flag){
        return $this->setDebug($flag);
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