<?php
namespace Jy\Common\MsgQueue\MsgQueue;

class RabbitmqBean extends \Jy\Common\MsgQueue\MsgQueue\RabbitmqBase{
    private $_exchange = "test.header.delay";
    private $_header = null;
    private $_callbackUserAck = null;
    private $_callbackUserNAck = null;
    private $_callbackUserConsumer = null;
    private $_mode = 0;//0为普通模式 1为确认模式 2为事务模式，一但设置了确认模式或者事务模式就不能再变更，这两种模式是互斥的
    private $_modeDesc = array(0=>'普通模式',1=>'确认模式',2=>'事务模式');
    private $_redisMsgStatusManager = 0;//帮业务人员，开启消息一致性
    private $_roleDesc = array(1=>'product',2=>'consumer');
    private $_role = 0;
    private $_childClassName = "";
    private $_consumerQos = 0;

    function __construct(){
        parent::__construct();
        $this->regDefaultAllCallback();
    }

    function setRole(int $roleId){
        $this->_role = $roleId;
    }

    function setClassFlag($flag = false){
        if($flag){
            $this->_childClassName = $flag;
        }else{
            $this->_childClassName = get_called_class();
        }
    }

    function setDefaultHeader(){
        $this->_header = array($this->_childClassName=>$this->_childClassName,"x-match"=>'any');
    }

    function  transactionStart(){
        $this->getChannel()->tx_select();
    }

    function  transactionCommit(){
        $this->getChannel()->tx_commit();
    }

    function  transactionRollback(){
        $this->getChannel()->tx_rollback();
    }
    //setExchangeName
    function setTopicName($name){
        $this->_exchange = $name;
    }

    //发送一条消息给路由器
    //$msgBody:发送消息体，可为json object string
    //$arguments:对消息体的一些属性约束
    //$header:主要是发送延迟队列时，使用
    function send($msgBody,$arguments = null,$header = null){
        if(!$msgBody){
            $this->throwException("msgBody is null");
        }

        if(is_bool($msgBody)){
            $this->throwException("msgBody is bool");
        }

        $msgId = $this->createUniqueMsgId();
        if($arguments){
            if(isset($arguments['message_id']) && $arguments['message_id']){
                $this->throwException("message_id key value: must null ");
            }

            if(isset($arguments['type']) && $arguments['type']){
                $this->throwException("type key value: must null ");
            }

            $arguments['message_id'] = $msgId;
        }else{
            $arguments = array( "message_id"=>$msgId);
        }

        $arguments['timestamp'] = time();
        $arguments['delivery_mode'] = 2;

        if($this->_mode == 1){//确认模式
            $arguments['type'] = "confirm";
//            if($this->_redisMsgStatusManager){
//                RedisMsgStatusAck::redisSetMsgStatusAck($msgId,RedisMsgStatusAck::$_statusProductWait);
//            }
        }elseif($this->_mode == 2){//事务模式
            $arguments['type'] = "tx";
        }else{
            $arguments['type'] = "normal";
        }

        if(is_object($msgBody)){
            if(!$arguments){
                $arguments = ['content_type'=>'application/serialize'];
            }else{
                $arguments['content_type'] = 'application/serialize';
            }
            $msgBody = serialize($msgBody);
        }
        elseif(is_array($msgBody) ){
            if(!$arguments){
                $arguments = ['content_type'=>'application/json'];
            }else{
                $arguments['content_type'] = 'application/json';
            }
            $msgBody = json_encode($msgBody);
        }
        //主要是给，延迟队列
        $rabbitHeader = $this->_header;
        if($header ){
            $rabbitHeader = array_merge($rabbitHeader,$header);
        }

        $publishServerRs = $this->publish($msgBody,$this->_exchange,"",$rabbitHeader,$arguments);
        if($this->_mode == 1){
            $this->waitAck();
        }
    }


    function setMode(int $mode){
        if(!in_array($mode,array_flip($this->_modeDesc))){
            $this->throwException(" mode value is error.");
        }

        if($this->_mode && $this->_mode != $mode){
            $this->throwException("confirm mode or  tx mode just have use one,is mutex -1");
        }

        if( $this->_mode == $mode){
            return true;
        }

        if($mode == 1){
            $this->confirmSelectMode();
        }

        $this->_mode = $mode;
    }


    function regUserCallbackAck($callback){
        $this->_callbackUserAck = $callback;
    }
    function regUserCallBackNAck($callback){
        $this->_callbackUserNAck = $callback;
    }

    function callbackUser($callback,$argc){
        if($callback){
            return call_user_func($callback,$argc);
        }
    }

