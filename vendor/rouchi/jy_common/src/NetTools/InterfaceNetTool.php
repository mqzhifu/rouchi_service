<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/1/9
 * @version : 1.0
 * @file : InterfaceNetTool.php
 * @desc :
 */

namespace Jy\Common\NetTools;

/**
 * 网络工具接口
 *
 * 网络工具类的本意只提供一个对应系列方法的工具类。
 * */
interface InterfaceNetTool
{
    /**
     * 创建句柄
     *
     * @param   $dest       string  目标地址
     * @param   $data       string  发送参数
     * @param   $options    array   初始化参数项
     * @return  mixed   句柄
     * */
    public function init($dest,$data,$options = array());

    /**
     * 测活
     *
     * @return  mixed   有连接的句柄
     * */
    public function ping();

    /**
     * 执行连接
     *
     * @return  string  请求返回数据
     * */
    public function exec():string;

    /**
     * 回收连接
     *
     * @return  null    无返回
     * */
    public function recycle();
}