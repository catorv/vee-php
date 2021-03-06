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
 * 数据库常量定义类及DbQuery的工厂类，并作为数据库的连接类
 *
 * @package vee-php\database
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class Db {
    // Mapping 文件中的字段数据类型
    const DT_SQL            = 'sql';
    const DT_AUTO           = 'auto';

    const DT_BOOLEAN        = 'boolean';
    const DT_BIT            = 'bit';
    const DT_INT            = 'int';
    const DT_DECIMAL        = 'decimal';
    const DT_DOUBLE         = 'double';
    const DT_FLOAT          = 'float';
    const DT_CHAR           = 'char';
    const DT_VARCHAR        = 'varchar';
    const DT_CLOB           = 'clob';
    const DT_TEXT           = 'text';
    const DT_BLOB           = 'blob';
    const DT_DATE           = 'date';
    const DT_TIME           = 'time';
    const DT_DATETIME       = 'datetime';

    // select操作符
    const OP_AS             = 'AS';
    const OP_MAX            = 'MAX';
    const OP_MIN            = 'MIN';
    const OP_SUM            = 'SUM';
    const OP_AVG            = 'AVG';
    const OP_COUNT          = 'COUNT';

    // where操作符
    const OP_EQ             = '=';
    const OP_NE             = '<>';
    const OP_GT             = '>';
    const OP_LT             = '<';
    const OP_GE             = '>=';
    const OP_LE             = '<=';
    const OP_BETWEEN        = 'BETWEEN';
    const OP_LIKE           = 'LIKE';
    const OP_ISNULL         = 'IS NULL';
    const OP_ISNOTNULL      = 'IS NOT NULL';
    const OP_IN             = 'IN';
    const OP_NOTIN          = 'NOT IN';
    const OP_AND            = 'AND';
    const OP_OR             = 'OR';
    const OP_NOT            = 'NOT';
    const OP_SQL            = 'SQL';

    // 记录提取模式
    const FETCH_ASSOC       = 2;
    const FETCH_NUM         = 3;
    const FETCH_BOTH        = 4;
    const FETCH_OBJ         = 5;

    // 排序方向
    const ORDER_ASC         = 'ASC';
    const ORDER_DESC        = 'DESC';

    /**
     * 数据库适配器器
     * @var ADbAdapter
     */
    public $adapter;
    /**
     * 参数
     * @var array
     */
    public $params;
    /**
     * 选项
     * @var array
     */
    public $options;

    /**
     * 构造函数
     * @param string $params 连接参数
     * @param array $options 选项
     */
    public function __construct($params, $options = array()) {
        $this->params = $params;
        $this->options = $options;
        if ('PdoMysql' == $params['driver']) {
            $adapterName = 'DbPdoSuperAdapter';
        } else {
            $adapterName = 'Db' . $params['driver'] . 'Adapter';
            require_once PATH_VEE_ROOT . 'database/adapter/'
                                        . $adapterName . '.class.php';
        }
        $this->adapter = new $adapterName($this);
    }

    /**
     * 新建一个查询类
     * @return DbQuery
     */
    public function query() {
        return new DbQuery($this);
    }

    /**
     * 直接执行SQL语句
     * @param string $sql SQL语句
     * @return int
     */
    public function execute($sql) {
        return $this->adapter->execute($sql);
    }

    /**
     * 值转义/过滤/格式化/给字符串加引号
     * @param mixed $value 值
     * @param string $type 值类型
     * @return mixed
     */
    public function quote($value, $type = Db::DT_AUTO) {
        return $this->adapter->quote($value, $type);
    }

    /**
     * 取当前序列号
     * @param string $name 序列名称
     * @return integer 序列ID
     */
    public function getCurrentSequence($name) {
        return $this->adapter->getSequence($name, 'current');
    }

    /**
     * 取下一个序列号
     * @param string $name 序列名称
     * @return integer 序列ID
     */
    public function getNextSequence($name) {
        return $this->adapter->getSequence($name, 'next');
    }

    /**
     * 初始化一个事务
     * @return boolean
     */
    public function beginTransaction() {
        return $this->adapter->beginTransaction();
    }

    /**
     * 提交一个事务
     * @return boolean
     */
    public function commit() {
        return $this->adapter->commit();
    }

    /**
     * 回滚一个事务
     * @return boolean
     */
    public function rollBack() {
        return $this->adapter->rollBack();
    }
}


