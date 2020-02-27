<?php


$url = getConfFile(__FILE__);
$origin_data = [];

if ($url) {
    $origin_data = require $url;
}else{
    $fileDir = explode(DIRECTORY_SEPARATOR,__FILE__ );
    $filename = array_pop($fileDir);
    die('conf配置:'.$filename .'加载失败!');
}


return $origin_data;
