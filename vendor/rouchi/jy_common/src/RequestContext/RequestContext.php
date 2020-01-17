<?php

namespace Jy\Common\RequestContext;

use Jy\Common\Contract\Context\RequestContextAbstract;


/**
 * @file RequestContext.php
 * 请求上下文
 * @author jingzhiheng
 * @version v1
 * @date 2020-01-15
 */


/**
 * @method static int get($key, $default = null, $uid = null)
 * @method static int put($key, $val, $uid = null)
 * @method static int multiPut($data, $uid = null)
 * @method static int del($key, $uid = null)
 * @method static int multiDel($keys, $uid = null)
 * @method static int currency()
 * @method static int has($key, $uid = null)
 * @method static int hasContext($uid)
 * @method static int isInSwooleCoroutine()
 * @method static int create()
 * @method static int getUniqKey()
 * @method static int destroy($uid)
 */
class RequestContext extends RequestContextAbstract
{

    public static function multiPut(array $data, $uid = null)
    {
        foreach ($data as $key => $val) {
            static::put($key, $val, $uid);
        }
        return true;
    }

    public static function multiDel(array $data, $uid = null)
    {
        foreach($data as $val) {
            static::del($val, $uid);
        }
        return true;
    }


}
