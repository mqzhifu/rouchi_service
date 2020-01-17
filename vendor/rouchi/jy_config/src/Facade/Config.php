<?php

namespace Jy\Config\Facade;

use \Jy\Config\Config\Config as CG;

class Config
{
    private static $instances;

    public static function getInstance()
    {
        if(self::$instances) return self::$instances;

        self::$instances = new CG();

        return self::$instances;
    }

    public static function __callStatic($name, $args)
    {
        if (is_callable(static::getInstance(), $name)) {
            throw new \Exception("method name :  ". $name. " not exists");
        }

        return static::getInstance()->$name(...$args);
    }

}
