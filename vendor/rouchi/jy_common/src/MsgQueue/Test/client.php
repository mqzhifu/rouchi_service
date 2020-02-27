<?php
namespace Jy\Common\MsgQueue\Test;
//因为依赖3方类库，需要先引入  php-amqplib
include_once "./../../../vendor/autoload.php";

//用户自定义的类:短信 订单 用户

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

//清空原有队列，创建新的 延迟队列及exchange
//testUnit(3);


//$connectConf =['host' => '127.0.0.1', 'port' => 5672, 'user' => 'root', 'pwd' => 'root', 'vhost' => '/',];
//$connectConf =['host' => '172.19.113.249', 'port' => 5672, 'user' => 'root', 'pwd' => 'sfat324#43523dak&', 'vhost' => '/',];
//配置文件，根据自己情况选择吧。不写的话，默认值是 127.0.0.1 这个
$connectConf = null;




//testSendSimple($connectConf);
//testSendConfirm($connectConf);
testSendTx($connectConf);
//testQuickConsumer($connectConf);

function testSendSimple($connectConf){
    //先定义ProductSms一个生产类，只需要继承一个基类即可
    $productSms = new ProductSms($connectConf);
    //再定义一个通信协议类
    $ProductSmsBean = new ProductSmsBean();
    //初始化要发送的数据
    $ProductSmsBean->_id = 1;
    $ProductSmsBean->_msg = "is finish.";
    $ProductSmsBean->_type = "order";
    //发送一条普通的消息
    $msgId = $productSms->send($ProductSmsBean);
    var_dump($msgId);
    //发送一条，延迟5秒执行的消息
    $header = array('x-delay'=>5000);
    $productSms->send($ProductSmsBean,null,$header);
}

function testSendConfirm($connectConf){
    //测试<确认>模式
//    $ProductOrder = new ProductOrder($connectConf);
    $ProductOrderBean = new ProductOrderBean();
    $ProductOrderBean->_id = 1;
    $ProductOrderBean->_price = 100.1;
    $ProductOrderBean->_price = 2;
    $ProductOrderBean->_uid = 100000;

    $ProductOrderBean->send($ProductOrderBean);
}

function testSendTx($connectConf){
    //测试事务模式
//    $ProductUser = new ProductUser($connectConf);
    $ProductUserBean = new ProductUserBean();
    $ProductUserBean->_nickName = "carssbor";
    $ProductUserBean->_realName = "wang";
    $ProductUserBean->_id = 10000;
    $ProductUserBean->_birthday = 19902014;
    $ProductUserBean->_regTime = 20200101;

    //发送一条事务消息
    $ProductUserBean->publishTx($ProductUserBean);
    //也可以自己封装一下 延迟 发送消息
//    $ProductUserBean->publishDelay($ProductUserBean,10000);//10秒
}


//快速开启一个consumer监听队列并消费
function testQuickConsumer($connectConf){
    //快速开启consumer 监听某一个事件队列
    $productSms = new ProductSms($connectConf);
    $userCallback = function ($recall){
        var_dump($recall['body']);
        echo "im in user callback func \n";
    };
    $productSms->groupSubscribe($userCallback,"dept_A");

    //$header = ["Jy\Common\MsgQueue\Test\Product\ProductSms"=>"Jy\Common\MsgQueue\Test\Product\ProductSms"];
    //$productSms->publish("aaaa",null,$header);
    //exit;
}

function testQuickConsumerOneGroup(){

}










