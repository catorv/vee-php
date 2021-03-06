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
 * Cache 接口定义
 *
 * @package vee-php\cache
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
interface ICache {
    /**
     * 在Cache中设置键为$key的项的值，如果该项不存在，则新建一个项
     * @param string $key 键值
     * @param mixed $var 值
     * @param int $expire 过期秒数, 0 无限期,
     *                     也可以用标准日期时间来描述 (strtotime) 过期时间
     * @param int $flag 标志位，例如 memcache 的 MEMCACHE_COMPRESSED
     * @return boolean 如果成功则返回 true，失败则返回 false。
     */
    public function set($key, $var, $expire = 0, $flag = 0);

    /**
     * 在Cache中获取键名为$key的项的值
     * @param string $key 键名
     * @return mixed 如果该项不存在，则返回 NULL
     */
    public function get($key);

    /**
     * 清空Cache中所有项
     * @return boolean 如果成功则返回 TRUE，失败则返回 FALSE。
     */
    public function flush();

    /**
     * 删除在Cache中键名为$key的项的值
     * @param string $key 键名
     * @return boolean 如果成功则返回 true，失败则返回 false。
     * @access public
     */
    public function delete($key);

    /**
     * 递增一个项的值
     * @param string $key 键名
     * @param int $value 递增量(默认为1)
     * @return int
     */
    public function increment($key, $value = 1);

    /**
     * 递减一个项的值
     * @param string $key 键名
     * @param int $value 递减量(默认为1)
     * @return int
     */
    public function decrement($key, $value = 1);
}