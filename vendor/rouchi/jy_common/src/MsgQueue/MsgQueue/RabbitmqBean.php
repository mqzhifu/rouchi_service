<?php
namespace Jy\Common\MsgQueue\MsgQueue;

class RabbitmqBean extends \Jy\Common\MsgQueue\MsgQueue\RabbitmqBase{

    function __construct( $conf ){

    }
    //初始化
    function init(){

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



}