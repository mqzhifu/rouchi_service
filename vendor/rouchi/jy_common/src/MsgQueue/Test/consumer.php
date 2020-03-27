<?php
namespace Jy\Common\MsgQueue\Test;
include "includeVendor.php";

use \Jy\Common\MsgQueue\MsgQueue\MessageQueue;
use Jy\Common\MsgQueue\Facades\MsgQueue;

use Jy\Common\MsgQueue\Test\Product\OrderBean;
use Jy\Common\MsgQueue\Test\Product\UserBean;
use Jy\Common\MsgQueue\Test\Product\SmsBean;
use Jy\Common\MsgQueue\Test\Product\PaymentBean;
use Jy\Common\MsgQueue\Test\Product\QueueMinLengthBean;

use Jy\Common\MsgQueue\Exception\RejectMsgException;
use Jy\Common\MsgQueue\Exception\RetryException;

//use Jy\Log\Facades\Log;
//Log::getInstance()->init("_path","Log");

//测试环境，忽略此2行代码
//$conf = include "config.php";
//MsgQueue::getInstance("rabbitmq",$conf,3)->setTopicName($exchangeName);



//开始  consumer监听单bean


//simple();exit;
//retry();exit;
//retryByMsg();exit;
//reject();exit;
//runtimeException();exit;
//runtimeUserCancel();exit;
//synchReceiveServerManyMsg();exit;

//异常
//noTopic();
//noAck();
//execTimeout();
//signalStopProcess();


function runtimeUserCancel(){
    $SmsBean = new SmsBean(null,null,3);


    $shutdownFunc = function(){
        echo "im shutdown callback func";
    };
    $SmsBean->regConsumerShutdownCallback($shutdownFunc);

    $callback = function() use ($SmsBean){
        echo "im runtimeUserCancel";
        $SmsBean->quitConsumerDemon();
    };
    $SmsBean->groupSubscribe($callback,"groupSimple");


    echo "cancel after....";
}

function execTimeout(){
    //最简单的情况
    $SmsBean = new SmsBean(null,null,3);
    $callback = function($msg){
        while (1){}
    };
    $SmsBean->groupSubscribe($callback,"groupSimple");
}



function noTopic(){
    $SmsBean = new SmsBean(null,null,3);
    $SmsBean->setTopicName("none");
    $callback = function(){
        echo "im noTopic";
    };
    $SmsBean->groupSubscribe($callback,"groupSimple");
}

function simple(){
    //最简单的情况
    $SmsBean = new SmsBean(null,null,3);
    $callback = function($msg){
        echo "im user callback by groupSubscribe, bean:SmsBean! \n";
    };
    $SmsBean->groupSubscribe($callback,"groupSimple");
}
//用户，暂时不想处理，走retry机制
function retry(){
    $UserBean = new UserBean(null,null,3);
    $UserBean->setRetryTime(array(5, 10));
    $callback = function ($msg) {
        echo "im user callback by groupSubscribe,bean: UserBean!! \n";
        throw new RetryException();
    };

    $UserBean->groupSubscribe($callback,'groupRetry');
}
function retryByMsg(){
    $UserBean = new UserBean(null,null,3);
    $UserBean->setRetryTime(array(5, 10));
    $callback = function ($msg) {
        echo "im user callback by groupSubscribe,bean: UserBean!! \n";
        $RetryException = new RetryException();
        $RetryException->setRetry(array(4, 7));
        throw $RetryException;
//        throw new RetryException();
    };

    $UserBean->groupSubscribe($callback,'groupRetry');
}
//用户，觉得该消息有问题，直接丢弃掉
function reject(){
    $OrderBean = new OrderBean(null,null,3);
    $callback = function($msg){
        echo "im user callback by groupSubscribe,bean: OrderBean! \n";
//        throw new \Exception("tmp",900);
        throw new RejectMsgException();
    };
    $OrderBean->groupSubscribe($callback,'groupReject');
}
//运行时异常
function runtimeException($conf){
    $SmsBean = new SmsBean($conf);
    $SmsBean->setRetryTime(array(5,10));
    $callback = function($msg){
        throw new \Exception("runtime err",9999);
    };

    $SmsBean->groupSubscribe($callback);
}

////QueueMinLengthBean();
//function QueueMinLengthBean(){
//    $QueueMinLengthBean = new QueueMinLengthBean(null,null,3);
//    $func = function ($backData){
//        var_dump($backData->_id);
//    };
//    $QueueMinLengthBean->groupSubscribe($func,"QueueMinLengthBean");
//}