<?php

namespace Jy\Db\Contract;

use Jy\Db\Contract\DbInterface;


abstract class DbAbstract implements DbInterface
{
    abstract protected function execute($sql, $param = array());
    abstract protected function buildQuery($sql);
    abstract protected function buildFields($fields = array());
    abstract protected function buildValues($fields = array());
    abstract protected function buildBatchValues($fieldArrs = []);

    abstract public function update($sql, $param = array());
    abstract public function insert($table,array $params);
    abstract public function multiInsert($table,array $params);
    abstract public function findOne($sql, $param = array());
    abstract public function findAll($sql, $param = array());
}
