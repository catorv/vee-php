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
 * 实现Memcache存储的Cache类
 *
 * @package vee-php\cache
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class MemcacheCache {
    /**
     * @var Memcache $connect Memcached 缓存连接对象
     */
    private $connect;

    /**
     * @var string $prefix 变量前缀
     */
    private $prefix = '';

    /**
     * Memcached 服务器的主机名或IP地址(IP地址或多个IP地址组成的数组)
     * @var mixed
     */
    private $host;

    /**
     * 端口号(或多个端口号组成的数组)
     * @var mixed
     */
    private $port;

    /**
     * 连接超时时间(秒)
     * @var int
     */
    private $timeout;

    /**
     * 是否持续化连接
     * @var boolean
     */
    private $persistent;

    /**
     * 构造函数
     * 
     * 配置项如下： 
     * 
     *     host:       Memcached 服务器的主机名或IP地址 
     *     port:       端口号 
     *     timeout:    超时时间 
     *     prefix:     KEY前缀 
     *     persistent: 是否持续化连接 
     * 
     * @param array $params 配置参数
     */
    function __construct($params) {
        $this->host = $params['host'];
        $this->port = $params['port'];
        $this->timeout = $params['timeout'];
        $this->persistent = $params['persistent'];
        if ($params['prefix']) {
            $this->prefix = $params['prefix'];
        }
        $this->connect();
    }

    /**
     * 析构函数
     */
    function __destruct() {
        $this->connect->close();
    }

    /**
     * 连接到服务器
     */
    private function connect() {
        $this->connect = new Memcache();
        if (is_array($this->host)) {
            foreach ($this->host as $i => $h) {
                if (is_array($this->port)) {
                    $p = isset($this->port[$i]) ? $this->port[$i] : 11211;
                } else {
                    $p = $this->port;
                }
                $this->connect->addServer($h, $p, $this->persistent, 100, $this->timeout);
            }
        } elseif ($this->persistent) {
            $this->connect->pconnect($this->host, $this->port, $this->timeout);
        } else {
            $this->connect->connect($this->host, $this->port, $this->timeout);
        }
    }

    /**
     * 重新连接到服务器
     */
    private function reconnect() {
        $this->connect->close();
        $this->connect();
    }

    /**
     * 在Cache中设置键名为$key的项的值，如果该项不存在，则新建一个项
     * @param string $key 键名
     * @param mixed $var 值
     * @param int $expire 过期秒数, 0 无限期,
     *                     也可以用标准日期时间来描述 (strtotime) 过期时间
     * @param int $flag 标志位(详情查阅PHP手册的Memcached部分)
     * @return bool 如果成功则返回 true，失败则返回 false。
     */
    public function set($key, $var, $expire = 0, $flag = 0) {
        if (is_string($expire)) {
            $expire = strtotime($expire);
        } elseif ($expire > 0) {
            $expire = $_SERVER['REQUEST_TIME'] + $expire;
        } else {
            $expire = 0;
        }

        $result = $this->connect->set($this->prefix . $key, $var, $flag, $expire);
        if (false === $result) {
            $this->reconnect();
            $result = $this->connect->set($this->prefix . $key, $var, $flag, $expire);
        }
        return $result;
    }

    /**
     * 在Cache中获取键名为$key的项的值
     * @param string $key 键名
     * @return string 如果该项不存在，则返回false
     */
    public function get($key) {
        $result = $this->connect->get($this->prefix . $key);
        if (false === $result) {
            $this->reconnect();
            $result = $this->connect->get($this->prefix . $key);
        }
        return $result;
    }

    /**
     * 清空Cache中所有项
     * @return boolean 如果成功则返回 true，失败则返回 false。
     */
    public function flush() {
        $result = $this->connect->flush();
        if (false === $result) {
            $this->reconnect();
            $result = $this->connect->flush();
        }
        return $result;
    }

    /**
     * 删除在Cache中键名为$key的项的值
     * @param string $key 键名
     * @return boolean 如果成功则返回 true，失败则返回 false。
     */
    public function delete($key) {
        $result = $this->connect->delete($this->prefix . $key);
        if (false === $result) {
            $this->reconnect();
            $result = $this->connect->delete($this->prefix . $key);
        }
        return $result;
    }

    /**
     * 递增一个项的值
     * @param string $key 键名
     * @param int $value 递增量(默认为1)
     * @return int
     */
    public function increment($key, $value = 1) {
        $result = $this->connect->increment($this->prefix . $key, $value);
        if (false === $result) {
            $this->reconnect();
            $result = $this->connect->increment($this->prefix . $key, $value);
        }
        return $result;
    }

    /**
     * 递减一个项的值
     * @param string $key 键名
     * @param int $value 递减量(默认为1)
     * @return int
     */
    public function decrement($key, $value = 1) {
        $result = $this->connect->decrement($this->prefix . $key, $value);
        if (false === $result) {
            $this->reconnect();
            $result = $this->connect->decrement($this->prefix . $key, $value);
        }
        return $result;
    }
}
