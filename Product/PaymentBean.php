<?php
namespace Rouchi\Product;
use Jy\Common\MsgQueue\MsgQueue\MessageQueue;

class PaymentBean extends MessageQueue{
    public $_id = 1;
    public $_price = "";

    function __construct(){
        parent::__construct();
        $this->setMode(1);
        $this->regUserCallbackAck(array($this,'ackHandle'));
    }

    function ackHandle($data){
        echo "PaymentBean receive rabbitmq server callback ack info. end<br/>";
    }
}