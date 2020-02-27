<?php
include_once "./../../../vendor/autoload.php";

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Wire\AMQPTable;
use PhpAmqpLib\Message\AMQPMessage;


$conf =['host' => '127.0.0.1', 'port' => 5672, 'user' => 'root', 'pwd' => 'root', 'vhost' => '/',];
$conn = new AMQPStreamConnection( $conf['host'], $conf['port'], $conf['user'], $conf['pwd'], $conf['vhost']);

echo "connect rabbit config;".json_encode($conf) . "<br/>";

if(!$conn->isConnected()){
    exit("connect failed");
}

$channel = $conn->channel();





$clientReturnListener = function ($code,$errMsg,$exchange,$routingKey,$AMQPMessage){

    foreach (func_get_args() as $k=>$v) {
        var_dump($k);var_dump($v);
        echo "<br/>";
    }


    echo ("callback return:");
//    $info = "return error info:  code:$code , err_msg:$msg , body:".$AMQPMessage->body ."";
//   var_dump($info);
};

$ack = function (){
    echo "ack callback";
};


$channel->set_ack_handler($ack);
$channel->set_return_listener($clientReturnListener);
$channel->confirm_select();

$AMQPMessage = new AMQPMessage("aaaa");
$channel->basic_publish($AMQPMessage,"test.other","aaa",true);

$channel->wait_for_pending_acks_returns(100);
//$channel->wait_content(100);



//sleep(1);
exit;
