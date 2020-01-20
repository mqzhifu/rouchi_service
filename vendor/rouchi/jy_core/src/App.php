<?php

namespace Jy;

use Jy\Exception\JyException;

class App
{
    public static $app;

    public static $container;

    public static $checkFramework;

    public static function init()
    {
        static::$app = new static();

        static::initException();

        static::$container = new \Jy\Container();

        static::$container = new \Jy\Container();
        static::$checkFramework = new \Jy\Util\CheckFramework();

        // 常量 配置 todo
        //...
    }

    public static function run()
    {
        static::init();
        static::$checkFramework->check();
        static::$app->dispatcher->dispatcher();
    }

    public function __get($class)
    {
        $namespace = "\\Jy\\" . ucfirst($class);
        return static::$container->get($namespace);
    }

    public static function initException()
    {
        set_error_handler(['\Jy\App', 'handleError']);
        register_shutdown_function(['\Jy\App', 'handleFatalError']);
        set_exception_handler(['\Jy\App', 'exceptionError']);
    }

    public static function exceptionError($code, $message, $file = '', $line = 0)
    {
        // 如果用户注册了自己的异常接受类，则继续传递
        // 继承接口

        // 上下文获取response，并返回

        exit();
    }

    public static function handleError($code, $message, $file = '', $line = 0)
    {
        throw new JyException($code, $message);
    }

    /**
     * 异常结束捕获
     */
    public static function handleFatalError()
    {
        $error = error_get_last();

        throw new JyException(500, $error['message'] ?? 'sys error');
    }
}
