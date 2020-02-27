<?php
namespace Jy\Common\MsgQueue\Test;
//因为依赖3方类库，需要先引入
include_once "./../../../vendor/autoload.php";

/*
 *
 * rabbitmq_delayed_message 插件
 * 问题：
 * 1、不支持mandatory flag
 * 2、发送后，将会存于当前节点的Mnesia表中，等到时候后，再投递给rabbitmq。换言之，不支持集群分布式
 * 3、正常重启，延迟消息依然生效，但是如果 关闭/禁止 该插件，还未到期的延迟消息，将会丢失
 *
*/

//短信
use Jy\Common\MsgQueue\Test\Product\ProductSms;
use Jy\Common\MsgQueue\Test\Product\ProductSmsBean;
//订单
use Jy\Common\MsgQueue\Test\Product\ProductOrder;
use Jy\Common\MsgQueue\Test\Product\ProductOrderBean;
//用户
use Jy\Common\MsgQueue\Test\Product\ProductUser;
use Jy\Common\MsgQueue\Test\Product\ProductUserBean;

//测试使用
include_once "testUnitClient.php";
//clearAll();
//testUnit(7);


//先定义ProductSms一个生产类，只需要继承一个基类即可
$productSms = new ProductSms();
//再定义一个通信协议类
$ProductSmsBean = new ProductSmsBean();
//初始化要发送的数据
$ProductSmsBean->_id = 1;
$ProductSmsBean->_msg = "is finish.";
$ProductSmsBean->_type = "order";
//发送一条普通的消息


//异常1：确认 与 事务 互斥

//$productSms->setMode(1);
//$productSms->transactionStart();
//$productSms->send($ProductSmsBean);
//$productSms->transactionCommit();

//异常2：exchange name err

//异常3：
//$productSms->setMode(1);
//$arguments = array("x-max-length"=>1000);
//$productSms->createQueue("test.hesdfader.delay.sms",$arguments,false,true);


//异常4：失效 reject nack recover 丢失/死信
$productSms->setMode(1);
$arguments = [];
//$arguments = array("expiration"=>5000);
$header = array(
    "email"=>"email",
    'x-match'=>'any',
    'x-delay'=>10000,
);
$productSms->publishToBase("aaaaa","test.header-many","",$header,$arguments);
$productSms->basicWait();
