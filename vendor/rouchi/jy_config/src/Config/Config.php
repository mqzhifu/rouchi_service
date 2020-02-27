<?php

namespace Jy\Config\Config;

use Jy\Config\Contract\ConfigAbstract;
use Jy\Common\Helpers\ArrayHelper;


class Config extends  ConfigAbstract
{
    public function get($module = '', $key = '')
    {
        if (!defined('ROUCHI_CONF_PATH')) {
            throw new \Exception('config path const : ROUCHI_CONF_PATH  没有定义');
        }

        $flag = mb_stripos($module, '@');
        if ($flag !== false && $flag == 0) {
            return $this->getAlias($module);
        }

        $moduleFile = rtrim(ROUCHI_CONF_PATH, '/') .'/' . $module . ".php";

        if (!file_exists($moduleFile)) {
            return [];
        }

        $res = require $moduleFile;

        return ArrayHelper::getItem((array) $res, $key, []);
    }

    public function set($module = "", $key = "", $value = "")
    {
        //...
        return null;
    }

    public function getAlias($alias = '')
    {
        $alias = mb_substr($alias, 1);
        $dotArr = explode('.', $alias);
        $tmp = $dotArr;

        $file = rtrim(ROUCHI_CONF_PATH, DIRECTORY_SEPARATOR);
        foreach ($dotArr as $val) {
            $file = $file.DIRECTORY_SEPARATOR.$val;
            $name = $file.'.php';
            array_shift($tmp);

            if (!file_exists($name)) continue;

            return ArrayHelper::getItem((array) require $name, implode('.', $tmp), []);
        }

        return [];
    }
}
