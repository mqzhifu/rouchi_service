<?php

namespace Jy;

class Router
{

    public static $ARGS = [];

    public $version;
    public $handle;

    public function getRouterInfo()
    {
        if (preg_match("/cli/i", php_sapi_name())) return ['Index', 'index'];

        $pathRoot = strpos($_SERVER['REQUEST_URI'], '?') ? strstr($_SERVER['REQUEST_URI'], '?', true) : $_SERVER['REQUEST_URI'];
        if (substr($pathRoot, -9) === 'index.php'){
            $pathRoot = substr($pathRoot, 0, -9);
        }

        $pathArr = explode('/', trim($pathRoot, '/'));
        $pathArr = array_filter($pathArr);
        $this->version = array_shift($pathArr);
        $action = array_pop($pathArr);

        $tmp = array_map(function($value){
           return ($value);
        }, $pathArr);

        $controller = implode("/", $tmp);

        return [$controller, $action, $this->version];
    }

    public function getRequestVersion()
    {
        if (empty($this->version))  $this->getRouterInfo();

        return $this->version ?? 'V1';
    }

}
