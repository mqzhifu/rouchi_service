<?php
namespace Jy\Common\MsgQueue\Test\Product;
use Jy\Common\MsgQueue\MsgQueue\MessageQueue;

class SmsBean extends MessageQueue{
    public $_id = 1;
    public $_type = "";
    public $_msg = "";

    function __construct($conf = null,$provinder = 'rabbitmq',$debug = 0){
        parent::__construct($provinder,$conf,$debug);
    }

}