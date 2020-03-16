<?php
namespace Jy\Common\MsgQueue\Test;
include "./../../../../../../vendor/autoload.php";

use Jy\Common\MsgQueue\Test\Product\OrderBean;
use Jy\Common\MsgQueue\Test\Product\UserBean;
use Jy\Common\MsgQueue\Test\Product\SmsBean;
use Jy\Common\MsgQueue\Test\Product\PaymentBean;
use Jy\Common\MsgQueue\Test\Product\LargerBean;

use Jy\Common\MsgQueue\Test\ToolsUnit;
use Jy\Common\Rabbitmq\Test\Consumer\Sms;
use Mockery\Exception;

//因为这是 <类包> 测试用例(独立于项目之外的方式)，我直接在文件里定义了配置文件
//如果是项目里测试，会使用jy_config自动获取rouchi_config
//线上 exchange 只有一个：many.header.delay
$conf = include "config.php";


//测试使用,可忽略
$ToolsUnit = new ToolsUnit($conf);
//$ToolsUnit->clearAll();exit;
//$ToolsUnit->createCaseAndDelOldCase(3);exit;



//因为目前只有rabbitmq一种队列，它是erlang 纯(异步|全双工)编程模型，没有同步通知这个概念。即使你发了一条消息成功，再通过API调，也不一定是最新的结果。
//测试的过程中，想验证结果的话，建议本地搭建一个rabbitmq-server，开启可视化



$OrderBean = new OrderBean($conf);
$SmsBean = new SmsBean($conf);
$UserBean = new UserBean($conf);
$PaymentBean = new PaymentBean($conf);
$LargerBean =  new LargerBean($conf);

//正式环境可忽略此行代码
$OrderBean->setTopicName($exchangeName);



//===================================如下是 测试用例======================================================

//注：因为 下面 开始的模式不一样，有互斥，会报错。（下一版会考虑解决这个）

simple($SmsBean);
//simgpleDelay($UserBean);
//ackModeCallback($OrderBean);
//transaction($PaymentBean);

exit;
LargerBeanException($LargerBean);

