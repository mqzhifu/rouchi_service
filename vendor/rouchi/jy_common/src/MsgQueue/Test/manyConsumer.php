<?php
namespace Jy\Common\MsgQueue\Test;
include "includeVendor.php";

use \Jy\Common\MsgQueue\MsgQueue\MessageQueue;

use Jy\Common\MsgQueue\Test\Product\OrderBean;
use Jy\Common\MsgQueue\Test\Product\UserBean;
use Jy\Common\MsgQueue\Test\Product\SmsBean;
use Jy\Common\MsgQueue\Test\Product\PaymentBean;

use Jy\Common\MsgQueue\Exception\RejectMsgException;
use Jy\Common\MsgQueue\Exception\RetryException;

//use Jy\Common\MsgQueue\Facades\MsgQueue;
//测试环境，忽略此2行代码
//$conf = include "config.php";
//MsgQueue::getInstance("rabbitmq",$conf,3)->setTopicName($exchangeName);

class ConsumerOneBean extends  MessageQueue{
    function __construct($conf = ""){
        parent::__construct("rabbitmq", $conf, 3);

        $SmsBean = new SmsBean(null,null,3);
        $this->setSubscribeBean(array($SmsBean));
    }

    function handleSmsBean($msg){
        echo "im handleOrderBean , ConsumerOneBean\n";
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

        $PaymentBean = new PaymentBean(null,null,3);
        $OrderBean = new OrderBean(null,null,3);
        $UserBean = new UserBean(null,null,3);

        $PaymentBean->setRetryTime(array(2,4,6));

        $this->setSubscribeBean(array($OrderBean,$PaymentBean,$UserBean));

    }

    function handlePaymentBean($msg){
        echo "im handlePaymentBean , ConsumerManyBean\n";
        throw new RejectMsgException();
    }

    function handleOrderBean($msg){
        echo "im handleOrderBean , ConsumerManyBean \n";
        throw new RetryException();
    }

    function handleUserBean($msg){
        echo "im handleUserBean\n , ConsumerManyBean";
    }
}

//$ConsumerOneBean = new ConsumerOneBean();
//$ConsumerOneBean->subscribe("normal");

$ConsumerManyBean =  new ConsumerManyBean();
$ConsumerManyBean->subscribe("many");
//异常处理
//$ConsumerOneBeanNoSetBean = new ConsumerOneBeanNoSetBean($conf);
//$ConsumerOneBeanNoHandle = new ConsumerOneBeanNoHandle($conf);

