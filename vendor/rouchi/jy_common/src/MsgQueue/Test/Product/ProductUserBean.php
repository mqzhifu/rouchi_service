<?php
namespace Jy\Common\MsgQueue\Test\Product;

use Jy\Common\MsgQueue\MsgQueue\MessageQueue;

class ProductUserBean extends MessageQueue{
    public $_id = 1;
    public $_nickName = "";
    public $_realName = "";
    public $_regTime = 0;
    public $_birthday = 0;

    function __construct($connectConf = null){
        parent::__construct($connectConf);
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