<?php
namespace Jy\Common\MsgQueue\Test\Product;
use Jy\Common\MsgQueue\MsgQueue\MessageQueue;

class ProductSms extends MessageQueue {
    function __construct()
    {
        parent::__construct();

//        $userCallback = function ($recall){
//            echo "im in user callback func \n";
//        };
//        $productSms->groupSubscribe($userCallback,"dept_A");
//        exit;
    }

    function subscribeSelf(){

    }
}