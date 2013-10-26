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
 * 校正辅助器
 * @package vee-php\helpers
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class CorrectHelper {
    /** 校正规则：整数 */
    const INT = 'CorrectHelper::intCorrector';
    /** 校正规则：浮点数 */
    const FLOAT = 'CorrectHelper::floatCorrector';
    /** 校正规则：时间戳 */
    const TIMESTAMP = 'CorrectHelper::timestampCorrector';
    /** 校正规则：数组 */
    const ARRAY_DATA = 'CorrectHelper::arrayCorrector';

    /**
     * 校正指定数组中的元素值为指定的格式
     * @param array $var 被校正的数组
     * @param array $rules 校正规则列表
     * @return mixed 如果数组中任何一个元素执行了校正操作，返回true，否则返回false
     */
    static public function correct(&$var, $rules) {
        $corrected = false;
        foreach ($rules as $names => $rule) {
            if (false === strpos($names, ',')) {
                $names = array($names);
            } else {
                $names = explode(',', $names);
            }
            if (is_array($rule) && isset($rule['corrector'])) {
                if (!isset($rule['options'])) {
                    $rule['options'] = array();
                } else if (!is_array($rule['options'])) {
                    $rule['options'] = array($rule['options']);
                }
            } else {
                $rule = array(
                        'corrector' => $rule,
                        'options'   => array(),
                        );
            }
            foreach ($names as $name) {
                if (isset($var[$name])) {
                    if (call_user_func_array($rule['corrector'],
                                             array_merge(array(& $var[$name]),
                                                         $rule['options']))) {
                        $corrected = true;
                    }
                }
            }
        }
        return $corrected;
    }

    /**
     * 将制定的变量值校正成整型数
     * @param mixed $var 需要校正的变量
     * @return boolean 如果已经执行了校正操作，返回true，否则返回false
     */
    static public function intCorrector($var) {
        if (is_int($var)) {
            return false;
        } else {
            $var = intval($var);
            return true;
        }
    }

    /**
     * 将制定的变量值校正成浮点数
     * @param mixed $var 需要校正的变量
     * @return boolean 如果已经执行了校正操作，返回true，否则返回false
     */
    static public function floatCorrector($var) {
        if (is_float($var)) {
            return false;
        } else {
            $var = floatval($var);
            return true;
        }
    }

    /**
     * 将制定的变量值校正成时间戳格式
     * @param mixed $var 需要校正的变量
     * @param int $default 如果校正失败后的默认值，如果该值小于0，则默认使用当前时间戳
     * @return boolean 如果已经执行了校正操作，返回true，否则返回false
     */
    static public function timestampCorrector($var, $default = -1) {
        if (is_int($var)) {
            return false;
        } else if (ctype_digit($var)) {
            $var = intval($var);
        } else {
            $var = strtotime($var);
            if (false === $var || 0 > $var) {
                $var = (0 <= $default) ? $default : time();
            }
        }
        return true;
    }

    /**
     * 将制定的变量值校正成数组格式
     * @param mixed $var 需要校正的变量
     * @param string $separator 分割字符串为数组的分隔符，默认是逗号
     * @return boolean 如果已经执行了校正操作，返回true，否则返回false
     */
    static public function arrayCorrector($var, $separator = ',') {
        if (is_array($var)) {
            return false;
        } else {
            $str = strval($var);
            if (false === strpos($str, $separator)) {
                $var = array($str);
            } else {
                $var = explode($separator, $str);
            }
            return true;
        }
    }
}