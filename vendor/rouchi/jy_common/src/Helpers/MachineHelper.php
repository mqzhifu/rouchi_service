<?php

namespace Jy\Common\Helpers;




/**
 * @file MachineHelper.php
 * 获取机器的相关信息
 * @author jingzhiheng
 * @version V1
 * @date 2020-02-20
 */

class MachineHelper
{
    /**
     * 获取机器的mac地址
     *
     * @return string
     */
    public static function getLocalMacAddr()
    {
        return 'mac';
    }

    public static function getLocalIp()
    {
        return gethostbyname(gethostname());
    }
}
