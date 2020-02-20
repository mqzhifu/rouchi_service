<?php
namespace Jy\Common\MsgQueue\Test\Product;
use Jy\Common\MsgQueue\MsgQueue\MessageQueue;

class ProductUser extends MessageQueue {
    function __construct(){
        parent::__construct();
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

    function publishDelay($bean,$time){
        $rabbitHeader = array('x-delay'=>$time);//1秒
        $this->send( $bean ,null,$rabbitHeader);
    }
}