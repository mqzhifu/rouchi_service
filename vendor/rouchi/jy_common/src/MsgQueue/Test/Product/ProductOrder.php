<?php
namespace Jy\Common\MsgQueue\Test\Product;
use Jy\Common\MsgQueue\MsgQueue\MessageQueue;

class ProductOrder extends MessageQueue {
    function __construct(){
        parent::__construct();
        $this->setMode(1);
        $this->regUserCallbackAck(array($this,'ackHandle'));
    }

    function ackHandle($data){
        echo "order receive rabbitmq server callback ack info.";
    }
}