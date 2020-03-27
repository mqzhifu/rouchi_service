<?php
namespace Jy\Common\MsgQueue\Test;
include "includeVendor.php";

use \Jy\Common\MsgQueue\MsgQueue\RabbitmqListenManyBean;

use Jy\Common\MsgQueue\Exception\RejectMsgException;
use Jy\Common\MsgQueue\Exception\RetryException;

//use Jy\Log\Facades\Log;
//Log::getInstance()->init("_path","Log");

//测试环境，忽略此2行代码
//$conf = include "config.php";
//MsgQueue::getInstance("rabbitmq",$conf,3)->setTopicName($exchangeName);

$RabbitmqListenManyBean =  new RabbitmqListenManyBean();

$OrderBeanCallback = function(){echo "im RabbitmqListenManyBean callback OrderBean";};
$UserBeanCallback = function(){echo "im RabbitmqListenManyBean callback UserBean";};
$PaymentBeanCallback = function(){echo "im RabbitmqListenManyBean callback PaymentBean";};
$SmsBeanCallback = function(){echo "im RabbitmqListenManyBean callback SmsBean";};

$classCallbackFunc = array(
    'Jy\Common\MsgQueue\Test\Product\OrderBean' =>$OrderBeanCallback,
    'Jy\Common\MsgQueue\Test\Product\UserBean' =>$UserBeanCallback,
    'Jy\Common\MsgQueue\Test\Product\SmsBean' =>$PaymentBeanCallback,
    'Jy\Common\MsgQueue\Test\Product\PaymentBean' =>$SmsBeanCallback,
);

$RabbitmqListenManyBean->setSubscribeBeanClass($classCallbackFunc);
$RabbitmqListenManyBean->subscribeMany("all");
