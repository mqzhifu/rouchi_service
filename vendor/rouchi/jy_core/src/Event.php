<?php

namespace Jy;

class Event
{
    /**
     * 事件句柄
     * @var int
     */
    private static $fh = 0;

    /**
     * 事件观察者
     * @var array
     */
    private static $monitors = [];


    /**
     * 绑定事件
     * @param callable $method
     * @param $event
     * @param null $times
     * @return int
     * @throws BinyException
     */
    public static function bind($method, $event, $times=null)
    {
        if (!is_callable($method)){
            throw new \Exception(5003, isset($method[1]) ? $method[1] : 'null');
        }
        $fh = ++self::$fh;
        self::$monitors[$event][$fh] = ['m'=>$method, 't'=>$times];
        return $fh;
    }

    /**
     * 绑定永久事件
     * @param callable $method
     * @param $event
     * @return int
     */
    public static function on($event, $method=null)
    {
        $method = $method ?: [Logger::instance(), 'event'];
        return self::bind($method, $event);
    }

    /**
     * 绑定一次事件
     * @param callable $method
     * @param $event
     * @return int
     */
    public static function one($event, $method=null)
    {
        $method = $method ?: [Logger::instance(), 'event'];
        return self::bind($method, $event, 1);
    }

    /**
     * 解绑事件
     * @param $event
     * @param $fh
     * @return bool
     */
    public static function off($event, $fh=null)
    {
        if ($fh){
            if (isset(self::$monitors[$event][$fh])){
                unset(self::$monitors[$event][$fh]);
                return true;
            } else {
                return false;
            }
        } else {
            unset(self::$monitors[$event]);
            return true;
        }
    }

    /**
     * 触发事件
     * @param $event
     * @param array $params
     * @return bool
     */
    public static function trigger($event, $params=[])
    {
        if (!isset(self::$monitors[$event])){
            return false;
        }
        array_unshift($params, $event);
        foreach (self::$monitors[$event] as $fh => &$value){
            $method = $value['m'];
            call_user_func_array($method, $params);
            if (isset($value['t']) && --$value['t'] <= 0){
                unset(self::$monitors[$event][$fh]);
            }
        }
        unset($value);
        return true;
    }

    /**
     * 启动类
     */
    public static function init()
    {
    }
}
