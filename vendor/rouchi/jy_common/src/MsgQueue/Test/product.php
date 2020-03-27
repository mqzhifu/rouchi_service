<?php
namespace Jy\Common\MsgQueue\Test;
include "includeVendor.php";

use Jy\Common\MsgQueue\Test\Product\OrderBean;
use Jy\Common\MsgQueue\Test\Product\UserBean;
use Jy\Common\MsgQueue\Test\Product\SmsBean;
use Jy\Common\MsgQueue\Test\Product\PaymentBean;

use Jy\Common\MsgQueue\Test\Product\LargerBean;
use Jy\Common\MsgQueue\Test\Product\NoRouteBean;
use Jy\Common\MsgQueue\Test\Product\QueueMinLengthBean;
//======================================================

use Jy\Common\MsgQueue\Test\ToolsUnit;
use Jy\Common\Rabbitmq\Test\Consumer\Sms;
use Jy\Common\MsgQueue\Facades\MsgQueue;

//use Jy\Log\Facades\Log;
//Log::getInstance()->init("_path","Log");

//正式环境可忽略此行代码
//$OrderBean->setTopicName($exchangeName);

//因为这是 <类包> 测试用例(独立于项目之外的方式)，我直接在文件里定义了配置文件
//如果是项目里测试，会使用conf里的，php_base里的rabbitqueue.php
//线上 exchange 只有一个：many.header.delay
//$conf = include "config.php";
//$conf = null;

//测试使用,可忽略
//$ToolsUnit = new ToolsUnit();
//$ToolsUnit->clearAll();exit;
//$ToolsUnit->createCaseAndDelOldCase(3);exit;



//因为目前只有rabbitmq一种队列，它是erlang 纯(异步|全双工)编程模型，没有同步通知这个概念。即使你发了一条消息成功，再通过API调，也不一定是最新的结果。
//测试的过程中，想验证结果的话，建议本地搭建一个rabbitmq-server，开启可视化

//MsgQueue::getInstance()->setIgnoreSendNoQueueReceive(1);

$SmsBean = new SmsBean(null,null,3);
$OrderBean = new OrderBean(null,null,3);
$UserBean = new UserBean(null,null,3);
$PaymentBean = new PaymentBean(null,null,3);
$LargerBean =  new LargerBean(null,null,3);
$QueueMinLengthBean = new QueueMinLengthBean(null,null,3);
$NoRouteBean =  new NoRouteBean(null,null,3);



//===================================如下是 测试用例======================================================

//注：因为 下面 bean 中， 开启的模式不一样，有互斥，会报错。（下一版会考虑解决这个）

simple($SmsBean);
//simgpleDelay($UserBean);
//ackModeCallback($OrderBean);
//transaction($PaymentBean);
//manyBeanSynchExec($SmsBean,$UserBean,$OrderBean);
//异常
//noTopic($SmsBean);exit;
//noRouting($NoRouteBean);
//modeMutex($SmsBean);
//ignoreSendNoQueueReceive();
//queueMinLengthNack($QueueMinLengthBean);
//LargerBeanException($LargerBean);
//manyBeanSynchExecMutex($SmsBean,$UserBean,$OrderBean,$PaymentBean);

//sleep(1);

function manyBeanSynchExec($SmsBean,$UserBean,$OrderBean){
    simple($SmsBean);
    simgpleDelay($UserBean);
    ackModeCallback($OrderBean);
}

function manyBeanSynchExecMutex($SmsBean,$UserBean,$OrderBean,$PaymentBean){
    simple($SmsBean);
    simgpleDelay($UserBean);
    ackModeCallback($OrderBean);
    transaction($PaymentBean);
}


function modeMutex(SmsBean $SmsBean){
    $SmsBean->setMode(1);
    $SmsBean->setMode(2);
}

function noRouting(NoRouteBean $NoRouteBean){
    $NoRouteBean->send();
}

function noTopic(SmsBean $SmsBean){
    $SmsBean->setTopicName("none");
    $SmsBean->send();
}

function queueMinLengthNack(QueueMinLengthBean $QueueMinLengthBean){
    $QueueMinLengthBean->_id = 2;
    $QueueMinLengthBean->send();
}

function simple(SmsBean $SmsBean){
    $SmsBean->_type = "register";
    $SmsBean->_id = 1;
    $SmsBean->_msg = "注册成功";
    $SmsBean->send();
    //发送成功的话，test.header.delay.sms 队列会多一条消息
}

function simgpleDelay(UserBean $user){
    $user->_id = "123456";
    $user->_regTime = time();
    $user->_birthday = "20200101";
    $user->_realName = "zhangsan";
    $user->_nickName = "carssbor";

    $user->sendDelay(5000);
    //发送成功的话，5秒后，test.header.delay.sms 队列会多一条消息
}

function ackModeCallback(OrderBean $OrderBean){


    $OrderBean->_id = 1;
    $OrderBean->_price = 100;
    $OrderBean->setMode(1);
    //这里也可以用 类 ，不一定是匿名函数
    //$msg 是刚刚自己发送的消息内容
    $callback = function ($msg){
        echo "im ack mode callback ,orderBean<br/>";
    };
    $OrderBean->regUserCallbackAck($callback);
    $OrderBean->send();

}

function LargerBeanException(LargerBean $largerBean){
    $largerBean->setMessageMaxLength(10);
    $largerBean->send();
}

function transaction(PaymentBean $PaymentBean){
    $PaymentBean->_type = "3yuan_small_class";
    $PaymentBean->_orderId = "abcdefg";
    $PaymentBean->_id = 456;
    try{
        $PaymentBean->transactionStart();
        $PaymentBean->send();
        $PaymentBean->transactionCommit();
    }catch (Exception $e){
        $PaymentBean->transactionRollback();
        var_dump($e->getMessage());exit;
    }

}


function ignoreSendNoQueueReceive(){
    $NoRouteBean = new NoRouteBean();
    $NoRouteBean->setIgnoreSendNoQueueReceive(1);
    noRouting($NoRouteBean);
}