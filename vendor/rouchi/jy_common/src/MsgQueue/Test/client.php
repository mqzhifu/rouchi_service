<?php
namespace Jy\Common\MsgQueue\Test;
//因为依赖3方类库，需要先引入
include_once "./../../../vendor/autoload.php";

//用户自定义的类

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
//clearAll();exit;
//testUnit(3);exit;


//先定义ProductSms一个生产类，只需要继承一个基类即可
$productSms = new ProductSms();
//再定义一个通信协议类
$ProductSmsBean = new ProductSmsBean();
//初始化要发送的数据
$ProductSmsBean->_id = 1;
$ProductSmsBean->_msg = "is finish.";
$ProductSmsBean->_type = "order";
//发送一条普通的消息
$productSms->send($ProductSmsBean);

//发送一条5秒失效的消息
$arguments = array("expiration"=>5000);
$productSms->send($ProductSmsBean,$arguments);
//发送一条，延迟15秒执行的消息
$header = array('x-delay'=>15000);
$productSms->send($ProductSmsBean,null,$header);

//两个也可以一起用，delay 先触发，然后是expire
$arguments = array("expiration"=>5000);
$header = array('x-delay'=>10000);
$productSms->send($ProductSmsBean,$arguments,$header);

//测试确认模式
$ProductOrder = new ProductOrder();
$ProductOrderBean = new ProductOrderBean();
$ProductOrderBean->_id = 1;
$ProductOrderBean->_price = 100.1;
$ProductOrderBean->_price = 2;
$ProductOrderBean->_uid = 100000;

$ProductOrder->send($ProductOrderBean);


//测试事务模式
$ProductUser = new ProductUser();
$ProductUserBean = new ProductUserBean();
$ProductUserBean->_nickName = "carssbor";
$ProductUserBean->_realName = "wang";
$ProductUserBean->_id = 10000;
$ProductUserBean->_birthday = 19902014;
$ProductUserBean->_regTime = 20200101;

//发送一条事务消息
$ProductUser->publishTx($ProductUserBean);
//也可以自己封装一下 延迟 发送消息
$ProductUser->publishDelay($ProductUserBean,10000);//10秒


//快速开启consumer 监听某一个事件队列
$productSms = new ProductSms();
$userCallback = function ($recall){
    echo "im in user callback func \n";
};
$productSms->groupSubscribe($userCallback,"dept_A");






//$header = ["Jy\Common\MsgQueue\Test\Product\ProductSms"=>"Jy\Common\MsgQueue\Test\Product\ProductSms"];
//$productSms->publish("aaaa",null,$header);
//exit;
