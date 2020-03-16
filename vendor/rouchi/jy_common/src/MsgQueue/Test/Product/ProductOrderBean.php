<?php
namespace Jy\Common\MsgQueue\Test\Product;

use Jy\Common\MsgQueue\MsgQueue\MessageQueue;

class ProductOrderBean extends MessageQueue{
    public $_id = 1;
    public $_channel = "tencent";//来源渠道
    public $_price = 0.00;//金额
    public $_num = 0;//购买数量
    public $_uid = 0;//用户ID

    function __construct($connectConf = null){
        parent::__construct($connectConf);
        $this->setMode(1);
        $this->regUserCallbackAck(array($this,'ackHandle'));
    }

    function ackHandle($data){
        echo "order receive rabbitmq server callback ack info.";
    }
}