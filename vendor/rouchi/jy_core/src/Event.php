<?php

namespace Jy;

// TODO 事件优先级: 触发层级限制, 全局事件、局部事件
class Event
{
    /**
     * 事件观察者
     * @var array
     */
    private static $monitors = [];// Context ...

    /**
     * 绑定事件
     * @param callable $method
     * @param $event
     * @param $alias  每次绑定都要指定一个别名，用于作为唯一值校验。  （同一个事件可以被多次绑定）
     * @return bool
     * @throws Exception
     */
    public static function bind($method, $event, $alias)
    {
        if (!is_callable($method)){
            throw new \Exception(5003, isset($method[1]) ? $method[1] : 'null');
        }

        self::$monitors[$event][$alias] = $method;
        return true;
    }

    /**
     * 解绑事件
     * @param $event
     * @param $alias  每次解绑都要指定一个别名，用于作为唯一值校验，同绑定逻辑
     * @return bool
     */
    public static function off($event, $alias)
    {
        if (isset(self::$monitors[$event][$alias])){
            unset(self::$monitors[$event][$alias]);
        }

        return true;
    }

    /**
     * 触发事件
     * @param $event
     * @param array $params
     * @param $alias 可以只触发某一个绑定的逻辑，通过别名指定，不传为空，则触发所有的绑定的
     * @return bool
     */
    public static function trigger($event, $params=[], $alias = '')
    {
        if (!isset(self::$monitors[$event])){
            return false;
        }

        foreach ($self::$monitors[$event] as $key => $value) {
            if (!empty($alias) && $key != $alias) continue;

            call_user_func_array($value, $params);
        }
        
        return true;
    }

    /**
     * 启动类
     */
    public static function init()
    {
    }
}