/**
 * 数据库适配器抽象类
 *
 * @package vee-php\database\adapter
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
abstract class ADbAdapter {
    /**
     * 数据库连接实例
     * (类型根据不同的数据库适配器而异，因本人使用PDO，所以在编辑器中提升为PDO类型)
     * @var PDO
     */
    protected $link = null;

    /**
     * 选项
     * @var array
     */
    protected $options;

    /**
     * 参数
     * @var array
     */
    protected $params;

    /**
     * 表前缀
     * @var string
     */
    protected $tablePrefix = '';

    /**
     * 自动驼峰格式解码
     * @var boolean
     */
    protected $camelAutoDecode = false;

    /**
     * 构造函数
     * @param Db $session 数据库连接
     */
    public function __construct($session) {
        $this->params = & $session->params;
        $this->options = & $session->options;
        if (isset($this->options['tablePrefix'])) {
            $this->tablePrefix = $this->options['tablePrefix'];
            unset($this->options['tablePrefix']);
        }
        if (isset($this->options['camelAutoDecode'])) {
            $this->camelAutoDecode = $this->options['camelAutoDecode'];
            unset($this->options['camelAutoDecode']);
        }
    }

    /**
     * 析构函数
     */
    public function __destruct() {
        $this->disconnect();
    }

    /**
     * 连接数据库
     * @return boolean 返回连接数据库是否成功
     */
    abstract public function connect();

    /**
     * 断开数据库连接
     */
    abstract public function disconnect();

    /**
     * 解析生成SQL语句
     * @param array $a DbQuery生成的SQL元素数组
     * @param array $isForPrint 是否只是为了输出SQL语句(在PDO模式下才有意义)
     * @return string
     */
    abstract public function parseSql($a, $isForPrint=false);

    /**
     * 获取一条记录
     * @param string $sql SQL语句
     * @param int $fetchMode 记录提取模式 默认字段名(Db::FETCH_ASSOC)
     * @return array
     */
    abstract public function getFirst($sql, $fetchMode = Db::FETCH_ASSOC);

    /**
     * 返回所有记录
     * @param string $sql SQL语句
     * @param int $offset 偏移量,即记录起始游标,从0开始
     * @param int $count 返回的最大记录数
     * @param int $fetchMode 记录提取模式 默认字段名(Db::FETCH_ASSOC)
     * @return array
     */
    abstract public function getAll($sql, $offset=0, $count=0,
                                    $fetchMode = Db::FETCH_ASSOC);

    /**
     * 执行SQL并/或返回下一条记录
     * (PDO模式下可能会轻微影响性能，不建议使用，而在非PDO模式下可以轻微提高性能)
     * @param int $fetchMode 记录提取模式 默认字段名(Db::FETCH_ASSOC)
     * @return array 如果没有记录可返回，则返回false
     */
    abstract public function next($fetchMode = Db::FETCH_ASSOC);

    /**
     * 取序列号
     * @param string $seqName 序列名
     * @param string $type    类型 current/next
     * @return integer $seqId 序列ID
     * @access public
     */
    abstract public function getSequence($seqName = 'global', $type = 'next');

    /**
     * 执行SQL(update/delete)并返回受影响的记录数
     * @param string $sql SQL语句
     * @return int
     */
    abstract public function execute($sql);

    /**
     * 返回最后一条插入行的序列值
     * @return int 最后一条插入行的序列值
     */
    abstract public function lastInsertId();

    /**
     * 初始化一个事务
     * @return boolean
     */
    abstract public function beginTransaction();

    /**
     * 提交一个事务
     * @return boolean
     */
    abstract public function commit();

    /**
     * 回滚一个事务
     * @return boolean
     */
    abstract public function rollBack();

    /**
     * 值转义/过滤/格式化/给字符串加引号
     * @param mixed $value 值
     * @param string $type 值类型
     * @return mixed
     */
    abstract public function quote($value, $type = Db::DT_AUTO);
}


