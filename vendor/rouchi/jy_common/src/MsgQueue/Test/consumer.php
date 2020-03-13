<?php
namespace Jy\Common\MsgQueue\Test;
include "./../../../../../../vendor/autoload.php";

use \Jy\Common\MsgQueue\MsgQueue\MessageQueue;

use Jy\Common\MsgQueue\Test\Product\OrderBean;
use Jy\Common\MsgQueue\Test\Product\UserBean;
use Jy\Common\MsgQueue\Test\Product\SmsBean;

$conf = include "config.php";

class ConsumerManyBean extends  MessageQueue{
    function __construct($conf = "")
    {
        parent::__construct("rabbitmq", $conf, 3);

        $OrderBean = new OrderBean();
//        $UserBean = new UserBean();

        $OrderBean->setRetryTime(array(2, 5));
        $this->setSubscribeBean(array($OrderBean));

    }

    function handleOrderBean($msg){
        echo "im handleOrderBean\n";
        var_dump($msg);
    }
}

class ConsumerManyBean2 extends  MessageQueue{
    function __construct($conf = "")
    {
        parent::__construct("rabbitmq", $conf, 3);

//        $PaymentBean = new PaymentBean();
//        $SmsBean = new SmsBean();

        $OrderBean = new OrderBean();
//        $UserBean = new UserBean();

        $OrderBean->setRetryTime(array(2, 5));

        $this->setDebug(3);
        $this->setSubscribeBean(array($OrderBean));

//        $queueName = "many.header.delay.order";
//        $this->setCustomerQueueName($queueName);
    }
}


//simple($conf);
$ConsumerManyBean =  new ConsumerManyBean($conf);
manyBean($ConsumerManyBean);





function manyBean($ConsumerManyBean){
    $ConsumerManyBean->subscribe();
}


function simple($conf){
    //最简单的情况
//    $SmsBean = new SmsBean($conf);
//    $callback = function($msg){
//        echo "im user callback by groupSubscribe! \n";
//        var_dump($msg);
//    };
//    $SmsBean->groupSubscribe($callback);


    //用户，暂时不想处理，走retry机制
//    $SmsBean = new SmsBean($conf);
//    $SmsBean->setRetryTime(array(5,10));
//    $callback = function($msg){
//        echo "im user callback by groupSubscribe! \n";
//        throw new \Exception("tmp",901);
//    };

    //用户，觉得该消息有问题，直接丢弃掉
//    $SmsBean = new SmsBean($conf);
//    $SmsBean->setRetryTime(array(5,10));
//    $callback = function($msg){
//        echo "im user callback by groupSubscribe! \n";
//        throw new \Exception("tmp",900);
//    };

    //运行时异常
    $SmsBean = new SmsBean($conf);
    $SmsBean->setRetryTime(array(5,10));
    $callback = function($msg){
        throw new \Exception("runtime err",9999);
    };

    $SmsBean->groupSubscribe($callback);
}

function testOneConsumer($lib){
    $lib->setBasicQos(1);

    $callback = function ($recall) use ($lib){
        $info = AmqpConsumer::debugMergeInfo($recall['attr']);
        out(" callback attr info: $info");

        $sms = new \Jy\Common\Rabbitmq\Test\Consumer\Sms();
        $sms->process();

        $lib->ack($recall['AMQPMessage']);

    };

    $queue_name = "test.direct.apple";
    $autoAck = false;
    $consumerTag = $queue_name ." tag";
    out("queue:$queue_name , autoAck: $autoAck , consumerTag : $consumerTag. ");
    $lib->basicConsume($queue_name, $consumerTag, $autoAck,  $callback);
    $lib->startListenerWait();
}

