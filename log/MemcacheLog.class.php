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
 * 实现Memcache存储的Log类
 *
 * @package vee-php\log
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class MemcacheLog {
    /** 日志创建时间戳变量名 */
    const VAR_TIMESTAMP = 'creatime';

    /** 日志消息变量名 */
    const VAR_MESSAGE = 'message';

    /**
     * @var Memcache $memcache Memcached 缓存连接对象
     */
    private $memcache = NULL;

    /**
     * @var string $prefix 变量前缀
     */
    private $prefix = 'vee_log_';

    /**
     * 构造函数
     * @param array $params 配置参数
     */
    public function __construct($params) {
        if ($params['prefix']) {
            $this->prefix = $params['prefix'];
        }
        $this->memcache = new Memcache();
        if (is_array($params['host'])) {
            foreach ($params['host'] as $i => $host) {
                if (is_array($params['port'])) {
                    $port = isset($params['port'][$i]) ? $params['port'][$i]
                                                       : 11211;
                } else {
                    $port = $params['port'];
                }
                $this->memcache->addServer($host, $port, $params['persistent'],
                                           100, $params['timeout']);
            }
        } elseif ($params['persistent']) {
            $this->memcache->pconnect($params['host'], $params['port'],
                                      $params['timeout']);
        } else {
            $this->memcache->connect($params['host'], $params['port'],
                                     $params['timeout']);
        }
    }

    /**
     * 析构函数
     */
    public function __destruct() {
        $this->memcache->close();
    }

    /**
     * 记录日志, 日志数据在内存中将被按以下格式 serialize() 后保存
     * @param string $type 日志分类
     * @param array $data 日志数据
     * @return boolean 返回是否记录成功
     */
    public function write($type, $data) {
        $key = $this->getKey($type);
        $index = $this->memcache->increment($key) - 1;
        if (is_array($data)) {
            $data[self::VAR_TIMESTAMP] = time();
        } else {
            $data = array(
                    self::VAR_MESSAGE       => $data,
                    self::VAR_TIMESTAMP     => time(),
                    );
        }
        return $this->memcache->set($key . '_' . $index, $data);
    }

    /**
     * 读取日志信息
     * @param string $type 日志分类
     * @param int $start 开始读取位置
     * @param int $length 读取日志条目数
     * @return array
     */
    public function read($type, $start = 0, $length = 100) {
        $key = $this->getKey($type);
        $head = $this->memcache->get($key);
        $tail = $this->memcache->get($key . '_');

        $result = array();
        for ($i = $tail + $start, $j = 0; $i<$head && $j<$length; ++$i, ++$j) {
            $data = $this->memcache->get($key . '_' . $i);
            if (is_string($data)) {
                // 某些memcache服务端不会自动反序列，如tokyotyrant
                $data = unserialize($data);
            }
            $result[] = $data;
        }
        return $result;
    }

    /**
     * 统计日志条目总数
     * @param string $type 日志分类
     * @return array
     */
    public function count($type) {
        $key = $this->getKey($type);
        $head = $this->memcache->get($key);
        $tail = $this->memcache->get($key . '_');
        return abs($head - $tail);
    }

    /**
     * 初始化日志队列并返回键名
     * @param string $type 日志分类
     */
    private function getKey($type) {
        static $keys = array();
        if (isset($keys[$type])) {
            return $keys[$type];
        } else {
            $keys[$type] = $this->prefix . $type;
            $isReady = $this->memcache->get($keys[$type]);
            if (false === $isReady) {
                $this->memcache->set($keys[$type], 0);
                $this->memcache->set($keys[$type] . '_', 0);
            }
            return $keys[$type];
        }
    }
}