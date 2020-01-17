<?php

namespace Jy;

class Container
{
    private $_singletons = [];

    public function get($name)
    {
        if (isset($this->_singletons[$name])) {
            return $this->_singletons[$name];
        }

        // 依赖注入  todo
        // 别名机制 todo
        // classMap todo
        // 配置文件自动注入 todo
        $className = $name;
        if (!class_exists($className)) {
            throw new \Exception('class '. $name .' not exists');
        }

        if (method_exists($className, 'getInstance')) {
            $single = $className::getInstance();
        } else {
            $single = new $className();
        }

        $this->_singletons[$name] =  $single;

        return $single;
    }

    //public function built($class, $param = [], $config = []){}
    //public function set(){}
    public function has($class)
    {
        return isset($this->_singletons[$class]);
    }

}