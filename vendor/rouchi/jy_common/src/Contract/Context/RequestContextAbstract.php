<?php

namespace Jy\Common\Contract\Context;


use Jy\Common\Helpers\ArrayHelper;

/**
 * @file RequestContextAbstract.php
 * 请求上下文的抽象类
 * @author jingzhiheng
 * @version v1
 * @date 2020-01-14
 */

abstract class RequestContextAbstract implements RequestContextInterface
{

    protected static $pool = [];

    abstract public static function multiPut(array $data, $uid = null);
    abstract public static function multiDel(array $data, $uid = null);

    public static function get($key, $default = null, $uid = null)
    {
        if ($uid == null) {
            $uid = static::currency();
        }

        if (!static::hasContext($uid))  return $default;

        if (static::has($key, $uid)) {
            return  ArrayHelper::getItem(static::$pool[$uid], $key, $default);
        }

        return $default;
    }

    public static function put($key, $val, $uid = null)
    {
        if ($uid == null) {
            $uid = static::currency();
        }

        if (!static::hasContext($uid))  static::$pool[$uid] = [];

        return ArrayHelper::setItem(static::$pool[$uid], $key, $val);
    }

    public static function del($key, $uid = null)
    {
        if ($uid == null) {
            $uid = static::currency();
        }

        if (!static::hasContext($uid))  return true;

        return ArrayHelper::delItem(static::$pool[$uid], $key);
    }

    public static function currency()
    {
        if (static::isInSwooleCoroutine()) return static::getUniqKey();

        return defined('JY_REQUEST_UNIQ_ID') ? JY_REQUEST_UNIQ_ID : -1;
    }

    public static function has($key, $uid = null):bool
    {
        if ($uid == null) {
            $uid = static::currency();
        }

        if (!static::hasContext($uid))  return false;

        return ArrayHelper::hasItem(static::$pool[$uid], $key);
    }

    public static function hasContext($uid):bool
    {
        return isset(static::$pool[$uid]);
    }

    public static function isInSwooleCoroutine()
    {
        return extension_loaded('swoole') && \Swoole\Coroutine::getCid() != -1;
    }

    /**
     * 创建一个上下文
     *
     * 分布式+多进程
     * @return mix
     */
    public static function create()
    {

        if (static::isInSwooleCoroutine()) return static::getUniqKey();

        if (defined('JY_REQUEST_UNIQ_ID')) return JY_REQUEST_UNIQ_ID;

        define('JY_REQUEST_UNIQ_ID', static::getUniqKey());

        return JY_REQUEST_UNIQ_ID;
    }

    public static function getUniqKey()
    {
        $pid = getmypid();

        $mac = 'mac';

        if (static::isInSwooleCoroutine()) {
            $uniqid = \Swoole\Coroutine::getCid();
        } else {
            $uniqid = uniqid();
        }

        return "mac_".$mac."_pid_". $pid."_uniq_".$uniqid;
    }

    public static function destroy($uid = null):void
    {
        if ($uid == null) {
            $uid = static::currency();
        }

        if (static::hasContext($uid)) {

            unset(static::$pool[$uid]);
        }
    }
}
