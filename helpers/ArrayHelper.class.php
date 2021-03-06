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
 * 数组工具类
 * @package vee-php\helpers
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class ArrayHelper {
    /**
     * 搜索数组中符合条件的值
     * 
     * 如果$keys=null且$onlyFirst=true的情况建议使用内建函数array_search()代替该方法
     *
     * @param array &$array 被搜索的数组
     * @param mixed $value 要查询的值
     * @param mixed $keys 深入搜索的键列表,不深入搜索则赋值为null,值可以是字符串或字符串数组类型
     * @param boolean $onlyFirst 是否只搜索第一个
     * @return mixed 搜索到一个则返回该数组元素,搜索到两个以上则返回结果数组,否则返回-1
     */
    public static function search(&$array, $value, $keys = null, $onlyFirst = false) {
        $result = array();
        foreach ($array as &$v) {
            if (is_string($keys)) {
                $isMatched = ($value === $v[$keys]);
            } elseif (is_array($keys)) {
                $v = & $array;
                foreach ($keys as $key) {
                    $v = & $v[$key];
                }
                $isMatched = ($value === $v);
            } else {
                $isMatched = ($value === $v);
            }
            if ($isMatched) {
                $result[] = $key;
                if ($onlyFirst) {
                    break;
                }
            }
        }
        $count = count($result);
        return ($count < 1) ? -1 : (($count == 1) ? $result[0] : $result);
    }

    /**
     * 插入指定的$key值前插入数据$value(由于处理结束后数组将变成数值索引,所以该函数适用于数值索引数组)
     * @param array &$array 操作的数组
     * @param string $key 键值,设置为NULL可以让数据添加到数组尾部
     * @param mixed $newValue 值
     * @param boolean $before 是否插入在指定$key之前
     * @return boolean 如果找到$key并插入成功则返回true,否则返回false,并把数据添加到数组尾部
     */
    public static function insert(& $array, $key, $newValue, $before = true) {
        $result = false;
        $size = sizeof($array);
        for ($i=0; $i<$size; $i++) {
            $value = array_shift($array);
            if ($i == $key) {
                if ($before) {
                    array_push($array, $newValue);
                    array_push($array, $value);
                } else {
                    array_push($array, $value);
                    array_push($array, $newValue);
                }
                $result = true;
            } else {
                array_push($array, $value);
            }
        }
        if (!$result) {
            array_push($array, $newValue);
        }
        return $result;
    }

    /**
     * 删除key为指定的key里的值
     * @param array &$array 操作的数组
     * @param string $keys 键值可以是数组
     */
    public static function delete(& $array, $keys) {
        if (!is_array($keys)) {
            $keys = array($keys);
        }
        foreach ($keys as $key) {
            unset($array[$key]);
        }
    }

    /**
     * 将一个平面的二维数组按照指定的字段转换为树状结构
     *
     * @param array $arr 数据源
     * @param string $key_node_id 节点ID字段名
     * @param string $key_parent_id 节点父ID字段名
     * @param string $key_children 保存子节点的字段名
     * @param boolean $refs 是否在返回结果中包含节点引用
     *
     * return array 树形结构的数组
     */
    public static function toTree($arr, $key_node_id, $key_parent_id = 'parentId',
    $key_children = 'children', & $refs = null)
    {
        $refs = array();
        foreach ($arr as $offset => $row)
        {
            $arr[$offset][$key_children] = array();
            $arr[$offset]['leaf'] = true; //针对ext的tree另外添加
            $refs[$row[$key_node_id]] =& $arr[$offset];
        }

        $tree = array();
        foreach ($arr as $offset => $row)
        {
            $parent_id = $row[$key_parent_id];
            if ($parent_id)
            {
                if (!isset($refs[$parent_id]))
                {
                    $tree[] =& $arr[$offset];//忽略不存在的父级
                    continue;
                }
                $parent =& $refs[$parent_id];
                $parent[$key_children][] =& $arr[$offset];
                $parent['leaf'] = false;
            }
            else
            {
                $tree[] =& $arr[$offset];
            }
        }

        return $tree;
    }

    /**
     * 将一个二维数组转换为 hashmap
     *
     * 如果省略 $valueField 参数，则转换结果每一项为包含该项所有数据的数组。
     *
     * @param array $arr
     * @param string $keyField
     * @param string $valueField
     *
     * @return array
     */
    public static function arrayToHashmap(& $arr, $keyField, $valueField = null)
    {
        $ret = array();
        if ($valueField) {
            foreach ($arr as $row) {
                $ret[$row[$keyField]] = $row[$valueField];
            }
        } else {
            foreach ($arr as $row) {
                $ret[$row[$keyField]] = $row;
            }
        }
        return $ret;
    }
    /**
     * 从一个二维数组中返回指定键的所有值
     *
     * 用法：
     * @code php
     * $rows = array(
     *     array('id' => 1, 'value' => '1-1'),
     *     array('id' => 2, 'value' => '2-1'),
     * );
     * $values = ArrayUtil::getCols($rows, 'value');
     *
     * dump($values);
     *   // 输出结果为
     *   // array(
     *   //   '1-1',
     *   //   '2-1',
     *   // )
     * @endcode
     *
     * @param array $arr 数据源
     * @param string $col 要查询的键
     *
     * @return array 包含指定键所有值的数组
     */
    public static function getCols($arr, $col)
    {
        $ret = array();
        foreach ($arr as $row)
        {
            if (isset($row[$col])) { $ret[] = $row[$col]; }
        }
        return $ret;
    }


    /**
     * 将一个二维数组按照指定字段的值分组
     *
     * 用法：
     * @code php
     * $rows = array(
     *     array('id' => 1, 'value' => '1-1', 'parent' => 1),
     *     array('id' => 2, 'value' => '2-1', 'parent' => 1),
     *     array('id' => 3, 'value' => '3-1', 'parent' => 1),
     *     array('id' => 4, 'value' => '4-1', 'parent' => 2),
     *     array('id' => 5, 'value' => '5-1', 'parent' => 2),
     *     array('id' => 6, 'value' => '6-1', 'parent' => 3),
     * );
     * $values = ArrayUtil::groupBy($rows, 'parent');
     *
     * dump($values);
     *   // 按照 parent 分组的输出结果为
     *   // array(
     *   //   1 => array(
     *   //        array('id' => 1, 'value' => '1-1', 'parent' => 1),
     *   //        array('id' => 2, 'value' => '2-1', 'parent' => 1),
     *   //        array('id' => 3, 'value' => '3-1', 'parent' => 1),
     *   //   ),
     *   //   2 => array(
     *   //        array('id' => 4, 'value' => '4-1', 'parent' => 2),
     *   //        array('id' => 5, 'value' => '5-1', 'parent' => 2),
     *   //   ),
     *   //   3 => array(
     *   //        array('id' => 6, 'value' => '6-1', 'parent' => 3),
     *   //   ),
     *   // )
     * @endcode
     *
     * @param array $arr 数据源
     * @param string $key_field 作为分组依据的键名
     *
     * @return array 分组后的结果
     */
    static function groupBy($arr, $key_field)
    {
        $ret = array();
        foreach ($arr as $row)
        {
            $key = $row[$key_field];
            $ret[$key][] = $row;
        }
        return $ret;
    }
    static function groupBy2($arr, $key_field)
    {
        $ret = array();
        $keyArray = array();
        $i = 0;
        foreach ($arr as $row)
        {
            $key = $row[$key_field];
            if (!in_array ($key, $keyArray)) {
                $keyArray[] = $key;
            }
            $index = array_keys($keyArray,$key);
            $ret[$index[0]][] = $row;
            $i++;
        }
        return $ret;
    }

    /**
     * 将一个二维数组按照多个列进行排序，类似 SQL 语句中的 ORDER BY
     *
     * 用法：
     * @code php
     * $rows = ArrayUtil::sortByMultiCols($rows, array(
     *     'parent' => SORT_ASC,
     *     'name' => SORT_DESC,
     * ));
     * @endcode
     *
     * @param array $rowset 要排序的数组
     * @param array $args 排序的键
     *
     * @return array 排序后的数组
     */
    static function sortByMultiCols($rowset, $args)
    {
        $sortArray = array();
        $sortRule = '';
        foreach ($args as $sortField => $sortDir)
        {
            foreach ($rowset as $offset => $row)
            {
                $sortArray[$sortField][$offset] = $row[$sortField];
            }
            $sortRule .= '$sortArray[\'' . $sortField . '\'], ' . $sortDir . ', ';
        }
        if (empty($sortArray) || empty($sortRule)) { return $rowset; }
        eval('array_multisort(' . $sortRule . '$rowset);');
        return $rowset;
    }
}