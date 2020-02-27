<?php
namespace Rouchi\Console\Mq;

use Rouchi\Product\OrderBean;
class Consumer{
    public function server(){
        $OrderBean = new OrderBean();
        $callback = array($this,'serverCallbackHandle');
        $OrderBean->groupSubscribe($callback,"testServer");
    }

    function serverCallbackHandle($data){
        echo "im in serverCallbackHandle";
    }

    function index(){
        echo 111;
    }
}