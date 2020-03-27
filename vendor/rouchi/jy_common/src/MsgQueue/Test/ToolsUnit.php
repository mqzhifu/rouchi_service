<?php
namespace Jy\Common\MsgQueue\Test;
use Jy\Common\MsgQueue\MsgQueue\MessageQueue;
use Jy\Common\MsgQueue\Test\Tools;

function out($msg ,$br = 1){
    if(is_object($msg) || is_array($msg)){
        $msg = json_encode($msg);
    }
    if($br){
        if (preg_match("/cli/i", php_sapi_name())){
            echo $msg . "\n";
        }else{
            echo $msg . "<br/>";
        }
    }else{
        echo $msg;
    }
}

class ToolsUnit extends  MessageQueue{
    function __construct($conf = null,$provinder = 'rabbitmq'){
        parent::__construct($provinder,$conf,3);
        $this->setMode(1);
        $this->regUserCallbackAck(array($this,'ackHandle'));
    }

    function clearAll(){
        $TestConfig = new Tools($this->getProvider());
        $TestConfig->clearAll();
    }

    function createCaseAndDelOldCase($pid,$isDel = 1){
        $TestConfig = new Tools($this->getProvider());
        $TestConfig->setProjectId($pid);

        if($isDel){
            $TestConfig->clearByProject($pid);
        }


        $TestConfig->initProjectExchangeQueue($pid);
        exit;
    }

    static function apiCurlQueueInfo($user,$password,$host,$vhost,$queueName){
        $curl = curl_init();

        $url = "http://".$host . "/api/queues/$vhost/$queueName";
        $header[] = "Content-Type:application/json";
        $header[] = "Authorization: Basic ".base64_encode("$user:$password"); //添加头，在name和pass处填写对应账号密码

        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_URL, $url);


        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $output = curl_exec($curl);
        $data = json_decode($output,"true");
        $returnData = array(
            'backing_queue_status_len'=>$data['backing_queue_status']['len'],
            'messages_ready'=>$data['messages_ready'],
            'messages'=>$data['messages'],
        );

        return $returnData;
    }
}