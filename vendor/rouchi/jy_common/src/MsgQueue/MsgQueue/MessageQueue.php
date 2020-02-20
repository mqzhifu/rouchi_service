<?php
namespace Jy\Common\MsgQueue\MsgQueue;
use Jy\Common\MsgQueue\Facades\MsgQueue;

abstract class MessageQueue{
    private $_provider = null;
    function __construct()
    {
        $this->_provider = MsgQueue::getInstance("rabbitmq");
        $this->_provider->setClassFlag(get_called_class());
        $this->_provider->setDefaultHeader();
    }
    //发送一个消息给mq
    function send($info,$arguments = null,$header = null){
        return $this->_provider->send($info,$arguments,$header);
    }
    //一个consumer订阅一个队列
    function subscribe($queueName, $consumerTag = "",$noAck = false){
        return $this->_provider->subscribe($queueName, $consumerTag ,$noAck );
    }
    //快速开启 一个consumer订阅一个队列
    function groupSubscribe($userCallback,$consumerTag = "",$autoDel = false,$durable = true,$noAck =false){
        return $this->_provider->groupSubscribe($userCallback,$consumerTag ,$autoDel ,$durable ,$noAck);
    }
    //创建一个队列
    function createQueue($queueName,$arguments,$durable,$autoDel){
        $this->_provider->createQueue($queueName,$arguments,$durable,$autoDel);
    }
    //设定队列绑定值
    function setBindQueue(){

    }
    //设定当前脚本模式  1普通 2确认模式 3事务模式  注：2 跟 3 互斥
    function setMode(int $num){
        return $this->_provider->setMode($num);
    }
    //注册用户ACK回调
    function regUserCallbackAck($callback){
        return $this->_provider->regUserCallbackAck($callback);
    }
    //开启一个事务
    function  transactionStart(){
        return $this->_provider->transactionStart();
    }
    //提交一个事务
    function  transactionCommit(){
        return $this->_provider->transactionCommit();
    }
    //回滚一个
    function  transactionRollback(){
        return $this->_provider->transactionRollback();
    }
    //一个consumer监听多个bean
    function setListenerBean($beanName,$callback){
        return $this->_provider->setListenerBean($beanName,$callback);
    }
    //一个consumer同时可处理的消息最大数
    function setBasicQos(int $num){
        return $this->_provider->setBasicQos($num);
    }

}