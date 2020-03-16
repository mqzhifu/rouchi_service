<?php

namespace Jy\Redis\Redis;

use Jy\Redis\Contract\RedisAbstract;

class Redis extends  RedisAbstract
{

    private $handler;
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
        $this->connect();
    }

    public function connect()
    {
        $config = $this->config;
        $this->handler = new \Redis();
        if (isset($config['keep-alive']) && $config['keep-alive']) {
            $fd = $this->handler->pconnect($config['host'], $config['port'], 1800);
        } else {
            $fd = $this->handler->connect($config['host'], $config['port']);
        }
        if ($config["password"]) {
            $this->handler->auth($config["password"]);
        }
        if (!$fd) {
            throw new \Exception("redis 连接失败", [$config['host'], $config['port']]);
        }
    }

    public function __call($method, $arguments)
    {
        if (!$this->handler || !$this->beforeUse()){
            $this->connect();
        }

        return call_user_func_array([$this->handler, $method], $arguments);
    }

    private function beforeUse()
    {
        $ret = $this->handler->ping('hello');

        return $ret === 'hello' || stripos($ret, "PONG") !== false;
    }

}
