<?php
namespace Jy\Common\MsgQueue\MsgQueue;
use Jy\Common\MsgQueue\MsgQueue\MessageQueue;

class RabbitmqListenManyBean extends MessageQueue{

    private $_ignoreSendNoQueueReceive = true;
    private $_ignoreSendNoQueueReceivePool = [];

    function __construct( ){
        parent::__construct();
    }

    function setSubscribeBeanClass(array $beansClass){
        $this->_listenManyBeanType = 2;
        $class = [];
        $callbackFunc = [];
        foreach ($beansClass as $k=>$v) {
            $class[] = new $k();
            $callbackFunc[] = $v;
        }

        $this->setSubscribeBean($class,$callbackFunc);
    }

    function subscribeMany($consumerTag){
        $this->_listenManyBeanType = 2;
        $this->subscribe($consumerTag);
    }


}