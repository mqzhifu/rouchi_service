<?php
/**
 * @user: ligongxiang (ligongxiang@rouchi.com)
 * @date : 2020/1/10
 * @version : 1.0
 * @file : InterfaceOrm.php
 * @desc :
 */

namespace Jy\Db\Orm;

/*
 * 此接口只是为项目中提供一种ORM的实现接口，实际orm类也即是Model类在项目中
 * */
interface InterfaceOrm
{
    //应用array至orm对象成员属性中
    public function apply(array $data);

    //处理orm对象设置部分值之后的insert语句拼接
    public function insert();

    //处理orm对象设置部分值之后的update语句拼接
    public function update();

    //将orm对象转换成map
    public function toArray():array;
}