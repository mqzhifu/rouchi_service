<?php

namespace Jy\Common\Helpers;



/**
 * @file ArrayHelper.php
 * 数组的工具类
 * @author jingzhiheng
 * @version v1
 * @date 2020-01-14
 */

class ArrayHelper
{

    /**
     * 获取数组中的元素，支持深维度直接获取，机：key可以时点号隔开的形式 eg：order.iterm.id
     *
     * @param $arr
     * @param $key
     * @param $default
     *
     * @return mix
     */
    public static  function getItem(array $arr, $key, $default = null)
    {
        $keyItem = explode(".", $key);
        $firstKey  = array_shift($keyItem);

        if (!empty($keyItem) && isset($arr[$firstKey])) {
            return static::getItem($arr[$firstKey], implode(".", $keyItem));
        }

        return $arr[$firstKey] ?? $default;
    }

    public static  function hasItem(array $arr, $key):bool
    {
        $keyItem = explode(".", $key);
        $firstKey  = array_shift($keyItem);

        if (!empty($keyItem) && isset($arr[$firstKey])) {
            return static::hasItem($arr[$firstKey], implode(".", $keyItem));
        }

        return isset($arr[$firstKey]);
    }


    public static  function setItem(array &$arr, $key, $val):bool
    {
        $keyItem = explode(".", $key);
        $firstKey  = array_shift($keyItem);

        if (!empty($keyItem)) {
            !isset($arr[$firstKey]) && $arr[$firstKey] = [];
            return static::setItem($arr[$firstKey], implode(".", $keyItem), $val);
        }

        $arr[$firstKey] = $val;

        return true;
    }

    public static  function delItem(array &$arr, $key):bool
    {
        $keyItem = explode(".", $key);
        $firstKey  = array_shift($keyItem);

        if (!empty($keyItem) && isset($arr[$firstKey])) {
            return static::delItem($arr[$firstKey], implode(".", $keyItem));
        }

        if(isset($arr[$firstKey])) unset($arr[$firstKey]);

        return true;
    }

}