function simple(SmsBean $SmsBean){
    $SmsBean->_type = "register";
    $SmsBean->_id = 1;
    $SmsBean->_msg = "注册成功";
    $SmsBean->setRetryTime(array(3,9));
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
        echo "im ack mode callback <br/>";
        var_dump($msg);
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

exit;


//注意 SERVER返回ACK确认 回调函数
//$lib->regAckCallback($clientAck);

//testDirectExchange($lib);
//testHeaderExchange($lib);
//testTopic($lib);
//testFanout($lib);
//testAck($lib);
//testDelayExchange($lib);
//testOther($lib);
//testManyToMany($lib);
//testCapabilitySimple($lib);
//testCapabilityConfirmMode($lib);
//testCapabilityTxMode($lib);

//$lib->wait();

//testUnit(6);





//$rabbit = new RabbitmqBean();
//$rabbit->setMode(1);
//$arg = array("delivery_mode"=>2);
//$rabbit->publishToBase("aaaaaa","test.other","sdff","",$arg);
//$rabbit->waitReturnListener();

//sleep(1);

//exit;

//echo "end";

//这个是最最简单的方式  非<确认|事务> 模式，(exchange queue 都已经建立好)
function testSimple($lib){
//    testUnit(2);
//    $info = "im testSimple";
    $info = new ProductSmsBean();
    $lib->publish( $info );
}

//确认模式下，SERVER返回ACK确认
$clientAck = function ($AMQPMessage) {
    out("client receive callback ack info:");

    $body = AmqpClient::getBody($AMQPMessage);
    var_dump($body);
    //发送消息的属性值
    $attr = AmqpClient::getReceiveAttr($AMQPMessage);
    //格式化，方便输出
    $info = AmqpClient::debugMergeInfo($attr);
    out("body:".json_encode($body) ." " .$info );
    if(isset($attr['message_id'])){
        $msgStatus = RedisMsgStatusAck::redisGetMsgStatusAck($attr['message_id']);
        RedisMsgStatusAck::redisSetMsgStatusAck($attr['message_id'],"sendOk");
        $info = " sendStatus:".$msgStatus['status'] . " sendTime:".date("Y-m-d H:i:s",$msgStatus['time']);
        out($info);
    }

    return true;
};

function testDirectExchange($lib){
//    testUnit($lib);

    $lib->confirmSelectMode();

    $exchangeName = "test.direct";

    $pre = "publish msg <direct> ";
    $info  = $pre ." ,test blank ";
    $lib->publish($info,$exchangeName);

    $info  =  $pre .",test no route";
    $lib->publish($info,$exchangeName,"aaaxxxxx");

    $routingKey = "apple";
    $info  =$pre . ",test routingKey : $routingKey  , queue ";
    $lib->publish($info,$exchangeName,$routingKey);

    $routingKey = "banana";
    $info  =$pre . ",test routingKey : $routingKey  , queue ";
    $lib->publish($info,$exchangeName,$routingKey);

    $routingKey = "banana";
    $info  =$pre ." ,test routingKey : $routingKey  ,complex msg";
    $arguments = [
//        'correlation_id'
//        'priority'=>1e

        'content_type' => 'text/plain',// application/json
        'delivery_mode' => 1 ,//1非持久化 2持久化
//        'content_encoding'=>"gzip",//gzip deflate ,传输格式
        "expiration"=>5000,
        'timestamp'=>time(),

        //以下均为扩展字段
        'type'=>'ext_direct',
        'user_id'=>'root',
        'app_id'=>1,
//        'cluster_id'=>-1,
        "message_id"=>RedisMsgStatusAck::getUniqueMsgId(),
    ];
    $lib->publish($info,$exchangeName,'banana',null,$arguments);

}

function testHeaderExchange($lib){
//    testUnit($lib,2);
    $lib->confirmSelectMode();

    $exchangeName = "test.header";
    $info  ="im header ,test blank publish";
    $lib->publish($info,$exchangeName);

    $info  ="im direct ,test no route publish";
    $lib->publish($info,$exchangeName,"aaaxxxxx");

    $arr = array("aaaa"=>"cccc");
    $info  ="im direct ,test err header publish";
    $lib->publish($info,$exchangeName,"",$arr);

    $info = "im header ,test x-match-all email";
    $arr = array("x-match"=>'all','type'=>'email');
    $lib->publish($info,$exchangeName,"",$arr);

    $info = "im header ,test x-match-all sms";
    $arr = array("x-match"=>'all','type'=>'sms');
    $lib->publish($info,$exchangeName,"",$arr);

    $arr = array("cate"=>"sms",'x-match'=>'all');
    $lib->unbindExchangeQueue($exchangeName,"test.header.sms",null,$arr);

    $arr = array('type'=>'email','x-match'=>'all');
    $lib->bindQueue("test.header.sms",$exchangeName,"",$arr);

    $info = "im header ,test x-match-all email";
    $arr = array("x-match"=>'all','type'=>'email');
    $lib->publish($info,$exchangeName,"",$arr);

}

function testTopic(Lib $lib){
    $lib->confirmSelectMode();

    $exchangeName = "test.topic";

    $info = "im Topic ,test key: #.a.b";
    $lib->publish($info,$exchangeName,"test.topic.error");
    $lib->publish($info,$exchangeName,"test.aaaa.topic.error");


    $info = "im Topic ,test key: A.*.B";
    $lib->publish($info,$exchangeName,"topic.emailsdfsdfd sdf.alert");
    $lib->publish($info,$exchangeName,"topic.emailsdfsdfd.alert");
    $lib->publish($info,$exchangeName,"topic.emailsdfsdfd ,.alert");
}

function testFanout(Lib $lib){
    $lib->confirmSelectMode();

    $exchangeName = "test.fanout";
    $info = "im Topic fanout";
    $info = json_encode($info);
    $lib->publish($info,$exchangeName);
    $lib->publish($info,$exchangeName,"xxxxxx");

}

function testAck(Lib $lib){
    $lib->confirmSelectMode();

    $exchangeName = "test.fanout";
    $info = "im testAck by fanout EX  ,use json header. ";
    $msgId = RedisMsgStatusAck::getUniqueMsgId();
    $arguments = array(
        'content_type'=>'application/json',
        "message_id"=>$msgId,
    );
    //增加批量确认 模式
    RedisMsgStatusAck::redisSetMsgStatusAck($msgId,"sendWait");
    $lib->publish($info,$exchangeName,"fanout.red",null,$arguments);


}

function testDelayExchange($lib){
    $lib->confirmSelectMode();

    $exchangeName = "test.header.delay";
    class Demo1{
        private $_id = 1;
        private $_name = "xiaoz";
    }
    $msgId = RedisMsgStatusAck::getUniqueMsgId();

    $infoClass = new Demo1();
    $info = serialize($infoClass);
    $arguments = array(
//        "expire"=>5000,
        "message_id"=>$msgId,
        'content_type'=>'application/serialize',
    );
    RedisMsgStatusAck::redisSetMsgStatusAck($msgId,"sendWait");
    $header = array("type"=>"email","x-match"=>'any','x-delay'=>10000);
    $lib->publish($info,$exchangeName,"",$header,$arguments);

}

function testOther($lib){
    $lib->confirmSelectMode();

    $exchangeName = "test.other";

    //测试优先集队列
    $info =  "test priority 1";
    $arguments = array(
        'content_type'=>'text/plain',
        'priority'=>1,
    );

    $lib->publish($info,$exchangeName,"priority",null,$arguments);


    $info =  "test priority 10";
    $arguments = array(
        'content_type'=>'text/plain',
        'priority'=>10,
    );

    $lib->publish($info,$exchangeName,"priority",null,$arguments);

    //测试 当队列大于设置的最大值后，消息处理. 去 dead exchange 查找
    $info =  "test max length 1";
    $arguments = array(
        'content_type'=>'text/plain',
    );

    $lib->publish($info,$exchangeName,"max_length",null,$arguments);


    $info =  "testmessage_tll";
    $arguments = array(
        'content_type'=>'text/plain',
    );

    $lib->publish($info,$exchangeName,"message_tll",null,$arguments);

}

function testManyToMany(Lib $lib){
//    testUnit($lib,7);
    $lib->txSelect();

    try{
        $exchangeName = "test.header-many";
        $arr = array("sms"=>"sms");
        $info  ="im header ,testManyToMany publish";
        $lib->publish($info,$exchangeName,"",$arr);


//        $lib->publish($info,"bbbbbb","",$arr);

        $lib->txCommit();
    }catch (Exception $e){
        $lib->txRollback();
    }
}

//测试性能，最简单的模式
function testCapabilitySimple(Lib $lib){
//    testUnit($lib,1);
    $exchangeName = "test.direct";
    $lib->_debug = 0;

    $info = "aaaaaa";
    TestConfig::Capability($lib,$exchangeName,'apple',10000,$info);
//    max length 没有满的情况下，最大0.4 最小0.32

    Capability($lib,$exchangeName,'apple',100000,$info);
    //最多4.1 最少3.59
}
//测试性能，<确认模式>的模式
function testCapabilityConfirmMode(Lib $lib){
//    testUnit($lib,1);
    $lib->confirmSelectMode();
    $exchangeName = "test.direct";
    $lib->_debug = 0;

    $info = "aaaaaa";
    TestConfig::Capability($lib,$exchangeName,'apple',10000,$info);
    //最小0.42  最大0.50
    TestConfig::Capability($lib,$exchangeName,'apple',100000,$info);
    //均5秒
}

function testCapabilityTxMode(Lib $lib){
//    testUnit($lib,1);
    $exchangeName = "test.direct";
    $lib->_debug = 0;
    $lib->txSelect();
    $info = "aaaaaa";

    TestConfig::CapabilityTx($lib,$exchangeName,'apple',10000,$info);
    //2.7-2.4秒
    TestConfig::CapabilityTx($lib,$exchangeName,'apple',100000,$info);
    //不忍直视
}

function getQueueInfo($conf,$queueName){
    return ToolsUnit::apiCurlQueueInfo($conf['user'],$conf['pwd'],$conf['host'].":15672",'%2f',$queueName);
}

