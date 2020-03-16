<?php
namespace Jy\Common\MsgQueue\Test;
include "./../../../../../../vendor/autoload.php";

use \Jy\Common\MsgQueue\MsgQueue\MessageQueue;

use Jy\Common\MsgQueue\Test\Product\OrderBean;
use Jy\Common\MsgQueue\Test\Product\UserBean;
use Jy\Common\MsgQueue\Test\Product\SmsBean;
use Jy\Common\MsgQueue\Test\Product\PaymentBean;
use Jy\Common\MsgQueue\Facades\MsgQueue;
//测试环境，忽略此2行代码
$conf = include "config.php";
MsgQueue::getInstance("rabbitmq",$conf,3)->setTopicName($exchangeName);

class ConsumerOneBean extends  MessageQueue{
    function __construct($conf = ""){
        parent::__construct("rabbitmq", $conf, 3);

        $SmsBean = new SmsBean();
        $this->setSubscribeBean(array($SmsBean));
    }

    function handleSmsBean($msg){
        echo "im handleOrderBean\n";
        var_dump($msg);
    }
}

class ConsumerOneBeanNoHandle extends  MessageQueue{
    function __construct($conf = ""){
        parent::__construct("rabbitmq", $conf, 3);

        $SmsBean = new SmsBean();
        $this->setSubscribeBean(array($SmsBean));
    }
}

class ConsumerOneBeanNoSetBean extends  MessageQueue{
    function __construct($conf = ""){
        parent::__construct("rabbitmq", $conf, 3);
    }
}


class ConsumerManyBean extends  MessageQueue{
    function __construct($conf = "")
    {
        parent::__construct("rabbitmq", $conf, 3);

        $PaymentBean = new PaymentBean();
        $OrderBean = new OrderBean();
        $UserBean = new UserBean();

        $PaymentBean->setRetryTime(array(2,4,6));

        $this->setSubscribeBean(array($OrderBean,$PaymentBean,$UserBean));

    }

    function handlePaymentBean($msg){
        echo "im handlePaymentBean\n";
        throw new \Exception("tmp", 901);
        var_dump($msg);
    }

    function handleOrderBean($msg){
        echo "im handleOrderBean\n";
        throw new \Exception("tmp",900);
        var_dump($msg);
    }

    function handleUserBean($msg){
        echo "im handleUserBean\n";
        var_dump($msg);
    }
}

//$ConsumerOneBean = new ConsumerOneBean($conf);
$ConsumerManyBean =  new ConsumerManyBean($conf);
//异常处理
//$ConsumerOneBeanNoSetBean = new ConsumerOneBeanNoSetBean($conf);
//$ConsumerOneBeanNoHandle = new ConsumerOneBeanNoHandle($conf);

function SubscribeBean($Consumer){
    $Consumer->subscribe();
}


SubscribeBean($ConsumerManyBean);