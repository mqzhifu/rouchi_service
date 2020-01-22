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

        static::initException();

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
        set_error_handler(['\Jy\App', 'errorHandle']);
        register_shutdown_function(['\Jy\App', 'shutdownHandle']);
        set_exception_handler(['\Jy\App', 'exceptionHandle']);
    }

    public static function exceptionHandle(\Throwable $message)
    {
        $param = [
            'from' => __METHOD__,
            'type' => $message->getCode(),
            'file' => $message->getFile(),
            'line' => $message->getLine(),
            'message' => $message->getMessage(),
            'trace' => $message->getTrace(),
        ];

        throw new JyException($param);
    }

    public static function errorHandle($code, $message, $file, $line)
    {
        $param = [
            'from' => __METHOD__,
            'type' => $code,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'trace' => [],
        ];

        throw new JyException($param);
    }

    /**
     * 异常结束捕获
     */
    public static function shutdownHandle()
    {
        $errorArr = error_get_last();
        error_clear_last();
        if (empty($errorArr)) return true;
        $param = [
            'from' => __METHOD__,
            'type' => $errorArr['type'] ?? -1,
            'message' => $errorArr['message'] ?? 'jy sys error',
            'file' => $errorArr['file'] ?? __FILE__,
            'line' => $errorArr['line'] ?? __LINE__,
            'trace' => [],
        ];

        throw new JyException($param);
    }
}
