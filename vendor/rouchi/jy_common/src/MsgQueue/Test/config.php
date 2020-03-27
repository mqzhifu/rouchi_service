<?php
define("ENV",'local');
//define("ENV",'test');
//define("ENV",'online');
//define("ENV",'self');

if(ENV == 'local'){
    $conf =['host' => '127.0.0.1', 'port' => 5672, 'user' => 'root', 'pwd' => 'root', 'vhost' => '/',];
    $exchangeName = "test.header.delay";
}elseif(ENV == 'test'){
    $conf =['host' => '172.19.113.249', 'port' => 5672, 'user' => 'root', 'pwd' => 'sfat324#43523dak&', 'vhost' => '/',];
//    $conf =['host' => '172.19.113.249', 'port' => 5672, 'user' => 'svc', 'pwd' => 'sfat324#85gsddhg7yd&', 'vhost' => '/',];
    $exchangeName = "test.header.delay";
}elseif(ENV == 'online'){
    $conf =['host' => '172.19.158.76', 'port' => 5672, 'user' => 'svc', 'pwd' => '85gsddhg7yd', 'vhost' => '/',];
    $exchangeName = "many.header.delay";
//    $exchangeName = "test.header.delay";
}elseif(ENV == 'self'){
    $conf =['host' => '39.107.127.244', 'port' => 5672, 'user' => 'admin', 'pwd' => '123456', 'vhost' => '/',];
    $exchangeName = "test.header.delay";
}else{
    exit("ENV error");
}


return $conf;