    function regDefaultAllCallback(){
        $this->out("regDefaultAllCallback : ack n-ack return_listener");
        $clientAck = function ($AMQPMessage){
            $this->out("callback ack info:",0);
            $body = RabbitmqBase::getBody($AMQPMessage);
            $attr = RabbitmqBase::getReceiveAttr($AMQPMessage);
            $info = RabbitmqBase::debugMergeInfo($attr);


//            if($this->_mode == 1){
//                if($this->_redisMsgStatusManager){
//                    $msgStatus = RedisMsgStatusAck::redisGetMsgStatusAck($attr['message_id']);
//                    RedisMsgStatusAck::redisSetMsgStatusAck($attr['message_id'],RedisMsgStatusAck::$_statusProductOk);
//                    $info = " sendStatus:".$msgStatus['status'] . " sendTime:".date("Y-m-d H:i:s",$msgStatus['time']);
//                    $this->out($info);
//                }
//            }

            $recall = array("AMQPMessage"=>$AMQPMessage,'body'=>$body,'attr'=>$attr);
            $this->callbackUser($this->_callbackUserAck,$recall);

            if(is_array($body)){
                $body = json_encode($body);
            }elseif(is_object($body)){
                $body = serialize($body);
            }

            $this->out(" body : body ".($body) . " , $info");
            return true;
        };

        $clientNAck = function ($AMQPMessage){
            $this->out("callback N-ack info:",0);
            $body = RabbitmqBase::getBody($AMQPMessage);
            $attr = RabbitmqBase::getReceiveAttr($AMQPMessage);
            $info = RabbitmqBase::debugMergeInfo($attr);


            $recall = array("AMQPMessage"=>$AMQPMessage,'body'=>$body,'attr'=>$attr);
            $this->callbackUser($this->_callbackUserNAck,$recall);

            $this->out(" body : $body ".json_encode($body) . " , $info");
            $this->throwException($info);
            return true;
        };

        $clientReturnListener = function ($code,$msg,$file,$aaa,$AMQPMessage){
            $this->out("callback return:");
            $info = "return error info:  code:$code , err_msg:$msg , body:".$AMQPMessage->body ."";
            $this->out($info);
            $this->throwException($info);
        };

        $this->getChannel()->set_return_listener($clientReturnListener);
        $this->getChannel()->set_nack_handler($clientNAck);
        $this->getChannel()->set_ack_handler($clientAck);
    }

    function groupSubscribe($userCallback,$consumerTag = "",$autoDel = false,$durable = true,$noAck =false){
        if(!$consumerTag){
            $this->throwException(" consumerName is null");
        }
        $queueName = $this->_childClassName . "_".$consumerTag;
        if(!$this->queueExist($queueName)){
            $this->createQueue($queueName,null,$durable,$autoDel);
        }

        $this->setBindQueue($queueName,$this->_exchange,null,$this->_header);
        $this->baseSubscribe($this->_exchange,$queueName,$consumerTag,$userCallback,$noAck);
        $this->startListenerWait();
    }

    //consumer  =====================================================

    function setHeader(){

    }

    function setQueueName(){

    }

    function createQueue($queueName,$arguments,$durable,$autoDel){
        $this->setQueue($queueName,$arguments,$durable,$autoDel);
    }

    function createTopic(){

    }

    function setBasicQos(int $num){
        $this->_consumerQos = $num;
        $this->getChannel()->basic_qos(null,$num,null);
    }

    function setBindQueue($queueName,$exchange,$routingKey,$header){
        return $this->bindQueue($queueName,$exchange,$routingKey,$header);
    }

    function setListenerBean($beanName,$callback){
        $this->_header[$beanName] = $beanName;
        $this->_bean[$beanName] = $callback;
    }

    function mappingBeanCallbackSwitch(){

    }

    function subscribe($queueName, $consumerTag = "",$noAck = false){
        $consumerCallback = function($recall) use ($noAck){
            if (!$noAck) {
                $this->mappingBeanCallbackSwitch($recall);
            }
        };

        $this->bindQueue($queueName,$this->_exchange,null,$this->_header);
        if(!$consumerTag){
            $consumerTag = $queueName .__CLASS__;
        }

        $this->baseSubscribe($this->_exchange,$queueName, $consumerTag, $consumerCallback,$noAck);
        $this->startListenerWait();
    }

    function waitAckReturnListener(){
        $this->waitAck();
        $this->waitReturnListener();
    }

    function waitAck(){
        $this->getChannel()->wait_for_pending_acks(100);
    }

    function waitReturnListener(){
        $this->getChannel()->wait_for_pending_acks_returns(100);
    }
}