<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/1/3
 * @version : 1.0
 * @file : SingletonTrait.php
 * @desc :
 */

namespace Jy\Common\Singleton;


trait SingletonTrait
{
    protected static $instance = null;

    protected function __construct(){}

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public function __clone()
    {
        return self::$instance;
    }
}