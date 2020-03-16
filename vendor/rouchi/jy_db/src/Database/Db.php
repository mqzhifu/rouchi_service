<?php

namespace Jy\Db\Database;

use Jy\Db\Contract\DbAbstract;
use Jy\Db\Database\PDODriver;

class Db extends  DbAbstract
{

    private $pdo = null;
    private $config = [];

    public function __construct($config)
    {
        $this->config = $config;
        $this->connect();
    }

    private function connect()
    {
        $this->pdo = PDODriver::getInstance($this->config);
    }

    /**
     * 包装原生PDO方法
     * @param string $method
     * @param array $params
     * @return mixed
     */
    public function __call($method, $params = array())
    {
        $res = "";

        try {
            $res = call_user_func_array(array($this->pdo, $method), $params);

            if (false === $res) {

                if (!$this->isAlive()) {

                    $this->connect($this->config);

                    return call_user_func_array([$this->pdo, $method], $params);
                }
            }
        } finally {
            if (!is_null($this->pdo->errorInfo()[1])) throw new \Exception($this->pdo->errorInfo()[2], $this->pdo->errorInfo()[1]);
        }

        return $res;
    }

    public function update($sql, $param = [])
    {
        // sql check ...
        return $this->execute($sql, $param);
    }

    private function isAlive(): bool
    {
        $errorInfo = $this->pdo->errorInfo();

        if (!is_null($errorInfo[1]) && ($errorInfo[1] != 2006 or $errorInfo[1] != 2013)) {
            return false;
        }

        return true;
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

        $query = $this->buildQuery($sql);

        return $query->execute($params);
    }

    /**
     * sql 处理
     * @param string $query
     * @return string
     */
    protected function buildQuery($query)
    {
        return $this->prepare($query);
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
            $params_t[] = ($field);
        }

        $ret = $this->execute($sql, $params_t);

        return $this->lastInsertId();
    }

    public function updateById($table, $id, array $param)
    {
        if (empty($table) || empty($param) || empty($id))
            return 0;
        $temp = array();
        foreach ($param as $k => $v) {
            $temp[] = $k.'='.$this->quote($v);
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
        $sql = sprintf('insert into `%s` (%s) values %s',
            $table,
            $this->buildFields(array_keys($params[0])),
            $this->buildMultiValues(count($params[0]), count($params))
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

    protected function buildMultiValues($fieldsCount = 1, $dataCount = 1)
    {
        $str = "";
        for ($i = 1; $i <= $dataCount; $i++) {
            $str .= "(". rtrim(str_repeat('?,', $fieldsCount), ',')  ."),";
        }

        return rtrim($str, ',');
    }

    /**
     * @param  array $fieldArrs 插入的数据
     * @return string
     */
    protected function buildBatchValues($fieldArrs = [])
    {
        $valueArr = [];
        foreach ($fieldArrs as $fieldArr) {
            foreach($fieldArr as $value) {
                $valueArr[] = ($value);
            }
        }
        return $valueArr;
    }


    /**
     * 获得一条记录
     * @param string $sql
     * @param array $params
     * @return mixed
     */
    public function findOne($sql, $params = array())
    {
        $query = $this->buildQuery($sql);
        if (!$st = $query->execute($params)) return array();
        $row = $query->fetch(\PDO::FETCH_ASSOC);
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
        $query = $this->buildQuery($sql);
        if (!$st = $query->execute($params)) return array();
        $rows = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $rows ? $rows : array();
    }


}
