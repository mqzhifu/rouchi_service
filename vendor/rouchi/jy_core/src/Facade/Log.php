<?php

namespace Jy\Facade;

use Jy\Log\Facades\Log as LG;

/**
 * @method static int emergency($message ,array $context = array())
 * @method static int alert($message ,array $context = array())
 * @method static int critical($message ,array $context = array())
 * @method static int error($message ,array $context = array())
 * @method static int warning($message ,array $context = array())
 * @method static int notice($message ,array $context = array())
 * @method static int info($message ,array $context = array())
 * @method static int debug($message ,array $context = array())
 * @method static int log($level,$message ,array $context = array())
 */
class Log
{

    public static function getInstance()
    {
        if (!defined('ROUCHI_LOG_PATH')) throw new \Exception("log path config const : ROUCHI_LOG_PATH  not exists");

        return LG::getInstance()->init('_path', ROUCHI_LOG_PATH);
    }

    public static function __callStatic($name, $args)
    {
        if (is_callable(static::getInstance(), $name)) {
            throw new \Exception("method name :  ". $name. " not exists");
        }

        return static::getInstance()->$name(...$args);
    }

}
