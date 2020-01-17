<?php

namespace Jy\Db\Contract;


interface DbInterface
{
    public function update($sql, $param = array());
    public function insert($table, array $params);
    public function multiInsert($table, array $params);
    public function findOne($sql, $param = array());
    public function findAll($sql, $param = array());
}
