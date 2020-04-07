<?php

namespace Rouchi\Models;


use InvalidArgumentException;
use Jy\Facade\DB;
use Jy\Db\Orm\InterfaceOrm;

/**
 * model 基类
 * usage: 所有select语句支持如下，其他请自行写sql
 * $this->where([['id', 1], ['type', 2], ['setup_type', '>=', 0], ['status' => 3]])
        ->where(['course_id' => 1, 'creator_id' => 1, 'memo' => ''])
        ->where(['session_times', '>=', 0])
        ->where('name', '=', 1)
        ->where('name', 'like', '%sd%')
        ->whereIn('active', [0,1])
        ->whereNotIn('active', [0,1])
        ->whereNull('name')
        ->whereNotNull('name')
        ->select(['*', 'name'])
        ->orderBy('id')
        ->orderByDesc('status')
        ->groupBy('status', 'id')
        ->limit(1)
        ->offset(2)
        //->first();
 *      //->get();
 *      //->count();
 * insert:
 * $this->create($arr)
 * or
 * $this->apply($arr)->insert()
 *
 * update:
 * $this->updateById($id, $arr)
 */
abstract class BaseModel implements InterfaceOrm
{
    const ACTIVE_DELETE = 0;
    const ACTIVE_NORMAL = 1;
    //临时内容arr，用于保存上次的全部数据，用于update的增量更新. 赋值节点为插入、apply、更新时.
    protected $_tempArr = [];

    //表名
    protected $_tableName = '';

    //属性，与数据表一致，默认值均为null，意为未设置
    public $id = null;                          //所有表必须有自增ID
    protected $active = self::ACTIVE_NORMAL;    //所有表必须包含软删temp，禁止外部获取

    //全属性一维arr，避免反射
    public $_colsArr = [];

    protected $bindings = [];
    // 只支持and拼接
    protected $wheres = [];

    protected $selects = '*';

    protected $limit = '';
    protected $offset = '';
    protected $orders = [];
    protected $groups = '';

    // last sql
    protected $sql;

    //kv数组映射成对象本身，null值会自动过滤
    public function apply(array $data)
    {
        foreach ($data as $key => $value) {
            if (in_array($key,$this->_colsArr) && $value !== null)
                $this->$key = $value;
        }
        $this->_tempArr = $this->toArray();
        return $this;
    }

    public function query()
    {
        return new static();
    }

