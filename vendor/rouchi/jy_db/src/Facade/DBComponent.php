<?php

namespace Jy\Db\Facade;

use Jy\Db\Database\Db;
use Jy\Config\Facade\Config;

class DBComponent
{
    public static $instances = array();

    public static function getInstance($name = '', $type = "write", $model = '')
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

        $mysqlConfig = Config::get($model, "connections.".$name);
        if (empty($mysqlConfig)) {
            $mysqlConfig = Config::get($model, $name);
        }

        if (empty($mysqlConfig)) {
            throw new \Exception('database conf is empty. pls check db conf');
        }

        $dbConfig = array(
            'host' => $mysqlConfig[$type]['host'] ?? $mysqlConfig['host'],
            'port' => $mysqlConfig[$type]['port'] ?? $mysqlConfig['port'],
            'user' => $mysqlConfig[$type]['username'] ?? $mysqlConfig['username'],
            'passwd' => $mysqlConfig[$type]['password'] ?? $mysqlConfig['password'],
            'dbname' => $mysqlConfig['database'],
            'timeout' => 2,
        );

        self::$instances[$key] = new Db($dbConfig);

        return self::$instances[$key];
    }

    public static function beginTransaction($name = '', $type = "write", $model = '')
    {
        return static::getInstance($name, $type, $model)->beginTransaction();
    }

    public static function commit($name = '', $type = "write", $model = '')
    {
        return static::getInstance($name, $type, $model)->commit();
    }

    public static function rollBack($name = '', $type = "write", $model = '')
    {
        return static::getInstance($name, $type, $model)->rollBack();
    }

    public static function update($sql, $param = array(), $name = '', $type = "write", $model = '')
    {
        return static::getInstance($name, $type, $model)->update($sql, $param);
    }

    public static function updateById($table, $id, $param = array(), $name = '', $type = "write", $model = '')
    {
        return static::getInstance($name, $type, $model)->updateById($table,$id,$param);
    }

    public static function insert($table, $params = array(), $name = '', $type = "write", $model = '')
    {
        return static::getInstance($name, $type, $model)->insert($table, $params);
    }

    public static function multiInsert($table, $params = array(), $name = '', $type = "write", $model = '')
    {
        return static::getInstance($name, $type, $model)->multiInsert($table, $params);
    }

    public static function findOne($sql, $param = array(), $master = false, $name = '', $model = '')
    {
        return static::getInstance($name, $master == true ? "write" : "read", $model)->findOne($sql, $param);
    }

    public static function findAll($sql, $param = array(), $master = false, $name = '', $model = '')
    {
        return static::getInstance($name, $master == true ? "write" : "read", $model)->findAll($sql, $param);
    }
}
