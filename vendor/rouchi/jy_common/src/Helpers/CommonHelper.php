<?php

/**
 * 配置加载函数
 */
if (!function_exists('getJyConfFile')) {

    function getJyConfFile($sFileUrl, $sAppName = "")
    {
        if (empty($sAppName)) $sAppName = getJyAppName();

        $sConfDirName = 'rouchi_conf';
        $sDir = explode(DIRECTORY_SEPARATOR, $sFileUrl);
        $sFile = array_pop($sDir);

        $sApproot = defined('ROUCHI_ROOT_PATH') ? ROUCHI_ROOT_PATH . '/..' : dirname(__FILE__) . '/../../../../../..';

        $sFileAddress = $sApproot . DIRECTORY_SEPARATOR . $sConfDirName . DIRECTORY_SEPARATOR . $sAppName . DIRECTORY_SEPARATOR . $sFile;

        if (is_file($sFileAddress)) {
            return $sFileAddress;
        } else {
            return FALSE;
        }
    }
}

if (!function_exists('getJyAppName')) {

    function getJyAppName()
    {
        $arr = explode(DIRECTORY_SEPARATOR, dirname(dirname(dirname(dirname(dirname(__DIR__))))));
        return array_pop($arr);
    }
}

if (!function_exists('isDebug')) {

    function isDebug()
    {
        return \Jy\Facade\Config::get('@app.debug', false);
    }
}

