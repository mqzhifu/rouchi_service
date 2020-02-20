<?php

namespace Jy\Facade;

use Jy\Db\Facade\DBComponent;

/**
 * @method static int insert($table, $params = array(), $model = '', $name = '', $type = "write")
 * @method static int update($table, $params = array(), $model = '', $name = '', $type = "write")
 * @method static int updateById($table, $id, $param = array(), $model = '', $name = '', $type = "write")
 * @method static array multiInsert($table, $params = array(), $model = '', $name = '', $type = "write")
 * @method static array findOne($sql, $param = array(), $master = false, $model = '', $name = '')
 * @method static array findAll($sql, $param = array(), $master = false, $model = '', $name = '')
 * @method static int beginTransaction($model = '', $name = '', $type = "write")
 * @method static int commit($model = '', $name = '', $type = "write")
 * @method static int rollBack($model = '', $name = '', $type = "write")
 */
class DB extends DBComponent
{
    //..

}
