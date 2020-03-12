<?php
namespace Rouchi\Console\Mq;

use Rouchi\Product\OrderBean;
use Rouchi\Product\PaymentBean;
use Rouchi\Product\SmsBean;
use Rouchi\Product\UserBean;

class Consumer{
    function index(){
        echo 111;
    }

//    public function server(){
//        $OrderBean = new OrderBean();
//        $callback = array($this,'serverCallbackHandle');
//        $OrderBean->groupSubscribe($callback,"testServer");
//
//    }
//
//    function serverCallbackHandle($data){
//        echo "im in serverCallbackHandle";
//    }
//
    public function customServer(){
        $HandleUserBean = new ConsumerSms();
        $HandleUserBean->init();
    }

    function groupSubscript(){
        $OrderBean = new OrderBean();
        $callback = function($reCallData){
            echo "im in groupSubscript<br/>";
            var_dump($reCallData);
        };
        $consumerTag = "groupSubscript_order_bean";
        $OrderBean->groupSubscribe($callback,$consumerTag);
    }
}


class HandleUserBean{
    function process($data){
//        var_dump($data['body']);
        echo "im in HandleUserBean method: process \n ";
        //也可以自定义返回  ACK
        return array("return"=>"ack");
    }
}

class HandleUserSmsBean{
    function doing($data){
//        var_dump($data['body']);
        echo "im in HandleUserSmsBean method: doing \n ";
        //也可以自定义返回  ACK
        return array("return"=>"ack");
    }
}


class ConsumerSms extends \Jy\Common\MsgQueue\MsgQueue\MessageQueue {
    function __construct(){
        parent::__construct();
    }


    function init(){
//        $PaymentBean = new PaymentBean();
//        $SmsBean = new SmsBean();

        $OrderBean = new OrderBean();
        $UserBean = new UserBean();
        $OrderBean->setRetryTime(array(2,5));
//        $UserBean->setRetryTime(array(3,7,9));

        $this->setDebug(3);
        $queueName = "many.header.delay.order";
        $this->setCustomerQueueName($queueName);
        $this->setSubscribeBean(array($OrderBean,$UserBean));
        $this->subscribe();



//        $this->setReceivedServerMsgMaxNumByOneTime(1);
//        $this->setRetryTime(array(1,5,10));
//        $this->setUserCallbackFuncExecTimeout(10);
//        $this->setQueueMessageDurable(true);//持久化
//        $this->setQueueAutoDel(false);//如果没有consumer rabbitmq 将自动 删除队列

    }

    function initBak(){
        //        $PaymentBean = new PaymentBean();
        $OrderBean = new OrderBean();
        $UserBean = new UserBean();
//        $SmsBean = new SmsBean();


        $this->setDebug(2);

        $queueName = "many.header.delay.order";
        $this->setQueueName($queueName);
        //一次最大可接收rabbitmq消息数
        $this->setBasicQos(1);
        $this->setQueueMessageDurable(true);//持久化
        $this->setQueueAutoDel(false);//如果没有consumer rabbitmq 将自动 删除队列
        $this->setSubscribeBean(array($OrderBean,$UserBean));
        $this->subscribe();

//        $bindingBeans = array("Rouchi\Product\OrderBean","Rouchi\Product\UserBean");
//        $this->consumerInitQueue($queueName,null,$durable,$autoDel,$bindingBeans);


        //====================================================

//        $handleSmsBean = array($this,'handleOrderBean');
//        $this->setListenerBean($OrderBean,$handleSmsBean);
        //======================================================


//        $HandleUserBeanClass =  new HandleUserBean();
//        $handleUserBean = array($HandleUserBeanClass,'process');
//        $this->setListenerBean($UserBean,$handleUserBean);


//        $HandleUserSmsBean =  new HandleUserSmsBean();
//        $handleUserBean = array($HandleUserSmsBean,'doing');
//        $this->setListenerBean($UserBean,$handleUserBean);

        //=================================================
//        $ProductUserBean = new ProductOrderBean();
//        $handleUserBean = array($this,'handleOrderBean');
//        $this->setListenerBean($ProductUserBean,$handleUserBean);


    }

//    function handleOrderBean($data){
////        var_dump($data['body']);
//        echo "im handleOrderBean handle \n ";
//        //什么都不返回，默认情况，框架会自动 ACK
//    }

    function handleUserBean($data){
        var_dump($data['body']);
        echo "im user bean handle \n ";
        //也可以自定义返回  ACK
        return array("return"=>"ack");
    }

    function handleOrderBean($data):bool{
//        var_dump($data);
//        set_time_limit(10);
        echo "im order bean handle \n ";
        echo "im dead loop where 1";
//        while(1){}
        return true;
    }

}