function testOneConsumerAckMode(){
    $lib = new AmqpConsumer();
    $lib->setBasicQos(1);

    $callback = function ($recall) use ($lib){
        $info = AmqpConsumer::debugMergeInfo($recall['attr']);
        out(" callback attr info: $info");

        $sendMsgStatus = AmqpConsumer::redisGetMsgStatusAck($recall['attr']['message_id']);
        if($sendMsgStatus['status'] == 'sendWait' || $sendMsgStatus['status'] == 'consumerFinish' ){
            $lib->reject($recall['AMQPMessage'],false);
            return true;
        }

        if($sendMsgStatus['status'] == 'consumerProcessing'){
            if(time() - $sendMsgStatus['time'] < 10){
                $lib->basic_reject($recall['AMQPMessage'],true);
                return true;
            }
        }

        AmqpConsumer::redisSetMsgStatusAck("consumerProcessing");
        try{
            $sms = new Sms();
            $sms->process();
        }catch (Exception $e){
            AmqpConsumer::redisSetMsgStatusAck("failed-".$e->getMessage());
            $lib->reject($recall['AMQPMessage'],false);
            return true;
        }


        AmqpConsumer::redisSetMsgStatusAck("consumerFinish");
        $lib->ack($recall['AMQPMessage']);
    };

    $queue_name = "test.header.delay.email";
    $autoAck = false;
    $consumerTag = "test.header.delay.email.consumer";
    out("queue:$queue_name , autoAck: $autoAck , consumerTag : $consumerTag. ");
    $lib->basicConsume($queue_name, $consumerTag, $autoAck,  $callback);

    $lib->consumerWait();
}

function testCancelConsumer(){
    $lib = new AmqpConsumer();
    $lib->setBasicQos(3);

    $queue_name = "test.direct.apple";
    $autoAck = false;
    $consumerTag = $queue_name ." tag";
    out("queue:$queue_name , autoAck: $autoAck , consumerTag : $consumerTag. ");


    $cnt = 1;
    $callback = function ($recall) use ($lib,$cnt,$consumerTag){
        global $cnt;
        $info = AmqpConsumer::debugMergeInfo($recall['attr']);
        out(" callback attr info: $info");

        $sms = new Sms();
        $sms->process();

        $lib->ack($recall['AMQPMessage']);

        if($cnt >= 5){
            $lib->listenerCancel($consumerTag);
            $lib->setStopListenerWait(1);
        }
        $cnt++;
    };

    $lib->basicConsume($queue_name, $consumerTag, $autoAck,  $callback);
    $lib->consumerWait();
}
//测试direct exchange ，开启多个consumer 监听
function testDirectExchangeByMultiConsumer($lib){
    $lib->setBasicQos(1);

    $callback = function ($recall) use ($lib){
//        $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'],true);

        $info = AmqpConsumer::debugMergeInfo($recall['attr']);
        out(" have a new msg: attr info: $info");

        if(!isset($recall['attr']['message_id']) || !$recall['attr']['message_id']){
            $lib->ack($recall['AMQPMessage']);
            return false;
        }

        $sendMsgStatus = RedisMsgStatusAck::redisGetMsgStatusAck($recall['attr']['message_id']);
        if($sendMsgStatus['status'] == 'sendWait' || $sendMsgStatus['status'] == 'consumerFinish' ){
            $lib->reject($recall['AMQPMessage'],false);
            return true;
        }

        if($sendMsgStatus['status'] == 'consumerProcessing'){
            if(time() - $sendMsgStatus['time'] < 10){
                $lib->reject($recall['AMQPMessage'],true);
                return true;
            }
        }

        RedisMsgStatusAck::redisSetMsgStatusAck($recall['attr']['message_id'],"consumerProcessing");
        try{
            //这里可以根据自己的需求，实现类
//            $sms = new Sms();
//            $sms->process();
            RedisMsgStatusAck::redisSetMsgStatusAck($recall['attr']['message_id'],"consumerFinish");
            $lib->ack($recall['AMQPMessage']);
        }catch (Exception $e){
            AmqpConsumer::redisSetMsgStatusAck("failed-".$e->getMessage());
            $lib->reject($recall['AMQPMessage'],false);
        }
    };
    //根据工具类，直接获取特定的一批队列名称
    $Tools = new Tools($lib);
    $Tools->setProjectId(1);
    $queues = $Tools->getQueues();
    //循环开启多个consumer ，走channel模式，复用一个TCP连接
    //这里，我为了省事儿只定义了一个CALLBACK function，如果想根据TYPE定制消费。多定义几个callback function 注册一下就行了
    foreach ($queues as $k=>$queue_name) {
        $autoAck = false;
        $consumerTag = $queue_name.".consumer";
        $lib->basicConsume($queue_name, $consumerTag, $autoAck,  $callback);
    }

    $lib->startListenerWait();
}