<?php

namespace Jy\Db\Db;

use Jy\Db\Contract\DbAbstract;
use Jy\Db\Db\PDODriver;

class Db extends  DbAbstract
{

    private $pdo = null;

    public function __construct($config)
    {
        $this->pdo = PDODriver::getInstance($config);
    }

    /**
     * 包装原生PDO方法
     * @param string $method
     * @param array $params
     * @return mixed
     */
    public function __call($method, $params = array())
    {
        return call_user_func_array(array($this->pdo, $method), $params);
    }

    public function update($sql, $param = [])
    {
        // sql check ...
        return $this->execute($sql, $param);
    }

    /**
     * 运行一段SQL
     * @param string $sql
     * @param array $params
     * @return boolean
     */
    protected function execute($sql, $params = array())
    {
        if (!is_array($params)) $params = array($params);
        $query = $this->buildQuery($sql, $params);
        return $this->exec($query);
    }

    /**
     * 绑定参数
     * @param string $query
     * @param array $params
     * @return string
     */
    protected function buildQuery($query, $params = array())
    {
        if (!is_array($params)) $params = array($params);
        foreach ($params as $param) {
            $query = preg_replace('/\?/', $param, $query, 1);
        }
        return $query;
    }

    /**
     * 插入单条并返回ID（插入失败返回0）
     * @return int
     */
    public function insert($table, array $params)
    {
        if (empty($table) || empty($params)) return 0;
        // 构建SQL
        $sql = sprintf('insert into `%s` (%s) values (%s)',
            $table,
            $this->buildFields(array_keys($params)),
            $this->buildValues($params)
        );

        $params_t = array();
        foreach ($params as $field) {
            $params_t[] = $this->quote($field);
        }

        $ret = $this->execute($sql, $params_t);

        return $this->lastInsertId();
    }

    public function updateById($table, $id, array $param)
    {
        if (empty($table) || empty($params) || empty($id))
            return 0;
        $temp = array();
        foreach ($param as $k => $v) {
            $temp[] = $k.'="'.$v.'"';
        }
        // 构建SQL
        $sql = sprintf('update `%s` set %s where id = %s',
            $table,
            implode(',',$temp),
            $id
        );
        // sql check ...
        return $this->exec($sql);
    }

    /**
     * 批量写入
     * @return int
     */
    public function multiInsert($table, $params = array())
    {
        if (empty($table) || empty($params)) return 0;
        // 构建SQL
        $sql = sprintf('insert into `%s` (%s) values ?',
            $table,
            $this->buildFields(array_keys($params[0])),
        );

        $params = $this->buildBatchValues($params);

        $ret = $this->execute($sql, $params);

        return $this->lastInsertId();
    }


    /**
     * 构造field列表
     * @param array $fields
     * @return string
     */
    protected function buildFields($fields = array())
    {
        return implode(', ', $fields);
    }

    /**
     * 构造values子句
     * @param array $fields
     * @return string
     */
    protected function buildValues($fields = array())
    {
        return rtrim(str_repeat('?,', count($fields)), ',');
    }

    /**
     * @param  array $fieldArrs 插入的数据
     * @return string
     *
     */
    protected function buildBatchValues($fieldArrs = [])
    {
        $sqlSection = [];
        foreach ($fieldArrs as $fieldArr) {
            $valueArr = [];
            foreach($fieldArr as $value) {
                $valueArr[] = $this->quote($value);
            }
            $sqlSection[] = " (". implode(',', $valueArr) . ") ";
        }
        return implode(',', $sqlSection);
    }


    /**
     * 获得一条记录
     * @param string $sql
     * @param array $params
     * @return mixed
     */
    public function findOne($sql, $params = array())
    {
        $query = $this->buildQuery($sql, $params);
        if (!$st = $this->query($query)) return array();
        $row = $st->fetch(\PDO::FETCH_ASSOC);
        return $row ? $row : array();
    }

    /**
     * 获得所有记录
     * @param string $sql
     * @param array $params
     * @return mixed
     */
    public function findAll($sql, $params = array())
    {
        $query = $this->buildQuery($sql, $params);
        if (!$st = $this->query($query)) return array();
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC);
        return $rows ? $rows : array();
    }


}
