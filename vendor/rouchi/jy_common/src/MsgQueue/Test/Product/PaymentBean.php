<?php
namespace Jy\Common\MsgQueue\Test\Product;
use Jy\Common\MsgQueue\MsgQueue\MessageQueue;

class PaymentBean extends MessageQueue{
    public $_id = 1;
    public $_type = "";
    public $_orderId = 0;

    function __construct($conf = null,$provinder = 'rabbitmq'){
        parent::__construct($provinder,$conf,3);
    }

}