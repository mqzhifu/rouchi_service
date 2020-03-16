<?php

namespace Jy\Config\Config;

use Jy\Config\Contract\ConfigAbstract;
use Jy\Common\Helpers\ArrayHelper;


class Config extends  ConfigAbstract
{
    public function get($module = '', $key = '', $default = null)
    {
        if (!defined('ROUCHI_CONF_PATH')) {
            throw new \Exception('config path const : ROUCHI_CONF_PATH  没有定义');
        }

        $flag = mb_stripos($module, '@');
        if ($flag !== false && $flag == 0) {
            return $this->getAlias($module, $key);
        }

        $moduleFile = rtrim(ROUCHI_CONF_PATH, '/') .'/' . $module . ".php";

        if (!file_exists($moduleFile)) {
            return $default;
        }

        $res = require $moduleFile;

        return ArrayHelper::getItem((array) $res, $key, $default);
    }

    public function set($module = "", $key = "", $value = "")
    {
        //...
        return null;
    }

    public function getAlias($alias = '', $default = null)
    {
        $alias = mb_substr($alias, 1);
        $dotArr = explode('.', $alias);

        $flag = mb_stripos($alias, 'fram');
        if ($flag !== false && $flag == 0) {
            array_shift($dotArr);
            return $this->getFramConfig(implode('.', $dotArr), $default);
        }

        $tmp = $dotArr;

        $file = rtrim(ROUCHI_CONF_PATH, DIRECTORY_SEPARATOR);
        foreach ($dotArr as $val) {
            $file = $file.DIRECTORY_SEPARATOR.$val;
            $name = $file.'.php';
            array_shift($tmp);

            if (!file_exists($name)) continue;

            return ArrayHelper::getItem((array) require $name, implode('.', $tmp), $default);
        }

        return $default;
    }

    public function getFramConfig($path, $default = null)
    {
        $dotArr = explode('.', $path);
        $tmp = $dotArr;

        $file = __DIR__;
        foreach ($dotArr as $val) {
            $file = $file.DIRECTORY_SEPARATOR.$val;
            $name = $file.'.php';
            array_shift($tmp);

            if (!file_exists($name)) continue;

            return ArrayHelper::getItem((array) require $name, implode('.', $tmp), $default);
        }

        return $default;
    }
}
