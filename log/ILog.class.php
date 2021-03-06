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
 * Log接口定义
 *
 * @package vee-php\log
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
interface ILog {
    /**
     * 记录日志信息
     * @param string $type 日志分类
     * @param array $data 日志数据
     * @return boolean 返回是否记录成功
     */
    public function write($type, $data);

    /**
     * 读取日志信息
     * @param string $type 日志分类
     * @param int $start 开始读取位置
     * @param int $length 读取日志条目数
     * @return array
     */
    public function read($type, $start = 0, $length = 100);

    /**
     * 统计日志条目总数
     * @param string $type 日志分类
     * @return array
     */
    public function count($type);
}