    // 支持lumen orm写法
    public function where($column, $operator = null, $value = null)
    {
        if (is_array($column)) {
            return $this->whereArr($column);
        }

        $args = func_num_args();
        if ($args == 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = "`$column` $operator ?";
        $this->bindings[] = $value;
        return $this;
    }

    public function select($columns = '*')
    {
        if ($columns == '*' || $columns == ['*']) {
            return $this;
        }

        if (!is_array($columns)) {
            $columns = func_get_args();
        }

        $this->selects = '`'.implode("`,`", $columns).'`';
        return $this;
    }

    public function whereNull($column, $not = '')
    {
        $this->wheres[] = "`$column` IS{$not} NULL";
        return $this;
    }

    public function whereNotNull($column)
    {
        return $this->whereNull($column, ' NOT');
    }

    protected function whereArr(array $arr)
    {
        $isMore = false;
        foreach ($arr as $v) {
            if (is_array($v)) {
                $isMore = true;
                break;
            }
        }

        $assocOrIdx = function ($arr) {
            if (isset($arr[0])) {
                $this->where(...$arr);
            } else {
                foreach ($arr as $k => $v) {
                    $this->where($k, $v);
                }
            }
        };

        if ($isMore) {
            foreach ($arr as $v) {
                $assocOrIdx($v);
            }
        } else {
            $assocOrIdx($arr);
        }
        return $this;
    }

    public function whereIn($column, array $values, $not = '')
    {
        if (empty($values)) {
            return $this;
        }

        $placeholder = join(',', array_fill(0, count($values), '?'));
        foreach ($values as $value) {
            $this->bindings[] = $value;
        }
        $this->wheres[] = "`$column`{$not} IN ({$placeholder})";
        return $this;
    }

    public function whereNotIn($column, array $values)
    {
        return $this->whereIn($column, $values, ' NOT');
    }

    public function get($master = false)
    {
        $this->sql = $this->getSelectSql();
        $res = DB::findAll($this->sql, $this->bindings, $master);
        $this->clear();
        $arr = [];
        foreach ($res as $row) {
            $arr[] = $this->query()->apply($row);
        }
        return $arr;
    }

    public function first($master = false)
    {
        $this->limit(1);
        $this->sql = $this->getSelectSql();
        $res = DB::findOne($this->sql, $this->bindings, $master);
        $this->clear();
        if (!$res) {
            return null;
        }
        return $this->query()->apply($res);
    }

    public function count($master = false): int
    {
        $this->selects = 'COUNT(1) n';
        $this->sql = $this->getSelectSql();
        $r = DB::findOne($this->sql, $this->bindings, $master);
        $this->clear();
        return $r['n'] ?? 0;
    }

    public function getDirty(array $attrs = [])
    {
        $dirty = [];
        foreach ($attrs as $key => $value) {
            if ($value === false) {
                continue;
            }

            if (!isset($this->_tempArr[$key]) || $this->_tempArr[$key] != $value) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    public function orderBy($column, $direction = 'ASC')
    {
        $direction = strtoupper($direction);

        if (! in_array($direction, ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException('Order direction must be "ASC" or "DESC".');
        }

        $this->orders[] = [
            'column' => $column,
            'direction' => $direction,
        ];
        return $this;
    }

    public function orderByDesc($column)
    {
        return $this->orderBy($column, 'DESC');
    }

    public function groupBy($columns)
    {
        if (!is_array($columns)) {
            $columns = func_get_args();
        }
        $this->groups = 'GROUP BY `'.implode('`,`', $columns).'`';
        return $this;
    }

    public function limit(int $value)
    {
        $this->limit = 'LIMIT '.$value;
        return $this;
    }

    public function offset(int $value)
    {
        $this->offset = 'OFFSET '.$value;
        return $this;
    }

    public function getLastSelectSql()
    {
        return $this->sql;
    }

    protected function getSelectSql()
    {
        return "SELECT {$this->selects} FROM `$this->_tableName` 
                    WHERE {$this->compileWheres()} {$this->groups} {$this->compileOrders()} {$this->limit} {$this->offset}";
    }

    protected function compileWheres()
    {
        if (empty($this->wheres)) {
            return '1=1';
        }

        return implode(' AND ', $this->wheres);
    }

    protected function compileOrders()
    {
        if (! empty($this->orders)) {
            return 'ORDER BY '.implode(', ', array_map(function ($v) {
                    return '`'.$v['column'].'` '.$v['direction'];
                }, $this->orders));
        }

        return '';
    }

    protected function setLastSelectSql()
    {
        $this->sql = vsprintf(str_replace('?', '%s', $this->sql), array_map(function ($v) {
            return is_string($v) ? "'{$v}'" : $v;
        }, $this->bindings));
    }

    protected function clear()
    {
        $this->setLastSelectSql();
        $this->wheres = [];
        $this->bindings = [];
        $this->selects = '*';
        $this->orders = [];
        $this->limit = '';
        $this->offset = '';
        $this->groups = '';
    }

    public function create($data)
    {
        $newModel = $this->query()->apply($data);
        $newModel->insert();
        return $newModel;
    }

    //插入单条，id列会自动过滤，不会生效
    public function insert()
    {
        $temp = $this->getSetColKVMap();
        //去掉id
        unset($temp['id']);
        $id = DB::insert($this->_tableName,$temp);
        $this->id = $id;
        $this->_tempArr = $this->toArray();
        return $id;
    }

    public function updateById($id, $data)
    {
        return DB::updateById($this->_tableName, $id, $data);
    }

    public function update()
    {
        $data = $this->getSetColKVMap();
        if (empty($data['id'])) {
            throw new \Exception('update '.$this->_tableName.' id value is empty ');
        }
        return $this->updateById($data['id'], $data);
    }

    //根据ID进行软删
    public function delete()
    {
        if ($this->id <= 0)
            return 0;

        $affectRows = DB::updateById($this->_tableName,$this->id,array(
            'active' => self::ACTIVE_DELETE,
        ));
        $this->id = null;
        return $affectRows;
    }

    public function toArray(): array
    {
        $temp = array();
        foreach ($this->_colsArr as $value) {
            $temp[$value] = $this->$value;
        }
        return $temp;
    }

    //获取已设置的map
    protected function getSetColKVMap()
    {
        $temp = array();
        foreach ($this->_colsArr as $value) {
            if ($this->$value !== null)
                $temp[$value] = $this->$value;
        }
        return $temp;
    }

}
