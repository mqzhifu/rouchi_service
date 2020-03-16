<?php
namespace Jy\Common\MsgQueue\Test;
include "./../../../../../../vendor/autoload.php";

use \Jy\Common\MsgQueue\MsgQueue\MessageQueue;

use Jy\Common\MsgQueue\Test\Product\OrderBean;
use Jy\Common\MsgQueue\Test\Product\UserBean;
use Jy\Common\MsgQueue\Test\Product\SmsBean;
use Jy\Common\MsgQueue\Test\Product\PaymentBean;
use Jy\Common\MsgQueue\Facades\MsgQueue;
//测试环境，忽略此2行代码
$conf = include "config.php";
MsgQueue::getInstance("rabbitmq",$conf,3)->setTopicName($exchangeName);
//simple($conf);

//consumer监听单bean
//groupSimple($conf);exit;
//groupRetry($conf);exit;
groupReject($conf);exit;
groupRuntimeException($conf);exit;



function subscribeBean($ConsumerManyBean){
    $ConsumerManyBean->subscribe();
}


function groupSimple($conf){
    //最简单的情况
    $SmsBean = new SmsBean($conf);
    $callback = function($msg){
        echo "im user callback by groupSubscribe! \n";
        var_dump($msg);
    };
    $SmsBean->groupSubscribe($callback);
}
//用户，暂时不想处理，走retry机制
function groupRetry($conf){

    $UserBean = new UserBean($conf);
    $UserBean->setRetryTime(array(5, 10));
    $callback = function ($msg) {
        echo "im user callback by groupSubscribe! \n";
        throw new \Exception("tmp", 901);
    };

    $UserBean->groupSubscribe($callback);
}
//用户，觉得该消息有问题，直接丢弃掉
function groupReject($conf){
    $OrderBean = new OrderBean($conf);
    $callback = function($msg){
        echo "im user callback by groupSubscribe! \n";
        throw new \Exception("tmp",900);
    };
    $OrderBean->groupSubscribe($callback);
}
//运行时异常
function groupRuntimeException($conf){
    $SmsBean = new SmsBean($conf);
    $SmsBean->setRetryTime(array(5,10));
    $callback = function($msg){
        throw new \Exception("runtime err",9999);
    };

    $SmsBean->groupSubscribe($callback);
}
