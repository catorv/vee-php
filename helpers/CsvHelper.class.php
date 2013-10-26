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
 * CSV文件操作辅助器
 * @package vee-php\helpers
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class CsvHelper {
    /**
     * 字段分隔符，默认是逗号
     * @var string
     */
    public $delimiter = ',';
    /**
     * 边界符(只能是一个字符)，默认是双引号
     * @var string
     */
    public $enclosure = '"';
    /**
     * 转义符
     * @var string
     */
    public $escape = '\\';
    /**
     * 是否输出标题行，默认是false
     * @var boolean
     */
    public $hasTitle = false;
    /**
     * CSV数据行的最大长度，默认是0
     * @var ing
     */
    public $length = 0;
    /**
     * 标题行内容
     * @var array
     */
    private $title = array();

    /**
     * 获取实例
     * @return CsvHelper
     */
    public static function getInstance() {
        return new CsvHelper();
    }

    /**
     * 设置标题
     *
     * @param array $titles
     */
    public function setTitle($titles){
        $this->hasTitle = true;
        $this->title = $titles;
    }

    /**
     * 将一个数组以CSV格式保存到文件或直接返回内容
     *
     * @param array $arr 数组
     * @param string $path 文件保存路径，如果未指定，则直接返回内容
     * @return boolen|string true|false|$content
     */
    public function putcsv($arr, $path=''){
        if ($this->hasTitle) {
            if ('' != $this->title) {
                array_unshift($arr, $this->title);
            } else if (isset($arr[0])) {
                array_unshift($arr, array_keys($arr[0]));
            }
        }

        if ('' != $path) {
            $handle = fopen($path, 'a');
            if ($handle) {
                foreach ($arr as $item){
                    if (fputcsv($handle, $item,
                                $this->delimiter, $this->enclosure) == false) {
                        return false;
                    }
                }
                fclose($handle);
                return true;
            }
            return false;
        } else {
            $content = '';
            foreach ($arr as $item) {
                foreach ($item as $key => & $value) {
                    if (false !== strpos($value, $this->enclosure)) {
                        $value = $this->enclosure
                               . str_replace($this->enclosure,
                                         $this->enclosure . $this->enclosure,
                                         $value)
                               . $this->enclosure;
                    }
                }
                $content .= implode($this->delimiter, $item) . "\r\n";
            }
            return $content;
        }
    }

    /**
     * 从文件中读取一个CSV文件内容到数组中
     *
     * @param string $path CSV文件路径
     * @return array $tmp
     */
    public function getcsv($path){
        $result = array();
        if (is_file($path)) {
            $handle = fopen($path, 'r');
            if ($handle) {
                $first = true;
                while ($data = fgetcsv($handle, $this->length,
                                       $this->delimiter, $this->enclosure)) {
                    if ($first) {
                        $first = false;
                        if ($this->hasTitle) {
                            $this->title = $data;
                            continue;
                        }
                    }
                    if ($this->hasTitle) {
                        $fields = array();
                        foreach ($data as $key => & $value) {
                            if (isset($this->title[$key])) {
                                $fields[$this->title[$key]] = & $value;
                            } else {
                                $fields[$key] = & $value;
                            }
                        }
                        $result[] = $fields;
                    } else {
                        $result[] = $data;
                    }
                }
                fclose($handle);
            }
        }
        return $result;
    }
}