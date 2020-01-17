<?php

namespace Jy\Common\Contract\Context;

/**
 * @file ContextInterface.php
 * 上下文接口
 * @author jingzhiheng
 * @version v1
 * @date 2020-01-14
 */

interface ContextInterface
{

    /**
     * 获取上下文的一个元素
     *
     * @return
     */
    public static function get($key, $default = null, $uid = null);

    /**
     * 往上下文中设置一个元素
     *
     * @return
     */
    public static function put($key, $val, $uid = null);

    /**
     *  判断上下文中是否有某个元素
     *
     * @return
     */
    public static function has($key, $uid = null);

    /**
     * 删除上下文的一个元素
     *
     * @return
     */
    public static function del($key, $uid = null);

    /**
     * 创建一个上下文
     *
     * @return
     */
    public static function create();

    /**
     * 销毁一个上下文
     *
     * @return
     */
    public static function destroy($uid);
}
