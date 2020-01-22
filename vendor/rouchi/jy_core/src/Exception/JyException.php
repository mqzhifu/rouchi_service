<?php

namespace Jy\Exception;

use Jy\Facade\Log;

class JyException extends \ErrorException
{

    public function __construct($param)
    {
        try {
            // trigger event
            // 如果用户注册了自己的异常接受类，则继续传递
            // 检查继承接口

            $fromType = $param['from'] ?? 'sys';
            unset($param['from']);

            //echo "<pre>";
            //print_r($param);
            //$debug = debug_backtrace();
            //print_r($debug);

            ob_start();
            debug_print_backtrace();
            $content = ob_get_clean();
            $contetArr = explode("\n", $content);
            array_shift($contetArr);
            $content = implode("\n", $contetArr);

            echo new \Jy\JSONResponse(['code' => $param['type'], 'message' => $param['message'], 'data' => []]);

            Log::error($content);
        } catch (\Throwable $e) {

            echo new \Jy\JSONResponse([
                'code' => $this->getCode(),
                'message' => $thie->getMessage(),
                'data' => [
                    'trace' => $this->getTrace(),
                    'line' => $this->getLine(),
                ],
            ]);
        }

        exit();
    }

}
