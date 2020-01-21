<?php

namespace Jy;

use Jy\Exception\JyException;
use Jy\Facade\Log;

class App
{
    public static $app;

    public static $container;

    public static $checkFramework;

    public static function init()
    {
        static::$app = new static();

        //static::initException();

        static::$container = new \Jy\Container();

        static::$container = new \Jy\Container();
        static::$checkFramework = new \Jy\Util\CheckFramework();

        // 常量 配置 todo
        //...
    }

    public static function run()
    {
        Log::info("APP start run...");
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

    public static function exceptionError(\Throwable $message)
    {
        // 如果用户注册了自己的异常接受类，则继续传递
        // 继承接口

        // 上下文获取response，并返回
        echo "<pre>";
        print_r([
            'ret' => $message->getLine(),
            'file' => $message->getFile(),
            'msg' => $message->getMessage(),
            'trace' => $message->getTrace(),
        ]);


        exit();
    }

    public static function handleError($code, $message, $file, $line)
    {
        $param = [
            'file' => $file,
            'line' => $line,
            'context' => $context
        ];
        throw new JyException($message, $code, $param);
    }

    /**
     * 异常结束捕获
     */
    public static function handleFatalError()
    {
        $error = error_get_last();
        //Logger
        // context destroy
    }
}
