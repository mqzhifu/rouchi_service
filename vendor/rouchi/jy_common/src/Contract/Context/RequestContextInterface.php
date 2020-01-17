<?php

namespace Jy\Common\Contract\Context;

/**
 * @file RequestContextInterface.php
 * 请求上下文的接口
 * @author jingzhiheng
 * @version V1
 * @date 2020-01-14
 */


interface RequestContextInterface extends ContextInterface
{
    //public static function override();
    //public static function from();
    //public static  function copy();
    public static function currency();
    //public static function getContainer();
}
