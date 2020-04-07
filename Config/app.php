<?php

$url = getJyConfFile(__FILE__);

if ($url) {
    return require $url;
}else{
    $fileDir = explode(DIRECTORY_SEPARATOR,__FILE__ );
    $filename = array_pop($fileDir);
    die('conf配置:'.$filename .'加载失败!');
}
