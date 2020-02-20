<?php

namespace Jy;

use Jy\Util\InstanceTrait;
use Jy\Common\RequestContext\RequestContext;

class Request
{

    use InstanceTrait;

    private function __construct()
    {
        //.
    }

    public function getModule()
    {
        return \Jy\App::$app->router->getRequestController();
    }

    public function getAction()
    {
        return \Jy\App::$app->router->getRequestAction();
    }

    public function getVersion()
    {
        return \Jy\App::$app->router->getRequestVersion();
    }

    public function getProtocol()
    {
        return \Jy\App::$app->router->getRequestProtocol();
    }

    public function getMethod()
    {
        if ($this->getProtocol() === 'rpc') return "RPC";
        if ($this->getProtocol() === 'cli') return "CLI";

        if (isset($_SERVER['REQUEST_METHOD'])) {
            return strtoupper($_SERVER['REQUEST_METHOD']);
        }

        return 'GET';
    }

    public function getArgs()
    {
        return $this->getUserData();
    }

    public function getUserData()
    {
        return isset($this->getRequestParams()['user_data']) ? $this->getRequestParams()['user_data'] : $this->getRequestParams();
    }

    public function getRequestParams()
    {
        if (RequestContext::has('request_data')) return RequestContext::get('request_data');

        if ($this->getMethod() == 'GET') {
            return $_GET ?? [];
        } else if ($this->getMethod() == "RPC") {
            // rpc todo
            return [];
        } else if ($this->getMethod() == "CLI") {
            global $argc;
            global $argv;
            return $argc > 2 ? array_slice($argv, 2) : [];
        }

        return $_POST ?? [];
    }

    public function getRequestSysParams()
    {
        return isset($this->getRequestParams()['sys_data']) ? $this->getRequestParams()['sys_data'] : [];
    }

    public function getRequestTraceParams()
    {
        return isset($this->getRequestParams()['trace_cs_data']) ? $this->getRequestParams()['trace_cs_data'] : [];
    }

    public function __get($name)
    {
        return $this->getArgs()[$name] ?? null;
    }
}
