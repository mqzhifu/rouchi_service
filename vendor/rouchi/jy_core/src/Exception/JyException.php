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

            ob_start();
            debug_print_backtrace();
            $content = ob_get_clean();

            if( strpos( PHP_OS ,"WIN" ) !== false){
                $logContent = "";
                $a = explode(",",$content);
                foreach ($a as $k=>$v) {
                    $logContent .= $v .PHP_EOL;
                }
            }else{
                $contetArr = explode("\n", $content);
                array_shift($contetArr);
                $logContent = implode("\n", $contetArr);
            }

            echo new \Jy\JSONResponse(['code' => $param['type'], 'message' => $param['message'], 'data' => []]);

            Log::error($logContent);
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
