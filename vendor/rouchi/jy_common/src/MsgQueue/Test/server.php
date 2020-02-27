<?php
namespace Jy\Common\Rabbitmq\Test;
include_once "./../../../vendor/autoload.php";

use Jy\Common\MsgQueue\MsgQueue\MessageQueue;

use Jy\Common\MsgQueue\Test\Product\ProductSmsBean;
use Jy\Common\MsgQueue\Test\Product\ProductUserBean;
use Jy\Common\MsgQueue\Test\Product\ProductOrderBean;

include_once "testUnitClient.php";


class HandleUserBean{
    function process($data){
//        var_dump($data['body']);
        echo "im in HandleUserBean method: process \n ";
        //也可以自定义返回  ACK
        return array("return"=>"ack");
    }
}

class HandleUserSmsBean{
    function doing($data){
//        var_dump($data['body']);
        echo "im in HandleUserSmsBean method: doing \n ";
        //也可以自定义返回  ACK
        return array("return"=>"ack");
    }
}


class ConsumerSms extends MessageQueue{
    function __construct(){
        parent::__construct();
    }

    function init(){
        $queueName = "test.header.delay.user";

        //一次最大可接收rabbitmq消息数
        $this->setBasicQos(1);
        $durable = true;//持久化
        $autoDel = false;//如果没有consumer 消费将自动 删除队列
        $this->createQueue($queueName,null,$durable,$autoDel);

        $ProductSmsBean = new ProductSmsBean();
        $handleSmsBean = array($this,'handleSmsBean');
        $this->setListenerBean($ProductSmsBean,$handleSmsBean);
        //======================================================


        $ProductUserBean = new ProductUserBean();

        $HandleUserBeanClass =  new HandleUserBean();
        $handleUserBean = array($HandleUserBeanClass,'process');
        $this->setListenerBean($ProductUserBean,$handleUserBean);


        $HandleUserSmsBean =  new HandleUserSmsBean();
        $handleUserBean = array($HandleUserSmsBean,'doing');
        $this->setListenerBean($ProductUserBean,$handleUserBean);

        //=================================================
        $ProductUserBean = new ProductOrderBean();
        $handleUserBean = array($this,'handleOrderBean');
        $this->setListenerBean($ProductUserBean,$handleUserBean);

        $this->subscribe($queueName,null);
    }

    function handleSmsBean($data){
        var_dump($data['body']);
        echo "im sms bean handle \n ";
        //什么都不返回，默认情况，框架会自动 ACK
    }

    function handleUserBean($data){
        var_dump($data['body']);
        echo "im user bean handle \n ";
        //也可以自定义返回  ACK
        return array("return"=>"ack");
    }

    function handleOrderBean($data){
        var_dump($data['body']);
        echo "im order bean handle \n ";
        //这里是，假设：发现数据不对，想将此条消息打回，有2种选择
        //1   reject 配合requeue :true 不要再重试了，直接丢弃。  false:等待固定时间，想再重试一下
        //2   直接抛出异常  ,框架会 给3次重试机会，如果还是一直失败，则抛弃
        return array("return"=>"reject",'requeue'=>false);
    }

}

$lib = new ConsumerSms();
$lib->init();





function out($msg ,$br = 1){
    if(is_object($msg) || is_array($msg)){
        $msg = json_encode($msg);
    }
    if($br){
        if (preg_match("/cli/i", php_sapi_name())){
            echo $msg . "\n";
        }else{
            echo $msg . "<br/>";
        }
    }else{
        echo $msg;
    }
}

