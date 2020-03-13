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

    public function customServer(){
        $HandleUserBean = new ConsumerSms();
        $HandleUserBean->subscribe();
    }

    function groupSubscript(){
        $OrderBean = new OrderBean();
        $callback = function($reCallData){
            echo "im in groupSubscript<br/>";

            $orderid = $reCallData->getId();

            var_dump($reCallData);

            return false;
        };
        $consumerTag = "groupSubscript_order_bean";
        $OrderBean->groupSubscribe($callback,$consumerTag);
    }
}


class ConsumerSms extends \Jy\Common\MsgQueue\MsgQueue\MessageQueue {
    function __construct(){
        parent::__construct();

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



//        $this->setReceivedServerMsgMaxNumByOneTime(1);
//        $this->setRetryTime(array(1,5,10));
//        $this->setUserCallbackFuncExecTimeout(10);
//        $this->setQueueMessageDurable(true);//持久化
//        $this->setQueueAutoDel(false);//如果没有consumer rabbitmq 将自动 删除队列
    }


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

//    function handleOrderBean($data){
//        var_dump($data['body']);
//        echo "im handleOrderBean handle \n ";
//        //什么都不返回，默认情况，框架会自动 ACK
//    }


}