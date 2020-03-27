<?php
namespace Jy\Common\MsgQueue\MsgQueue;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Wire\AMQPTable;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitmqComposerLib{
    private $_conn = null;
    function connect($conf){
        $insist = false;
        $login_method = 'AMQPLAIN';
        $login_response = null;
        $locale = 'en_US';


        $connection_timeout = 3.0;
        $read_write_timeout = 5.0;
        $context = null;
        $keepAlive = false;
        $heartbeat = 0;


//        try{
        $conn = new AMQPStreamConnection( //建立生产者与mq之间的连接
            $conf['host'], $conf['port'], $conf['user'], $conf['pwd'], $conf['vhost'],
            $insist,$login_method,$login_response,$locale,
            $connection_timeout,$read_write_timeout,$context,$keepAlive,$heartbeat
        );
//        }catch (\Exception $e){
//            var_dump($e->getMessage());exit;
//        }

        $this->_conn = $conn;
        return $conn;
    }

    function isConnected(){
        return $this->_conn->isConnected();
    }

    function getChannel():AMQPChannel{
        return $this->_conn->channel();
    }

    function setBasicQos($num){
        return $this->getChannel()->basic_qos(null,$num,null);
    }

    function queueDeclare($queueName,$passive = false ,$durable,$exclusive = false ,$autoDelete,$nowait = false,$arguments){
        $table = null;
        if($arguments){
            $table = new AMQPTable($arguments);
        }

        return $this->getChannel()->queue_declare($queueName,$passive,$durable,$exclusive,$autoDelete,$nowait,$table);
    }

    //等待rabbitmq 返回内容
    function baseWait(){
        $this->getChannel()->wait_for_pending_acks_returns();
    }

    function consumerWait(){
        $this->getChannel()->wait();
    }

    function queueBind($queueName,$exchangeName,$routingKey,$nowait = false,$header){
        if($header){
            $header = new AMQPTable($header);
        }

        $this->getChannel()->queue_bind($queueName,$exchangeName,$routingKey,$nowait,$header);
    }
    function deleteQueue($queueName){
        $this->getChannel()->queue_delete($queueName);
    }

    function exchangeDeclare($exchangeName,$type,$arguments = null){
        $table = null;
        if($arguments) {
            $table = new AMQPTable($arguments);
        }
        $this->getChannel()->exchange_declare($exchangeName,$type,false,true,false,false,false,$table);
    }

    function unbindExchangeQueue($exchangeName,$queueName,$routingKey = "",$arguments = null){
        if($arguments){
            $arguments = new AMQPTable($arguments);
        }
        $this->_factoryType->queue_unbind($queueName,$exchangeName,$routingKey,$arguments);
    }

    function deleteExchange($exchangeName){
        $this->getChannel()->exchange_delete($exchangeName);
    }

    function confirmSelectMode(){
        $this->out("start confirm_select mode:");
        $this->getChannel()->confirm_select();
    }
    //开启一个事务
    function txSelect(){
        $this->out("txSelect");
        $this->getChannel()->tx_select();
    }

    function txCommit(){
        $this->out("txCommit");
        $this->getChannel()->tx_commit();
    }

    function txRollback(){
        $this->out("rollback");
        $this->getChannel()->tx_rollback();
    }

    function basicConsume($queueName,$consumerTag = "" ,$callback,$noAck = false){
        $this->getChannel()->basic_consume($queueName,$consumerTag,false,$noAck,false,false,$callback);
    }

    function listenerCancel($consumerTag){
        $this->getChannel()->basic_cancel($consumerTag);
    }

    function basicPublish($exchangeName,$routingKey,$msg,$arguments){
        if($arguments && isset($arguments['application_headers'] ) && $arguments['application_headers'] ){
            $arguments['application_headers'] = new AMQPTable(arguments['application_headers']);
        }
        $AMQPMessage = new AMQPMessage($msg,$arguments);
        return $this->getChannel()->basic_publish($AMQPMessage,$exchangeName,$routingKey,true);
    }

    function setRabbitmqAckCallback($ackFunc,$nackFuc){
        $this->getChannel()->set_nack_handler($ackFunc);
        $this->getChannel()->set_ack_handler($ackFunc);
    }

    function setRabbitmqErrCallback($clientReturnListener){
        $this->getChannel()->set_return_listener($clientReturnListener);
    }

    function ack($msg){
        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag'] );
    }

    function nack(){

    }

    function reject(){

    }

}