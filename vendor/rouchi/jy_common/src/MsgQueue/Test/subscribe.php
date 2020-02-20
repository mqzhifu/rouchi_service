<?php
namespace Jy\Common\MsgQueue\Test;
include_once "./../../../vendor/autoload.php";

//用户自定义的类
use Jy\Common\MsgQueue\Test\Product\ProductSms;

$productSms = new ProductSms();
$userCallback = function ($recall){
    echo "im in user callback func \n";
};
$productSms->groupSubscribe($userCallback,"dept_A");


//$header = ["Jy\Common\MsgQueue\Test\Product\ProductSms"=>"Jy\Common\MsgQueue\Test\Product\ProductSms"];
//$productSms->publish("aaaa",null,$header);
//exit;



//retry 策略
//顺序消费
//consumer 挂了，导致 消息积压
//堆积的数据正好有ttl，你还没处理完。。  rabbitmq KILL 掉了
//mq 挂了，整个系统全挂
//整体一致性
//内存或硬盘暴了，丢失数据
//如何追踪