/**
 * 实现PDO方式的数据库适配器超类（以MySQL数据库为原型开发的）
 *
 * @package vee-php\database
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class DbPdoSuperAdapter extends ADbAdapter {
    /**
     * Statement的mark列表
     * @var array
     */
    protected $marks = array();

    /**
     * PDO的statement对象
     * @var PDOStatement
     */
    protected $statement = null;

    /**
     * 连接数据库,如果连接失败,抛出 PDOException 异常
     * @return boolean 返回连接数据库是否成功
     */
    public function connect() {
        $this->link = new PDO($this->params['dsn'], $this->params['user'],
                              $this->params['password'], $this->options);
        return true;
    }

    /**
     * 断开数据库连接
     */
    public function disconnect() {
        if ($this->link) {
            $this->link = null;
            $this->statement = null;
        }
    }

    /**
     * 解析生成SQL语句
     * @param array $a DbQuery生成的SQL元素数组
     * @param array $isForPrint 是否只是为了输出SQL语句(在PDO模式下才有意义)
     * @return string
     */
    public function parseSql($a, $isForPrint=false) {
        $this->marks = array();
        $sql = $this->makeSql($a);
        if ($isForPrint) {
            $sql = $this->sqlToPrint($sql);
        }
        return $sql;
    }

    /**
     * 生成SQL语句
     * @param array $a DbQuery生成的SQL元素数组
     * @return string
     */
    private function makeSql($a) {
        switch ($a['mode']) {
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
                if (!empty($a['where'])) {
                    $sql .= ' WHERE ' . $this->parseSqlWhere($a['where']);
                }
                break;
            default : // 'select'
                $sql = 'SELECT ' . $this->parseSqlField($a['field'], true)
                        . ' FROM ' . $this->parseSqlTable($a['table']);
                if (!empty($a['where'])) {
                    $sql .= ' WHERE ' . $this->parseSqlWhere($a['where']);
                }
                if (!empty($a['group'])) {
                    $sql .= ' GROUP BY ' . $this->parseSqlGroup($a['group']);
                }
                if (!empty($a['order'])) {
                    $sql .= ' ORDER BY ' . $this->parseSqlOrder($a['order']);
                }
                break;
        }
        return $sql;
    }

    /**
     * 将带有标记的SQL语句转换成真实SQL语句,即把标记的位置替换上真实的值
     * @param $sql 带有标记的SQL语句
     * @return string
     */
    private function sqlToPrint($sql) {
        if (null === $this->link) {
            $this->connect();
        }
        $pos = strpos($sql, '?');
        $i = 0;
        $result = '';
        while (false !== ($pos = strpos($sql, '?')) && isset($this->marks[$i])) {
            $mark = $this->marks[$i];
            $s = is_string($mark) ? $this->link->quote($mark) : $mark;
            $result .= substr($sql, 0, $pos) . $s;
            $sql = substr($sql, $pos + 1);
            ++$i;
        }
        return isset($result) ? $result . $sql : $sql;
    }

    /**
     * 字段名编码
     * @param string $name 字段名
     * @return string
     */
    private function encodeFieldName($name) {
        if (preg_match('/^\w+$/', $name)) {
            $result = '`' . $name . '`';
        } elseif (preg_match('/^(\w+)\.(\w+)$/', $name, $m)) {
            $result = '`' . $m[1] . '`.`' . $m[2] . '`';
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
     * @return mixed
     */
    private function encodeFieldValue($value, $type) {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->encodeFieldValue($v, $type);
            }
            return $value;
        } else {
            if ($type == Db::DT_AUTO) { //自动识别数据类型, 只会被识别为整型,浮点型,字符型中的一种
                switch (gettype($value)) {
                    case 'boolean': $type = Db::DT_BOOLEAN;    break;
                    case 'integer': $type = Db::DT_INT;        break;
                    case 'double':  $type = Db::DT_FLOAT;      break;
                    default:        $type = Db::DT_VARCHAR;    break;
                }
            }
            switch ($type) {
                case Db::DT_VARCHAR: //与default case相同，放在这里是为了提高效率
                    return strval($value);

                case Db::DT_INT:
                    return intval($value);

                case Db::DT_DECIMAL:
                case Db::DT_DOUBLE:
                case Db::DT_FLOAT:
                    return floatval($value);

                case Db::DT_DATETIME:
                    return is_numeric($value) ? date('Y-m-d H:i:s', $value) : $value;

                case Db::DT_DATE:
                    return is_numeric($value) ? date('Y-m-d', $value) : $value;

                case Db::DT_TIME:
                    return is_numeric($value) ? date('H:i:s', $value) : $value;

                case Db::DT_SQL:
                    return $this->encodeFieldName($value);

                case Db::DT_BOOLEAN:
                case Db::DT_BIT:
                    return empty($value) ? 0 : 1;

                default:
                    return strval($value);
            }
        }
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
            $result = $this->encodeFieldValue($value, $type);
            if (is_string($result)) {
                if (null === $this->link) {
                    $this->connect();
                }
                $result = $this->link->quote($result);
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
            if (!empty($field['opt'])) {
                $s = $field['opt'] . '(' . $s
                   . str_repeat(')', substr_count($field['opt'], '(') + 1);
            }
            if ($field['alias']) {
                $s .= ' ' . Db::OP_AS . ' `' . $field['alias'] . '`';
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
                if ($value['type'] == Db::DT_SQL) {
                    $sql[] = $this->encodeFieldName($value['name'])
                           . '=' . $value['value'];
                } else {
                    $sql[] = $this->encodeFieldName($value['name']) . '=?';
                    $this->marks[] = $this->encodeFieldValue($value['value'],
                                                             $value['type']);
                }
            }
            $sql = implode(',', $sql);
        } else { //插入记录的Value部分
            $fields = array();
            $fieldValues = array();
            foreach ($values as $value) {
                $fields[] = $this->encodeFieldName($value['name']);
                if ($value['type'] == Db::DT_SQL) {
                    $fieldValues[]= $value['value'];
                } else {
                    $fieldValues[] = '?';
                    $this->marks[]= $this->encodeFieldValue($value['value'],
                                                            $value['type']);
                }
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
     * @access private
     */
    private function parseSqlTable($tables, $withAlias=true) {
        if (empty($tables)) {
            throw new Exception('您没有指定表名', 4041);
        }
        $sql = '';
        $tables[0]['on'] = '';
        foreach ($tables as $table) {
            if ($this->camelAutoDecode) {
                $tableName = '`' . StringHelper::camelDecode($this->tablePrefix)
                             . $table['name'] . '`';
            } else {
                $tableName = '`' . $this->tablePrefix . $table['name'] . '`';
            }
            if ($withAlias && $table['alias'] && $table['alias'] != $table['name']) {
                $tableName .= ' ' . Db::OP_AS . ' `' . $table['alias'] . '`';
            }
            if (empty($sql)) {
                $sql = $tableName;
            } elseif ($table['on']) {
                $sql .= ' LEFT JOIN ' . $tableName . ' ON (' . $table['on'] . ')';
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
     * @access private
     */
    private function parseSqlWhere($wheres) {
        $sql = '';
        $addLogical = false;
        foreach ($wheres as $where) {
            if (($addLogical && $where['name'] != ')') || $where['logical'] == Db::OP_NOT) {
                $sql .= ' ' . $where['logical'] . ' ';
            }
            if ($where['name'] == '(' || $where['name'] == ')') {
                $sql .= $where['name'];
                $addLogical = ($where['name'] != '(');
            } else {
                $name = $this->encodeFieldName($where['name']);
                switch ($where['opt']) {
                    case Db::OP_IN:
                    case Db::OP_NOTIN:
                        if (is_array($where['value']) && count($where['value']) > 0) {
                            $value = $this->encodeFieldValue($where['value'], $where['type']);
                            $sql .= ' ' . $name . ' ' . $where['opt'] . ' (?'
                                  . str_repeat(',?', count($value) - 1) . ')';
                            $this->marks = array_merge($this->marks, $value);
                        } else if ($where['value'] instanceof DbQuery) {
                            $pieces = $where['value']->getPieces();
                            $pieces['mode'] = 'select';
                            $sql .= ' ' . $name . ' ' . $where['opt']
                                        . ' (' . $this->makeSql($pieces) . ')';
                        } else {
                            throw new Exception('字段值不合法:必须为DbQuery对象或至少拥有一个元素的数组', 4053);
                        }
                        break;
                    case Db::OP_SQL:
                        $sql .= ' ' . $name;
                        break;
                    case Db::OP_BETWEEN:
                        $value = $this->encodeFieldValue($where['value'], $where['type']);
                        if (is_array($value) && count($value) >= 2) {
                            $sql .= ' ' . $name . ' ' . Db::OP_BETWEEN . ' ? AND ?';
                            $this->marks[] = $value[0];
                            $this->marks[] = $value[1];
                        } else {
                            throw new Exception('字段值不合法:必须为至少拥有两个元素的数组', 4053);
                        }
                        break;
                    case Db::OP_ISNULL:
                    case Db::OP_ISNOTNULL:
                        $sql .= $name . ' ' . $where['opt'];
                        break;
                    default :
                        if ($where['type'] == Db::DT_SQL) {
                            $sql .= $name . ' ' . $where['opt'] . ' ' . $where['value'];
                        } else {
                            $sql .= $name . ' ' . $where['opt'] . ' ?';
                            $this->marks[] = $this->encodeFieldValue($where['value'], $where['type']);
                        }
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
        return implode(',', $groups);
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
     * @param boolean $fetchAll 是否返回全部记录，默认值为 false
     * @return mixed
     */
    final private function fetch($fetchMode = Db::FETCH_ASSOC, $fetchAll = false) {
        if ($fetchAll) {
            return $this->statement->fetchAll($fetchMode);
        } else {
            return $this->statement->fetch($fetchMode);
        }
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
        return $this->fetch($fetchMode, false);
    }

    /**
     * 返回所有记录
     * @param string $sql SQL语句
     * @param int $offset 偏移量,即记录起始游标,从0开始
     * @param int $count 返回的最大记录数
     * @param int $fetchMode 记录提取模式 默认字段名(Db::FETCH_ASSOC)
     * @return array
     */
    public function getAll($sql, $offset=0, $count=0, $fetchMode = Db::FETCH_ASSOC) {
        if ($count > 0) {
            $sql .= ' LIMIT ' . $offset . ',' . $count;
        }
        $this->execute($sql);
        return $this->fetch($fetchMode, true);
    }

    /**
     * 执行SQL并/或返回下一条记录
     * @param int $fetchMode 记录提取模式 默认字段名(Db::FETCH_ASSOC)
     * @return array 如果没有记录可返回，则返回false
     */
    public function next($fetchMode = Db::FETCH_ASSOC) {
        if ($this->statement) {
            return $this->fetch($fetchMode, false);
        } else {
            return false;
        }
    }

    /**
     * 执行SQL并返回是否执行成功
     * @param string $sql SQL语句
     * @param int $retry 失败重试次数
     * @return boolean
     */
    final public function execute($sql, $retry = 0) {
        if (Config::DEBUG & C::DEBUG_ORM) {
            $msg = $this->sqlToPrint($sql) . ' (';
            $timeStart = microtime(true);
        }
        if (null === $this->link) {
            $this->connect();
        }
        $this->statement = $this->link->prepare($sql);
        if (strpos($sql, '?') === false) {
            $result = $this->statement->execute();
        } else {
            $result = $this->statement->execute($this->marks);
        }
        if (Config::DEBUG & C::DEBUG_ORM) {
            if ($result) {
                $msg .= $this->statement->rowCount() . ' rows, '
                      . round((microtime(true) - $timeStart) * 1000, 3);
            } else {
                $msg .= round((microtime(true) - $timeStart) * 1000, 3);
            }
            V::debug($msg . ' ms)', C::DEBUGTYPE_ORM);
        }
        if (false === $result) {
            if ($retry <= 0) {
                $errorInfo = $this->statement->errorInfo();
                if (isset($errorInfo[1]) && $errorInfo[1] == '2006') { // MySQL server has gone away
                    $this->disconnect();
                    $this->connect();
                    return $this->execute($sql, $retry + 1);
                }
            }
            $this->error($sql);
        }
        return $result;
    }

    /**
     * 返回最后一条插入行的序列值
     * @return int 最后一条插入行的序列值
     */
    public function lastInsertId() {
        if (null === $this->link) {
            $this->connect();
        }
        return $this->link->lastInsertId();
    }

    /**
     * 初始化一个事务
     * @return boolean
     */
    public function beginTransaction() {
        if (null === $this->link) {
            $this->connect();
        }
        return $this->link->beginTransaction();
    }

    /**
     * 提交一个事务
     * @return boolean
     */
    public function commit() {
        if (null === $this->link) {
            $this->connect();
        }
        return $this->link->commit();
    }

    /**
     * 回滚一个事务
     * @return boolean
     */
    public function rollBack() {
        if (null === $this->link) {
            $this->connect();
        }
        return $this->link->rollBack();
    }

    /**
     * 取序列号
     * @param string $seqName 序列名
     * @param string $type    类型 current/next
     * @return integer $seqId 序列ID
     * @access public
     */
    public function getSequence($seqName = 'global', $type = 'next'){
        if ($type == 'current') {
            $result = $this->getFirst('SELECT MAX(seq_id) FROM seq_' . $seqName,
                                       Db::FETCH_NUM);
            $result = empty($result) ? 0 : $result[0];
        } else {
            $this->execute('INSERT INTO seq_' . $seqName
                           . ' (create_time)VALUES(' . time() . ')');
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
        $errorInfo = $this->statement->errorInfo();
        $code = $errorInfo[0] . (isset($errorInfo[1]) ? ('] [' . $errorInfo[1]) : '' );
        $msg = isset($errorInfo[2]) ? (' ' . $errorInfo[2]) : '';
        throw new Exception('SQL执行错误: ' . $this->sqlToPrint($sql)
                           . '  (SQLSTATE[' . $code . ']' . $msg . ')', 4001);
    }
}


/**
 * 数据库查询类
 *
 * @package vee-php\database
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class DbQuery {
    /**
     * 数据库连接
     * @var Db
     */
    protected $session;

    /**
     * 用于生成SQL语句的元素数组(包含table,field,value,where,order by,group by等)
     * @var array
     */
    protected $pieces;

    /**
     * 构造函数
     * @param Db $session 数据库连接
     */
    public function __construct($session) {
        $this->session = $session;
        $this->clear();
    }

    /**
     * 获取生成用于生成SQL语句的元素数组
     * @return array
     */
    public function getPieces() {
        return $this->pieces;
    }

    /**
     * 获取本数据库查询对象的数据库连接会话(session)
     * @return Db
     */
    public function getSession() {
        return $this->session;
    }

    /**
     * 生成并执行SQL,然后返回结果数据集中的第一行记录的第一列数据
     * @param string $sql 直接执行原生SQL,如果是PDO模式,该参数类型为PDOStatement
     * @return string
     */
    public function getValue($sql = null) {
        if (null === $sql) {
            $sql = $this->asSql('select', false);
        }
        $data = $this->session->adapter->getFirst($sql, Db::FETCH_NUM);
        return $data ? $data[0] : null;
    }

    /**
     * 生成并执行SQL,然后返回一个数组对象表示结果数据集中的第一行记录
     * @param string $sql 直接执行原生SQL,如果是PDO模式,该参数类型为PDOStatement
     * @param int $fetchMode 记录提取模式 默认按字段名提取数据(Db::FETCH_ASSOC)
     * @return array
     */
    public function getRow($sql = null, $fetchMode = Db::FETCH_ASSOC) {
        if (null === $sql) {
            $sql = $this->asSql('select', false);
        }
        $data = $this->session->adapter->getFirst($sql, $fetchMode);
        return $data ? $data : array();
    }

    /**
     * 生成并执行SQL,然后返回一个数组表示结果数据集中的第一列数据
     * @param string $sql 直接执行原生SQL,如果是PDO模式,该参数类型为PDOStatement
     * @param int $offset 偏移量
     * @param int $count 返回结果集记录条数
     * @return array
     */
    public function getColumn($sql = null, $offset = 0, $count = 0) {
        $result = array();
        $list = $this->getList($sql, $offset, $count, Db::FETCH_NUM);
        if ($list) {
            foreach ($list as & $item) {
                $result[] = $item[0];
            }
        }
        return $result;
    }

    /**
     * 生成并执行SQL,然后返回一个数组表示一个数据集
     * @param string $sql 直接执行原生SQL,如果是PDO模式,该参数类型为PDOStatement
     * @param int $offset 偏移量
     * @param int $count 返回结果集记录条数
     * @param int $fetchMode 记录提取模式 默认字段名(Db::FETCH_ASSOC)
     * @return array
     */
    public function getList($sql = null, $offset = 0, $count = 0,
                            $fetchMode = Db::FETCH_ASSOC) {
        if (null === $sql) {
            $sql = $this->asSql('select', false);
        }
        $data = $this->session->adapter->getAll($sql, $offset, $count,
                                                $fetchMode);
        return $data ? $data : array();
    }

    /**
     * 获取数据表的记录总数
     * @return int
     */
    public function getRecordCount() {
        $fields = $this->pieces['field'];
        $orders = $this->pieces['order'];
        $this->pieces['field'] = array(
                    array(
                        'name'  => '*',
                        'alias' => 'rowcount',
                        'opt'   => Db::OP_COUNT,
                    ),
                );
        $this->pieces['order'] = null;
        $result = $this->getValue();
        $this->pieces['field'] = $fields;
        $this->pieces['order'] = $orders;
        return $result;
    }

    /**
     * 执行SQL并/或返回下一条记录
     * @param int $fetchMode 记录提取模式 默认字段名(Db::FETCH_ASSOC)
     * @return array
     */
    public function next($fetchMode = Db::FETCH_ASSOC) {
        $data = $this->session->adapter->next($fetchMode);
        return $data ? $data : array();
    }

    /**
     * 更新数据库记录,返回受影响的记录数
     * @param array $fields 内容为 字段名=>字段值
     *                       或者 array( 'name' => 字段名,
     *                                   'value' => 字段值,
     *                                   'type' => 字段类型 )
     * @return int
     */
    public function update($fields = '') {
        return $this->updateOrInsert($fields, 'update');
    }

    /**
     * 插入数据库新记录,返回受影响的记录数
     * @param array $fields 内容为 字段名=>字段值
     *                       或者 array( 'name' => 字段名,
     *                                   'value' => 字段值,
     *                                   'type' => 字段类型 )
     * @return int
     */
    public function insert($fields = '') {
        return $this->updateOrInsert($fields, 'insert');
    }

    /**
     * 插入/更新数据库新记录,返回受影响的记录数
     * @param array $fields 内容为 字段名=>字段值，或者 array( 'name' => 字段名,
     *                                                     'value' => 字段值,
     *                                                     'type' => 字段类型 )
     * @param string $mode 保存模式(update|insert)
     * @return int
     */
    public function updateOrInsert($fields = null, $mode = 'insert') {
        if (is_array($fields)) {
            foreach ($fields as $name => $value) {
                if (is_array($value)) {
                    if (!isset($value['type'])) {
                        $value['type'] = Db::DT_AUTO;
                    }
                    if (isset($value['name']) && isset($value['value'])) {
                        $this->addValue($value['name'], $value['value'], $value['type']);
                    }
                } else {
                    $this->addValue($name, $value);
                }
            }
        }
        return $this->session->adapter->execute($this->asSql($mode, false));
    }

    /**
     * 更新数据库记录,返回受影响的记录数,如果执行失败返回false
     * @return int
     */
    public function delete() {
        return $this->session->adapter->execute($this->asSql('delete', false));
    }

    /**
     * 直接执行SQL语句
     * @param string $sql SQL语句
     * @return int
     */
    public function execute($sql = null) {
        if (null === $sql) {
            $sql = $this->asSql('select', false);
        }
        return $this->session->adapter->execute($sql);
    }

    /**
     * 返回生成的SQL脚本
     * @param string $type SQL查询语句的类型(select|update|delete|insert)
     * @param boolean $isForPrint 是否只是为了输出SQL语句(在PDO模式下才有意义)
     * @return string 返回生成的SQL脚本,如果是PDO模式且$isForPrint=false,
     *                 则返回未替换变量值的SQL
     */
    public function asSql($type = 'select', $isForPrint = true) {
        $this->pieces['mode'] = $type;
        return $this->session->adapter->parseSql($this->pieces, $isForPrint);
    }

    /**
     * 清空表,字段,约束
     * @param string $part 要清空的部分,默认清空全部,值可为:
     *                      'table', 'field', 'value', 'where', 'group', 'order'
     * @return DbQuery
     */
    public function clear($part = null) {
        if (isset($this->pieces[$part])) {
            $this->pieces[$part] = array();
        } else if (null === $part) {
            $this->pieces = array (
                    'mode'     => 'select',
                    'table'    => array(),
                    'field'    => array(),
                    'value'    => array(),
                    'where'    => array(),
                    'group'    => array(),
                    'order'    => array(),
                    );
        }
        return $this;
    }

    /**
     * 加入表
     * @param string $tableName 表名
     * @param string $alias 别名
     * @param string $on 如果指定该参数，则加入的表使用left join,
     *                    并以该参数指定的表达式为条件
     * @return DbQuery
     */
    public function addTable($tableName, $alias = '', $on = '') {
        if (empty($tableName)) {
            throw new Exception('表名不能为空', 4051);
        }
        $this->pieces['table'][] = array(
                'name'  => $tableName,
                'alias' => $alias,
                'on'    => $on,
                );
        return $this;
    }

    /**
     * 加入主表
     * @param string $tableName 表名
     * @param string $alias 别名
     * @return DbQuery
     */
    public function addMasterTable($tableName, $alias = '') {
        if (empty($tableName)) {
            throw new Exception('表名不能为空', 4051);
        }
        foreach ($this->pieces['table'] as $k => $table) {
            if ($table['name'] == $tableName) {
                unset($this->pieces['table'][$k]);
            }
        }
        array_unshift($this->pieces['table'], array(
                'name'  => $tableName,
                'alias' => $alias,
                'on'    => '',
                ));
        return $this;
    }

    /**
     * 加入字段
     * @param string $fieldName 字段名
     * @param string $alias 别名，默认为空串
     * @param string $opt 操作/函数类型，默认为空串
     * @return DbQuery
     */
    public function addField($fieldName, $alias = '', $opt = '') {
        if (empty($fieldName)) {
            throw new Exception('字段名不能为空', 4052);
        }
        $this->pieces['field'][] = array(
                'name'  => $fieldName,
                'alias' => $alias,
                'opt'   => $opt,
                );
        return $this;
    }

    /**
     * 加入值
     * @param string $fieldName 字段名
     * @param string $fieldValue 字段值
     * @param string $fieldType 字段类型
     * @return DbQuery
     */
    public function addValue($fieldName, $fieldValue,
                             $fieldType = Db::DT_AUTO) {
        if (empty($fieldName)) {
            throw new Exception('字段名不能为空', 4052);
        }
        $this->pieces['value'][] = array(
                'name'  => $fieldName,
                'value' => $fieldValue,
                'type'  => $fieldType,
                );
        return $this;
    }

    /**
     * 加入约束条件
     * @param string $fieldName 字段名
     * @param mixed $value 字段值, 默认值为NULL
     * @param string $opt 操作符, 默认值为Db::OP_EQ,
     *                     如果没有后续参数, 该参数可以表示$logical
     * @param string $fieldType 字段类型, 默认值为Db::DT_AUTO,
     *                           如果没有后续参数, 该参数可以表示$logical
     * @param string $logical 逻辑关系操作符(Db::OP_AND|Db::OP_OR|Db::OP_NOT),
     *                         默认值为Db::OP_AND
     * @return DbQuery
     */
    public function addWhere($fieldName, $value = null, $opt = Db::OP_EQ,
                             $fieldType = Db::DT_AUTO, $logical = Db::OP_AND) {
        if (empty($fieldName)) {
            throw new Exception('字段名不能为空', 4052);
        }
        $num = func_num_args();
        $where = array(
                'name' => $fieldName,
                'value' => $value,
                );
        if ($num == 3 && ($opt == Db::OP_AND || $opt == Db::OP_OR
                                             || $opt == Db::OP_NOT)) {
            $where['opt'] = Db::OP_EQ;
            $where['type'] = $fieldType;
            $where['logical'] = $opt;
        } elseif ($num == 4 && ($fieldType == Db::OP_AND
                                || $fieldType == Db::OP_OR
                                || $fieldType == Db::OP_NOT)) {
            $where['opt'] = $opt;
            $where['type'] = Db::DT_AUTO;
            $where['logical'] = $fieldType;
        } else {
            $where['opt'] = $opt;
            $where['type'] = $fieldType;
            $where['logical'] = $logical;
        }
        $this->pieces['where'][] = $where;
        return $this;
    }

    /**
     * 加入分组
     * @param string $fieldName 字段名
     * @return DbQuery
     */
    public function addGroupBy($fieldName) {
        if (empty($fieldName)) {
            throw new Exception('字段名不能为空', 4052);
        }
        $this->pieces['group'][] = $fieldName;
        return $this;
    }

    /**
     * 加入排序
     * @param string $fieldName 字段名
     * @param string $orderBy 排序方式，默认为 Db::ORDER_ASC
     * @return DbQuery
     */
    public function addOrderBy($fieldName, $orderBy = Db::ORDER_ASC) {
        if (empty($fieldName)) {
            throw new Exception('字段名不能为空', 4052);
        }
        $this->pieces['order'][] = array(
                'name'  => $fieldName,
                'order' => $orderBy,
                );
        return $this;
    }
}


/**
 * 数据库实体类
 *
 * @package vee-php\database
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class DbEntity {
    /**
     * Db配置项编号
     * @var string
     */
    protected $_db;
    /**
     * 实体对象对应的数据库表名
     * @var string
     */
    protected $_table = '';
    /**
     * 实体对象对应的数据表的主键字段名称
     * @var string
     */
    protected $_key = '';
    /**
     * 实体对象的属性及数据表字段定义数组
     * @var array
     */
    protected $_fields = array();
    /**
     * 是否缓存实体对象的内容, 如果指定要缓存实体对象内容, 请使用memcache等内存缓存引擎,
     * 使用文件缓存引擎有可能导致单文件夹内文件个数过多而造成性能急剧下降
     * @var boolean
     */
    protected $_cache = true;
    /**
     * 实体对象对应的数据表的主键字段值
     * @var mixed
     */
    private $_id = null;

    /**
     * 构造函数
     * @param int $id 主键值, 默认可以不指定, 通过 load() 方法获取,
     *                        如果指定了, 则自动调用 load() 装载对应的数据
     */
    public function __construct($id = null) {
        if (null !== $id) {
            if (is_array($id)) {
                $this->setProperties($id);
            } else {
                $this->load($id);
            }
        }
    }

    /**
     * 判断该实体对象是否为空
     * @return boolean 如果该实体对象没有连接数据, 则返回true, 否则返回false
     */
    public function isNull() {
        return (null === $this->_id);
    }

    /**
     * 根据主键值, 从数据库中获取一条记录并填充当前实体对象
     * @param mixed $id 实体对象的主键值
     */
    public function load($id = null) {
        if (!$this->isNull()) {
            $this->elope();
        }
        if ($id === null) {
            $query = $this->dbQuery(true);
            foreach ($this->_fields as $field => & $attributes) {
                if (null !== $this->{$field}) {
                    if (is_array($this->{$field})) {
                        $query->addWhere($field, $this->{$field}, Db::OP_IN);
                    } else {
                        $query->addWhere($field, $this->{$field});
                    }
                }
            }
            $data = $query->getRow();
            if ($data) {
                $this->setProperties($data);
            }
        } else if (!Config::ORM_CACHE || !$this->_cache
                                      || !$this->loadFromCache($id)) {
            $this->loadFromDb($id);
        }
        return !$this->isNull();
    }

    /**
     * 获取实体类对应的数据库表名
     * @return string
     */
    public function getTableName() {
        return $this->_table;
    }

    /**
     * 获取实体类主键(id)字段的值
     * @return int
     */
    public function getId() {
        return $this->_id;
    }

    /**
     * 返回实体对象数组
     * @param DbQuery $query 数据库查询对象
     * @param int $offset 偏移量
     * @param int $count 返回结果集记录条数
     * @return array
     */
    public function getList($query = null, $offset = 0, $count = 0) {
        $result = array();
        if (null === $query) {
            $query = $this->dbQuery(true);
        } else {
            $query->addMasterTable($this->_table, '_master')->clear('field');
        }
        $query->addField('_master.' . $this->_key, $this->_key);
        $ids = $query->getList(null, $offset, $count, Db::FETCH_NUM);

        $className = get_class($this);
        $i = 0;
        $miss = array();
        foreach ($ids as $row) {
            $id = intval($row[0]);
            if ($data = $this->getCache($id)) {
                $result[$i] = new $className($id);
            } else {
                $result[$i] = null;
                $miss[$i] = $id;
            }
            ++$i;
        }

        if ($miss) {
            $this->dbQuery()
                 ->clear()
                 ->addTable($this->_table)
                 ->addWhere($this->_key, $miss, Db::OP_IN);
            $dataset = $this->dbQuery()->getList();
            $miss = array_flip($miss);
            foreach ($dataset as $row) {
                $id = $row[$this->_key];
                $result[$miss[$id]] = new $className($row);
            }
        }

        return $result;
    }

    /**
     * 返回匹配的第一个对象
     * @param DbQuery $query 数据库查询对象
     * @return DbEntity
     */
    public function getFirst($query = null) {
        $result = $this->getList($query, 0, 1);
        if ($result) {
            return $result[0];
        } else {
            $className = get_class($this);
            return new $className();
        }
    }

    /**
     * 从数组中导入实体各属性的值,即批量赋值,但不包含主键.
     * @param array $array 数组
     * @param array $exclude 排除项
     */
    public function importProperties($array, $exclude = array()) {
        if (is_array($array)) {
            foreach ($this->_fields as $field => & $attributes) {
                if (isset($array[$field]) && $this->_key != $field
                            && !in_array($field, $exclude)) {
                    $this->setProperty($array, $field, $attributes);
                }
            }
        } else if (is_object($array)) {
            $this->importProperties(get_object_vars($array), $exclude);
        }
    }

    /**
     * 脱离ORM关系, 成为一个孤立的对象
     * @return DbEntity
     */
    public function elope() {
        $this->_id = null;
        $this->{$this->_key} = null;
        return $this;
    }

    /**
     * 复位当前对象为空对象
     * @return DbEntity
     */
    public function reset() {
        $this->_id = null;
        foreach ($this->_fields as $field => & $attributes) {
            $this->{$field} = null;
            $attributes['value'] = null;
        }
        return $this;
    }

    /**
     * 保存实体到数据库中(更新/新增)
     * @return boolean 返回保存是否成功
     */
    public function save() {
        $query = $this->dbQuery()->clear();
        if ($this->isNull()) { // 新增一条记录
            $query->addTable($this->_table);
            foreach ($this->_fields as $field => & $attributes) {
                if (null !== $this->$field) {
                    $query->addValue($field, $this->$field, $attributes['type']);
                } else if ($field != $this->_key && null !== $attributes['default']) {
                    $query->addValue($field, $attributes['default'], $attributes['type']);
                }
            }

            if ($query->insert()) {
                $id = $this->{$this->_key};
                if (null !== $id) {
                    $this->_id = $id;
                } else {
                    $this->_id = $query->getSession()->adapter->lastInsertId();
                }
                if (Config::ORM_CACHE && $this->_cache) {
                    $this->loadFromDb($this->_id);
                } else {
                    $this->{$this->_key} = $this->_id;
                }
                return true;
            }

        } else { // 编辑当前记录
            $needUpdate = false;
            foreach ($this->_fields as $field => & $attributes) {
                if ($this->$field != $attributes['value']) {
                    $needUpdate = true;
                    $query->addValue($field, $this->$field, $attributes['type']);
                }
            }

            if ($needUpdate) {
                if (Config::ORM_CACHE && $this->_cache) {
                    $this->deleteFromCache();
                }
                $query->addTable($this->_table)->addWhere($this->_key, $this->_id);
                if ($query->update()) {
//                    $this->loadFromDb($this->_id);
                    $this->saveToCache();
                    return true;
                }
            } else {
                return true;
            }
        }
        return false;
    }

    /**
     * 删除实体, 如果实体为空, 则直接返回false
     * @return boolean 返回删除是否成功
     */
    public function delete() {
        if (!$this->isNull()) {
            $this->dbQuery()->clear()
                            ->addTable($this->_table)
                            ->addWhere($this->_key, $this->_id);
            if ($this->dbQuery()->delete()) {
                if (Config::ORM_CACHE && $this->_cache) {
                    $this->deleteFromCache();
                }
                $this->_id = null;
                foreach ($this->_fields as $field => & $attributes) {
                    $this->$field = null;
                }
                return true;
            }
        }
        return false;
    }

    /**
     * 返回数据库查询对象(共享对象)
     * @param boolean $clear 是否自动清空并添加数据库表名
     * @return DbQuery
     */
    public function dbQuery($clear = false) {
        static $query = null;
        if (null === $query) {
            $query = V::db($this->_db)->query();
        }
        if ($clear) {
            $query->clear()->addTable($this->_table, '_master');
        }
        return $query;
    }

    /**
     * 从数据库中读取实体对象的内容,
     * @param int $id 实体对象主键值
     */
    protected function loadFromDb($id) {
        $data = $this->dbQuery()->clear()
                                ->addTable($this->_table)
                                ->addWhere($this->_key, $id)
                                ->getRow();
        $this->setProperties($data);
        return !!$data;
    }

    /**
     * 从缓存中读取实体对象的内容
     * @param int $id 实体对象主键值
     * @return boolean 返回是否获取成功
     */
    protected function loadFromCache($id) {
        $data = $this->getCache($id);
        if (empty($data)) {
            return false;
        } else {
            foreach ($data as $k => $v) {
                $this->$k = $v;
                $this->_fields[$k]['value'] = $v;
            }
            $this->_id = $id;
            return true;
        }
    }

    /**
     * 获取缓存的键名
     * @param mixed $id 主键的值
     * @return string
     */
    private function getCacheKey($id) {
//        if (!is_numeric($id)) {
//            $num = hexdec(substr($id, 0, 8));
//        } else {
//            $num = intval($id);
//        }
//        return Config::ORM_CACHE_PREFIX . $this->_table
//                                        . '/' . (($num / 1000) % 1000)
//                                        . '/' . ($num % 1000) . '/' . $id;
        return Config::ORM_CACHE_PREFIX . $this->_table . '/' . $id;
    }

    /**
     * 获取缓存数据
     * @param int $id 实体对象主键值
     * @return array
     */
    private function getCache($id) {
        return V::cache(Config::ORM_CACHE_ID)->get($this->getCacheKey($id));
    }

    /**
     * 将当前实体对象保存到缓存中
     * @return boolean 返回是否保存成功
     */
    private function saveToCache() {
        if ($this->isNull()) {
            return false;
        } else {
            $data = array();
            foreach ($this->_fields as $field => & $attributes) {
                $data[$field] = $this->$field;
            }
            return V::cache(Config::ORM_CACHE_ID)
                        ->set($this->getCacheKey($this->_id), $data);
        }
    }

    /**
     * 将当前实体对象从缓存中删除
     * @return boolean 返回是否保存成功
     */
    protected function deleteFromCache() {
        if ($this->isNull()) {
            return false;
        } else {
            return V::cache(Config::ORM_CACHE_ID)
                        ->delete($this->getCacheKey($this->_id));
        }
    }

    /**
     * 根据从数据库中获取到的数据数组填充一个实体对象,
     * 同时更新缓存数据(不管缓存数据是否过期)
     * @param array $data 数据数组
     */
    private function setProperties($data) {
        if ($data) {
            foreach ($this->_fields as $field => & $attributes) {
                $this->setProperty($data, $field, $attributes);
                $attributes['value'] = $this->$field;
                if (isset($data[$field])) {
                    unset($data['field']);
                }
            }
            foreach ($data as $field => $value) {
                $this->$field = $value;
            }
            $this->_id = $this->{$this->_key};
            if (Config::ORM_CACHE && $this->_cache) {
                $this->saveToCache();
            }
        } else {
            $this->_id = null;
        }
    }

    /**
     * 根据字段的类型将值转换为相应的数据类型并赋给指定的属性
     * @param string $data 数据库中的一条记录
     * @param string $field 当前字段名
     * @param array $attributes 该字段的属性设置
     */
    private function setProperty($data, $field, $attributes) {
        if (isset($data[$field])) {
            switch ($attributes['type']) {
                case Db::DT_INT:
                    $this->$field = intval($data[$field]);
                    break;
                case Db::DT_DECIMAL:
                case Db::DT_FLOAT:
                case Db::DT_DOUBLE:
                    $this->$field = floatval($data[$field]);
                    break;
                default:
                    $this->$field = $data[$field];
                    break;
            }
        }
    }

    /**
     * 输出实体对象内容
     * @return string
     */
    public function __toString() {
        return '[' . get_class($this) . json_encode($this) . ']';
    }
}