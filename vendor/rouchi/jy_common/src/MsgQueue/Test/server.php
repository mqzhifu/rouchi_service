<?php
namespace Jy\Common\Rabbitmq\Test;
include_once "./../../../vendor/autoload.php";

use Jy\Common\MsgQueue\MsgQueue\MessageQueue;

use Jy\Common\MsgQueue\Test\Product\ProductSmsBean;
use Jy\Common\MsgQueue\Test\Product\ProductUserBean;

include_once "testUnitClient.php";


class ConsumerSms extends MessageQueue{
    function __construct()
    {
        parent::__construct();
    }

    function init(){
        $queueName = "test.header.delay.sms";

        $this->setBasicQos(1);
//        $durable = true;$autoDel = false;
//        $this->createQueue();

        $ProductSmsBean = new ProductSmsBean();
        $handleSmsBean = array($this,'handleSmsBean');
        $this->setListenerBean($ProductSmsBean->getBeanName(),$handleSmsBean);

        $ProductUserBean = new ProductUserBean();
        $handleUserBean = array($this,'handleUserBean');
        $this->setListenerBean($ProductUserBean->getBeanName(),$handleUserBean);

        $this->subscribe($queueName,null);
    }

    function handleSmsBean($data){
        echo "im sms bean handle \n ";
        return array("return"=>"ack");
    }

    function handleUserBean($data){
        echo "im user bean handle \n ";
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

