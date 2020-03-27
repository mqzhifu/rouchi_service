<?php
namespace Jy\Common\MsgQueue\Test\Product;
use Jy\Common\MsgQueue\MsgQueue\MessageQueue;

class QueueMinLengthBean extends MessageQueue{
    public $_id = 1;
    public $_orderId = 0;

    function __construct($conf = null,$provinder = 'rabbitmq',$debugMode = 0){
        parent::__construct($provinder,$conf,$debugMode);
    }
}