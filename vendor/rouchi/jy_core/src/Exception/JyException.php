<?php

namespace Jy\Exception;


class JyException extends \ErrorException
{

    public function __construct($msg, $code, $param = [])
    {
        try {
            // trigger event
            //.
        } catch (\Exception $e) {
            echo "<b>Fatal error</b>: Endless loop <b>{$this->getFile()}</b>:<b>{$this->getLine()}</b>\nStack trace:\n{$this->getTraceAsString()}";
        }

        exit();
    }

}
