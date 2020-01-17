<?php

namespace Jy\Facade;

use Jy\Db\Facade\DBComponent;

/**
 * @method static int insert($table, $params = array())
 * @method static int update($table, $params = array())
 * @method static int updateById($table, $id, $param = array())
 * @method static array multiInsert($table, $params = array())
 * @method static array findOne($sql, $param = array(), $master = false)
 * @method static array findAll($sql, $param = array(), $master = false)
 * @method static int beginTransaction()
 * @method static int commit()
 * @method static int rollBack()
 */
class DB extends DBComponent
{
    //..

}
