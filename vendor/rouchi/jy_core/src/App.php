<?php

namespace Jy;

use Jy\Exception\JyException;
use Jy\Facade\Log;
use Jy\Common\RequestContext\RequestContext;
use Jy\Facade\Trace;

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

        static::$checkFramework = new \Jy\Util\CheckFramework();

        // 上下文数据的初始化
        static::initRequestContext();

        // tracing
        Trace::setServiceReceiveTrace();

        // 常量 配置 todo
        //...
    }

    public static function run()
    {
        Log::info("APP start run...");

        date_default_timezone_set('PRC');

        RequestContext::create();

        static::init();

        static::$checkFramework->check();

        static::$app->dispatcher->dispatcher();
    }

    public static function initRequestContext()
    {

        if (!RequestContext::isInSwooleCoroutine()) {
            // data pre deal
            RequestContext::put('request_user_data', \Jy\App::$app->request->getUserData());
            RequestContext::put('request_data', \Jy\App::$app->request->getRequestParams());
            RequestContext::put('request_sys_params', \Jy\App::$app->request->getRequestSysParams());
            RequestContext::put('request_trace_cs_data', \Jy\App::$app->request->getRequestTraceParams());

            unset($_GET);
            unset($_POST);
            unset($_REQUEST);
        }

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
            'e' => $message
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
            'e' => new \ErrorException($message, $code, E_ERROR, $file, $line),
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
        $param['e'] = new \ErrorException($param['message'], $param['type'], E_ERROR, $param['file'], $param['line']);

        throw new JyException($param);
    }
}
