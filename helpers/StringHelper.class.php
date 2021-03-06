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
 * 字符串辅助器
 * @package vee-php\helpers
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class StringHelper {
    /**
     * 为数组中的每个元素取消魔术引用
     * @param mixed $var 要取消魔术引用的变量,可以是数组或字符串
     */
    static public function stripSlashesForArray(& $var) {
        if (is_array($var)) {
            foreach ($var as & $val) {
                self::stripSlashesForArray($val);
            }
        } else {
            $var = stripslashes($var);
        }
    }

    /**
     * 驼峰字符串编码
     * @param string $s 源字符串
     * @param string $first 第一个字符是否大写, 默认不大写
     * @return string
     */
    public static function camelEncode($s, $first = false) {
        if (false !== strpos($s, '_')) {
            $s = str_replace(' ', '', ucwords( strtr($s, '_', ' ') ) );
            if (!$first && $s) {
                $s[0] = strtolower($s[0]);
            }
        } else if ($first) {
            $s = ucfirst($s);
        }
        return $s;
    }

    /**
     * 驼峰字符串解码(注意：字符串前面的下划线"_"将被清除)
     * @param string $s 源字符串
     * @return string
     */
    public static function camelDecode($s) {
        $s = preg_replace('/([A-Z])/', '_\\1', $s);
        return strtolower(ltrim($s, '_'));
    }
}