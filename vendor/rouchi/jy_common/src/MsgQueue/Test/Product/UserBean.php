<?php
namespace Jy\Common\MsgQueue\Test\Product;
use Jy\Common\MsgQueue\MsgQueue\MessageQueue;

class UserBean extends MessageQueue{
    public $_id = 1;
    public $_nickName = "";
    public $_realName = "";
    public $_regTime = 0;
    public $_birthday = 0;

    function __construct($conf = null,$provinder = 'rabbitmq'){
        parent::__construct($provinder,$conf,3);
    }

    function publishTx($bean){
        $this->transactionStart();
        try{
            $this->send( $bean );
            $this->transactionCommit();
        }catch (\Exception $e){
            $this->transactionCommit();
        }
    }

}