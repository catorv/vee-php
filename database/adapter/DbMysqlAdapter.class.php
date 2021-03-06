<?php
/**
 * VEE-PHP - a lightweight, simple, flexible, fast PHP MVC framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to catorwei@gmail.com so we can send you a copy immediately.
 *
 * @package vee-php
 * @copyright Copyright (c) 2005-2079 Cator Vee
 * @license http://www.opensource.org/licenses/bsd-license.php
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */

/**
 * 实现使用MySQL扩展模块功能的数据库适配器类
 *
 * @package vee-php\database
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class DbMysqlAdapter extends ADbAdapter {
    /**
     * 执行结果暂存
     * @var resource
     */
    private $dataset = null;

    /**
     * 连接数据库
     * @return boolean 返回连接数据库是否成功
     */
    public function connect() {
        if ($this->options['persistent']) {
            $this->link = mysql_pconnect($this->params['host'],
                                         $this->params['user'],
                                         $this->params['password']);
        } else {
            $this->link = mysql_connect($this->params['host'],
                                        $this->params['user'],
                                        $this->params['password']);
        }
        mysql_select_db($this->params['name']);
        if (isset($this->options['charset'])) {
            mysql_set_charset($this->options['charset']);
        }
        return ($this->link !== false);
    }

    /**
     * 断开数据库连接
     */
    public function disconnect() {
        if ($this->link) {
            mysql_close($this->link);
        }
    }

    /**
     * 解析生成SQL语句
     * @param array $a DbQuery生成的SQL元素数组
     * @param array $isForPrint 是否只是为了输出SQL语句(在PDO模式下才有意义)
     * @return string
     */
    public function parseSql($a, $isForPrint=false) {
        switch ($a['mode']) {
            case 'select':
                $sql = 'SELECT ' . $this->parseSqlField($a['field'], true)
                        . ' FROM ' . $this->parseSqlTable($a['table']);
                if ($a['where']) {
                    $sql .= ' WHERE ' . $this->parseSqlWhere($a['where']);
                }
                if ($a['group']) {
                    $sql .= ' GROUP BY ' . $this->parseSqlGroup($a['group']);
                }
                if ($a['order']) {
                    $sql .= ' ORDER BY ' . $this->parseSqlOrder($a['order']);
                }
                break;
            case 'update':
                $sql = 'UPDATE ' . $this->parseSqlTable($a['table'], false)
                     . ' SET ' . $this->parseSqlValue($a['value'], 'update');
                if ($a['where']) {
                    $sql .= ' WHERE ' . $this->parseSqlWhere($a['where']);
                }
                break;
            case 'insert':
                $sql = 'INSERT ' . $this->parseSqlTable($a['table'], false)
                     . ' ' . $this->parseSqlValue($a['value'], 'insert');
                break;
            case 'delete':
                $sql = 'DELETE FROM ' . $this->parseSqlTable($a['table'], false);
                if ($a['where']) {
                    $sql .= ' WHERE ' . $this->parseSqlWhere($a['where']);
                }
                break;
        }
        return $sql;
    }

    /**
     * 字段名编码
     * @param string $name 字段名
     * @return string
     */
    private function encodeFieldName($name) {
        if (preg_match('/^\w+$/', $name)) {
            $result = '`' . $name . '`';
        } elseif (preg_match('/^\w+\.\w+$/', $name)) {
            $result = preg_replace('/^(\w+)\.(\w+)$/', '`\\1`.`\\2`', $name);
        } else {
            $result = $name;
        }
        if ($this->camelAutoDecode) {
            $result = StringHelper::camelDecode($result);
        }
        return $result;
    }

    /**
     * 字段值编码
     * @param string $value 字段值
     * @param string $type 字段类型
     * @return string
     */
    private function encodeFieldValue($value, $type) {
        return $this->quote($value, $type);
    }

    /**
     * 值转义/过滤/格式化/给字符串加引号
     * @param mixed $value 值
     * @param string $type 值类型
     * @return mixed
     */
    public function quote($value, $type = Db::DT_AUTO) {
        if (is_array($value)) {
            foreach ($value as $k => & $v) {
                $v = $this->quote($v, $type);
            }
            return $value;
        } else {
            if (Db::DT_AUTO == $type) { //自动识别数据类型
                switch (gettype($value)) {
                    case 'boolean': $type = Db::DT_BOOLEAN;    break;
                    case 'integer': $type = Db::DT_INT;        break;
                    case 'double':  $type = Db::DT_FLOAT;      break;
                    default:        $type = Db::DT_VARCHAR;    break;
                }
            }
            switch ($type) {
                case Db::DT_INT: $result = intval($value); break;
                case Db::DT_DECIMAL:
                case Db::DT_DOUBLE:
                case Db::DT_FLOAT: $result = floatval($value); break;
                case Db::DT_BOOLEAN:
                case Db::DT_BIT: $result = ($value ? 1 : 0); break;
                case Db::DT_DATETIME: $result = date('Y-m-d H:i:s', is_numeric($value) ? $value : strtotime($value)); break;
                case Db::DT_DATE: $result = date('Y-m-d', is_numeric($value) ? $value : strtotime($value)); break;
                case Db::DT_TIME: $result = date('H:i:s', is_numeric($value) ? $value : strtotime($value)); break;
                case Db::DT_SQL: $result = $this->encodeFieldName($value); break;
                default: $result = strval($value); break;
            }
            if (is_string($result)) {
                if (null === $this->link) {
                    $this->connect();
                }
                if (function_exists('mysql_real_escape_string')) {
                    $result = mysql_real_escape_string($result, $this->link);
                } else {
                    $result = mysql_escape_string($result);
                }
                $result = '\'' . $result . '\'';
            }
            return $result;
        }
    }

    /**
     * 解析生成SQL语句中的字段列表(select)
     * @param array $fields DbQuery生成的SQL元素数组中的字段列表部分
     * @return string
     */
    private function parseSqlField($fields) {
        $sql = array();
        foreach ($fields as $field) {
            $s = $this->encodeFieldName($field['name']);
            if ($field['opt']) {
                $s = $field['opt'] . '(' . $s
                   . str_repeat(')', substr_count($field['opt'], '(') + 1);
            }
            if ($field['alias']) {
                $s .= ' ' . Db::OP_AS . ' ' . $field['alias'];
            }
            $sql[] = $s;
        }
        return empty($sql) ? '*' : implode(',', $sql);
    }

    /**
     * 解析生成SQL语句中的字段列表(update&insert)
     * @param array $values DbQuery生成的SQL元素数组中的字段列表部分
     * @param string $type 类型,update|insert
     * @return string
     */
    private function parseSqlValue($values, $type) {
        if ($type == 'update') { //更新记录的Value部分
            $sql = array();
            foreach ($values as $value) {
                $sql[] = $this->encodeFieldName($value['name'])
                       . '=' . $this->encodeFieldValue($value['value'],
                                                       $value['type']);
            }
            $sql = implode(',', $sql);
        } else { //插入记录的Value部分
            $fields = array();
            $fieldValues = array();
            foreach ($values as $value) {
                $fields[] = $this->encodeFieldName($value['name']);
                $fieldValues[] = $this->encodeFieldValue($value['value'],
                                                         $value['type']);
            }
            $sql = '(' . implode(',', $fields) . ')VALUES('
                       . implode(',', $fieldValues) . ')';
        }
        return $sql;
    }

    /**
     * 解析生成SQL语句中的表列表
     * @param array $tables DbQuery生成的SQL元素数组中的表列表部分
     * @param boolean $withAlias 是否带别名
     * @return string
     */
    private function parseSqlTable($tables, $withAlias = true) {
        if (empty($tables)) {
            throw new Exception('您没有指定表名', 4041);
        }
        $sql = '';
        $tables[0]['on'] = '';
        foreach ($tables as $table) {
            if ($this->camelAutoDecode) {
                $tableName = '`' . $this->tablePrefix
                           . StringHelper::camelDecode($table['name']) . '`';
            } else {
                $tableName = '`' . $this->tablePrefix . $table['name'] . '`';
            }
            if ($withAlias && $table['alias']
                           && $table['alias'] != $table['name']) {
                $tableName .= ' ' . Db::OP_AS . ' ' . $table['alias'];
            }
            if (empty($sql)) {
                $sql = $tableName;
            } elseif ($table['on']) {
                $sql .= ' LEFT JOIN ' . $tableName
                                      . ' ON (' . $table['on'] . ')';
            } else {
                $sql .= ' CROSS JOIN ' . $tableName;
            }
        }
        return $sql;
    }

    /**
     * 解析生成SQL语句中的条件列表
     * @param array $wheres DbQuery生成的SQL元素数组中的条件列表部分
     * @return string
     */
    private function parseSqlWhere($wheres) {
        $sql = '';
        $addLogical = false;
        foreach ($wheres as $where) {
            if ($addLogical && $where['name'] != ')'
                            || $where['logical'] == Db::OP_NOT) {
                $sql .= ' ' . $where['logical'] . ' ';
            }
            if ($where['name'] == '(' || $where['name'] == ')') {
                $sql .= $where['name'];
                $addLogical = ($where['name'] != '(');
            } else {
                $name = $this->encodeFieldName($where['name']);
                if (!is_object($where['value'])) {
                    $value = $this->encodeFieldValue($where['value'],
                                                     $where['type']);
                } else {
                    $value = $where['value'];
                }
                switch ($where['opt']) {
                    case Db::OP_SQL:
                        $sql .= ' ' . $name;
                        break;
                    case Db::OP_BETWEEN:
                        if (is_array($value) && count($value) == 2) {
                            $sql .= ' ' . $name . ' ' . Db::OP_BETWEEN
                                  . ' ' . implode(' AND ', $value);
                        } else {
                            throw new Exception('字段值不合法:必须为至少拥有两个元素的数组', 4053);
                        }
                        break;
                    case Db::OP_IN:
                    case Db::OP_NOTIN:
                        if (is_array($value)) {
                            $value = $this->encodeFieldValue($where['value'],
                                                             $where['type']);
                            $sql .= ' ' . $name . ' ' . $where['opt']
                                  . ' (' . implode(',', $value) . ')';
                        } else if (is_object($where['value'])) {
                            $pieces = $value->getPieces();
                            $pieces['mode'] = 'select';
                            $sql .= ' ' . $name . ' ' . $where['opt']
                                        . ' (' . $this->parseSql($pieces) . ')';
                        } else {
                            throw new Exception('字段值不合法:必须为DbQuery对象或至少拥有一个元素的数组', 4053);
                        }
                        break;
                    case Db::OP_ISNULL:
                    case Db::OP_ISNOTNULL:
                        $sql .= $name . ' ' . $where['opt'];
                        break;
                    default :
                        $sql .= $name . ' ' . $where['opt'] . ' ' . $value;
                        break;
                }
                $addLogical = true;
            }
        }
        return $sql;
    }

    /**
     * 解析生成SQL语句中的分组列表
     * @param array $groups DbQuery生成的SQL元素数组中的分组列表部分
     * @return string
     * @access private
     */
    private function parseSqlGroup($groups) {
        $sql = array();
        foreach ($groups as $group) {
            $sql[] = $this->encodeFieldName($group);
        }
        return implode(',', $sql);
    }

    /**
     * 解析生成SQL语句中的排序列表
     * @param array $orders DbQuery生成的SQL元素数组中的排序列表部分
     * @return string
     * @access private
     */
    private function parseSqlOrder($orders) {
        $sql = array();
        foreach ($orders as $order) {
            $sql[] = $this->encodeFieldName($order['name'])
                   . ' ' . $order['order'];
        }
        return implode(',', $sql);
    }

    /**
     * 从结果集中提取记录
     * @param int $fetchMode 记录提取模式 默认字段名(Db::FETCH_ASSOC)
     * @return mixed
     */
    private function fetch($fetchMode = Db::FETCH_ASSOC) {
        switch ($fetchMode) {
            case Db::FETCH_ASSOC:
                $result = mysql_fetch_assoc($this->dataset);    break;
            case Db::FETCH_NUM:
                $result = mysql_fetch_row($this->dataset);      break;
            case Db::FETCH_BOTH:
                $result = mysql_fetch_array($this->dataset);    break;
            case Db::FETCH_OBJ:
                $result = mysql_fetch_object($this->dataset);   break;
        }
        return $result;
    }

    /**
     * 获取一条记录
     * @param string $sql SQL语句
     * @param int $fetchMode 记录提取模式 默认字段名(Db::FETCH_ASSOC)
     * @return array
     */
    public function getFirst($sql, $fetchMode = Db::FETCH_ASSOC) {
        $sql .= ' LIMIT 1';
        $this->execute($sql);
        return $this->fetch($fetchMode);
    }

    /**
     * 返回所有记录
     * @param string $sql SQL语句
     * @param int $offset 偏移量,即记录起始游标,从0开始
     * @param int $count 返回的最大记录数
     * @param int $fetchMode 记录提取模式 默认字段名(Db::FETCH_ASSOC)
     * @return array
     */
    public function getAll($sql, $offset=0, $count=0,
                           $fetchMode = Db::FETCH_ASSOC) {
        $result = array();
        if ($count > 0) {
            $sql .= ' LIMIT ' . $offset . ',' . $count;
        }
        $this->execute($sql);
        while ($row = $this->fetch($fetchMode)) {
            $result[] = $row;
        }
        return $result;
    }

    /**
     * 执行SQL并/或返回下一条记录
     * @param int $fetchMode 记录提取模式 默认字段名(Db::FETCH_ASSOC)
     * @return array 如果没有记录可返回，则返回false
     */
    public function next($fetchMode = Db::FETCH_ASSOC) {
        if ($this->dataset) {
            return $this->fetch($fetchMode);
        } else {
            return false;
        }
    }

    /**
     * 执行SQL(update/delete)并返回受影响的记录数
     * @param string $sql SQL语句
     * @return int
     */
    public function execute($sql) {
        if (Config::DEBUG & C::DEBUG_ORM) {
            $timeStart = microtime(true);
        }
        if (empty($this->link)) {
            $this->connect();
        }
        $this->dataset = mysql_query($sql, $this->link);
        if (Config::DEBUG & C::DEBUG_ORM) {
            if (is_resource($this->dataset)) {
                $msg = mysql_num_rows($this->dataset) . ' rows, ';
            } else if (true === $this->dataset) {
                $msg = mysql_affected_rows($this->link) . ' rows, ';
            } else {
                $msg = '';
            }
            V::debug($sql . ' (' . $msg
                          . round((microtime(true) - $timeStart) * 1000, 2)
                          . ' ms)',
                     C::DEBUGTYPE_ORM);
        }
        if (false === $this->dataset) {
            $this->error($sql);
        }
        return $this->dataset;
    }

    /**
     * 返回最后一条插入行的序列值
     * @return int 最后一条插入行的序列值
     */
    public function lastInsertId() {
        if (empty($this->link)) {
            $this->connect();
        }
        return mysql_insert_id($this->link);
    }

    /**
     * 初始化一个事务
     * @return boolean
     */
    public function beginTransaction() {
        return $this->execute('BEGIN');
    }

    /**
     * 提交一个事务
     * @return boolean
     */
    public function commit() {
        return $this->execute('COMMIT');
    }

    /**
     * 回滚一个事务
     * @return boolean
     */
    public function rollBack() {
        return $this->execute('ROLLBACK');
    }

    /**
     * 取序列号(实验性的,不建议使用)
     * @param string $seqName 序列名
     * @param string $type    类型 current/next
     * @return integer $seqId 序列ID
     * @access public
     */
    public function getSequence($seqName = 'global', $type = 'next') {
        if ($type == 'current') {
            $result = $this->getFirst('SELECT MAX(seq_id) FROM seq_'
                                      . $seqName, Db::FETCH_NUM);
            if ($result) {
                $result = $result[0];
            } else {
                $result = 0;
            }
        } else {
            $this->execute('INSERT INTO seq_' . $seqName);
            $result = $this->lastInsertId();
            $this->execute('DELETE FROM seq_' . $seqName
                           . ' WHERE seq_id<' . $result);
        }
        return $result;
    }

    /**
     * 数据库操作执行错误
     * @param string $sql 错误的SQL语句
     */
    protected function error($sql) {
        throw new Exception('SQL执行错误: ' . $sql
                           . '  错误代码: ' . mysql_errno($this->link)
                           . '  错误信息: ' . mysql_error($this->link), 4001);
    }
}
