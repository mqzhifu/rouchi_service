<?php
namespace Jy\Common\MsgQueue\Test;
//因为依赖3方类库，需要先引入  php-amqplib
include_once "./../../../vendor/autoload.php";

use function GuzzleHttp\Psr7\parse_header;
use Jy\Common\MsgQueue\MsgQueue\MessageQueue;

//class ProductSms extends MessageQueue {


//class MyMq extends MessageQueue{
//    function __construct(){
//        parent::__construct();
//    }
//
//    function sendMsg($Bean){
//        $this->send($Bean);
//    }
//}

class Bean extends MessageQueue{
    public $_id = 1;
    public $_channel = "tencent";//来源渠道
    public $_price = 0.00;//金额
    public $_num = 0;//购买数量
    public $_uid = 0;//用户ID

    function getBeanName(){
        return __CLASS__;
    }

}


$myClass = new Bean();
//$Bean = new Bean();
$myClass->channel = "baidu";

$myClass->send($myClass);
