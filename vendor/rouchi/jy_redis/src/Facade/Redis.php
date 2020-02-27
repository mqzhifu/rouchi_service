<?php

namespace Jy\Redis\Facade;

use Jy\Redis\Redis\Redis as RD;
use Jy\Config\Facade\Config;

class Redis
{

    private static $instances = array();

    public static function getInstance($model = '', $name = '')
    {
        if (empty($model)) {
           $model = "database";
        }

        if (empty($name)) {
            $name = 'redis.default';
        }

        $key = $model . '.' . $name . '_master';

        if (isset(self::$instances[$key])) {
            return self::$instances[$key];
        }

        $config = Config::get($model, $name);

        if (empty($config)) {
            throw new \Exception('redis conf in database is empty. pls check redis conf');
        }

        self::$instances[$key] = new RD($config);

        return self::$instances[$key];
    }

    public static function __callStatic($name, $args)
    {
        if (is_callable(static::getInstance(), $name)) {
            throw new \Exception("method name :  ". $name. " not exists");
        }

        return static::getInstance()->$name(...$args);
    }
}
