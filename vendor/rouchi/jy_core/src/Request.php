<?php

namespace Jy;

use Jy\Util\InstanceTrait;

class Request
{

    use InstanceTrait;

    private $module;
    private $action=null;
    private $method=null;
    private $_hostInfo = null;
    private $_securePort = null;
    private $_port = null;
    private $_isSecure = null;

    private $params;
    private $posts;
    private $gets;
    private $args;
    private $jsons;

    public $methodParam = '_method';

    private function __construct()
    {
        list($this->module, $this->action) = \Jy\App::$app->router->getRouterInfo();

        $this->params = array_merge($_REQUEST, Router::$ARGS);
        $this->posts = $_POST;
        $this->gets = $_GET;
        $this->method = $this->getMethod();
    }

    public function getModule()
    {
        return $this->module;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getMethod()
    {
        if (
            isset($_POST[$this->methodParam])
            && !in_array(strtoupper($_POST[$this->methodParam]), ['GET', 'HEAD', 'OPTIONS'], true)
        ) {
            return strtoupper($_POST[$this->methodParam]);
        }

        if (isset($_SERVER['REQUEST_METHOD'])) {
            return strtoupper($_SERVER['REQUEST_METHOD']);
        }

        return 'GET';
    }

    public function getArgs()
    {
        if ($this->method == "GET") {
            return $_GET;
        }
        return $_POST;
    }

    public function __get($name)
    {
        return $this->getArgs()[$name] ?? null;
    }
}
