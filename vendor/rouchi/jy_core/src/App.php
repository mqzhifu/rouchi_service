<?php

namespace Jy;

class App
{
    public static $app;

    public static $container;

    public static $checkFramework;

    public static function init()
    {
        static::$app = new static();
        static::$container = new \Jy\Container();
        static::$checkFramework = new \Jy\Util\CheckFramework();
        //set_error_handler(['\Jy\App', 'handleError']);
        //register_shutdown_function(['\Jy\App', 'handleFatalError']);
        //set_exception_handler(['\Jy\App', 'exceptionError']);

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

    public static function exceptionError($code, $message, $file = '', $line = 0)
    {

        throw new JyException($code, $message);
        return;
    }

    public static function handleError($code, $message, $file = '', $line = 0)
    {
        throw new JyException($code, $message);
        return;
    }

    /**
     * 异常结束捕获
     */
    public static function handleFatalError()
    {
        $error = error_get_last();

        if (isset($error['type']) && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING])) {
            throw new JyException(1000, $error['message']);
        }
    }
}
