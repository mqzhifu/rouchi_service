<?php

namespace Jy\Config\Config;

use Jy\Config\Contract\ConfigAbstract;

class Config extends  ConfigAbstract
{
    public function get($module = '', $key = '')
    {
        if (!defined('ROUCHI_CONF_PATH')) {
            throw new \Exception('config path const : ROUCHI_CONF_PATH  没有定义');
        }

        $moduleFile = rtrim(ROUCHI_CONF_PATH, '/') .'/' . $module . ".php";

        if (!file_exists($moduleFile)) {
            throw new \Exception('config:' . $moduleFile . '文件名不存在');
        }
        $res = require $moduleFile;

        $kList = explode('.', $key);
        switch (count($kList)) {

        case 1:
            if (isset($res[$kList[0]])) {
                return $res[$kList[0]];
            } else {
                throw new \Exception('config:' . $key . '不存在');
            }
        case 2:
            if (isset($res[$kList[0]][$kList[1]])) {
                return $res[$kList[0]][$kList[1]];
            } else {
                throw new \Exception('config:' . $module . ' . ' . $key . '不存在');
            }
        default:
            throw new \Exception('config:' . $module . ' . ' . $key . '不存在');

        }
    }


    public function set($module = "", $key = "", $value = "")
    {
        //...
        return null;
    }
}
