<?php

namespace Jy\Db\Facade;

use Jy\Db\Db\Db;
use Jy\Config\Facade\Config;

class DBComponent
{
    public static $instances = array();

    public static function getInstance($model = '', $name = '', $type = "write")
    {
        if (empty($model)) {
           $model = "database";
        }

        if (empty($name)) {
            $name = 'mysql';
        }

        $key = $model . '.' . $name . '_' . $type;

        if (isset(self::$instances[$key])) {
            return self::$instances[$key];
        }

        $mysqlConfig = Config::get($model, $name);

        $dbConfig = array(
            'host' => $mysqlConfig[$type]['host'],
            'port' => $mysqlConfig[$type]['port'],
            'user' => $mysqlConfig[$type]['username'],
            'passwd' => $mysqlConfig[$type]['password'],
            'dbname' => $mysqlConfig['database'],
            'timeout' => 2,
        );

        self::$instances[$key] = new Db($dbConfig);

        return self::$instances[$key];
    }

    public static function beginTransaction()
    {
        return static::getInstance('', '', "write")->beginTransaction();
    }

    public static function commit()
    {
        return static::getInstance('', '', "write")->commit();
    }

    public static function rollBack()
    {
        return static::getInstance('', '', "write")->rollBack();
    }

    public static function update($sql, $param = array())
    {
        return static::getInstance('', '', "write")->update($sql, $param);
    }

    public static function updateById($table, $id, $param = array())
    {
        return static::getInstance('', '', "write")->updateById($table,$id,$param);
    }

    public static function insert($table, $params = array())
    {
        return static::getInstance('', '', "write")->insert($table, $params);
    }

    public static function multiInsert($table, $params = array())
    {
        return static::getInstance('', '', "write")->multiInsert($table, $params);
    }

    public static function findOne($sql, $param = array(), $master = false)
    {
        return static::getInstance('', '', $master == true ? "write" : "read")->findOne($sql, $param);
    }

    public static function findAll($sql, $param = array(), $master = false)
    {
        return static::getInstance('', '', $master == true ? "write" : "read")->findAll($sql, $param);
    }
}
