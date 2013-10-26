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
 * 实现文件存储的Cache类
 *
 * @package vee-php\cache
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class FileCache {
    /**
     * @var string $cachePath 缓存文件根路径
     */
    private $cachePath = PATH_APP_CACHE;

    /**
     * 构造函数
     * @param string $params 配置参数,配置项：
     *                          root: 文件Cache的根路径
     */
    function __construct($params = null) {
        if (isset($params['root']) && !empty($params['root'])) {
            if ($params['root'][strlen($params['root'])-1] != '/') {
                $params['root'] .= '/';
            }
            $this->cachePath = $params['root'];
        }
    }

    /**
     * 在Cache中设置键名为$key的项的值，如果该项不存在，则新建一个项
     * @param string $key 键名
     * @param mixed $var 值
     * @param int $expire 过期秒数, 0 无限期,
     *                    也可以用标准日期时间来描述 (strtotime) 过期时间
     * @param int $flag 标志位(保留参数，暂时无具体意义)
     * @return mixed 如果成功则返回写入文件的字节数，失败则返回 false。
     */
    public function set($key, $var, $expire = 0, $flag = 0) {
        if (is_string($expire)) {
            $expire = strtotime($expire);
        } else if ($expire > 0) {
            $expire = $_SERVER['REQUEST_TIME'] + intval($expire);
        } else {
            $expire = 0;
        }
        
        $value = array('timeout' => $expire);
        $type = gettype($var);
        if ($type == 'object' || $type == 'resource'
                              || $type == 'unknown type') {
            $value['serialize'] = serialize($var);
        } else {
            $value['var'] = $var;
        }
        
        $data = '<?php return ' . var_export($value,true) . ';';
        
        $filename = $this->makeFilename($key);
        if (is_file($filename)) {
             unlink($filename);
        }
        return file_put_contents($filename, $data, LOCK_EX);
    }

    /**
     * 在Cache中获取键名为$key的项的值
     * @param string $key 键名
     * @return mixed 如果该项不存在，则返回 null
     */
    public function get($key) {
        $result = null;
        $file = $this->makeFilename($key);
        if (is_file($file)) {
            $value = include($file);
            if ($value['timeout'] == 0
                    || $_SERVER['REQUEST_TIME'] <= $value['timeout']) {
                $result = isset($value['var'])
                          ? $value['var']
                          : unserialize($value['serialize']);
            } else {
                unlink($file);
            }
        }
        return $result;
    }

    /**
     * 清空Cache中所有项
     * @param string $path 缓存文件路径,如果指定该路径，则只清除该路径之下的所有缓存
     * @return boolean 如果成功则返回 true，失败则返回 false。
     */
    public function flush($path = '') {
        return $this->clearCacheFile($this->cachePath . $path);
    }

    /**
     * 删除在Cache中键名为$key的项的值
     * @param string $key 键名
     * @return boolean 如果成功则返回 true，失败则返回 false。
     */
    public function delete($key) {
        $file = $this->makeFilename($key);
        return is_file($file) ? unlink($file) : false;
    }

    /**
     * 递增一个项的值
     * @param string $key 键名
     * @param int $value 递增量(默认为1)
     * @return int
     */
    public function increment($key, $value = 1) {
        $v = 0;
        $t = 0;
        $file = $this->makeFilename($key);
        if (is_file($file)) {
            $ary = include($file);
            if ($ary['timeout'] == 0
                    || $_SERVER['REQUEST_TIME'] <= $ary['timeout']) {
                $v = intval($ary['var']);
                $t = $ary['timeout'];
            }
        }
        $value += $v;
        $this->set($key, $value, $t);
        return $value;
    }

    /**
     * 递减一个项的值
     * @param string $key 键名
     * @param int $value 递减量(默认为1)
     * @return int
     */
    public function decrement($key, $value = 1) {
        $v = 0;
        $t = 0;
        $file = $this->makeFilename($key);
        if (is_file($file)) {
            $ary = include($file);
            if ($ary['timeout'] == 0 || time() <= $ary['timeout']) {
                $v = intval($ary['var']);
                $t = $ary['timeout'];
            }
        }
        $value = $v - $value;
        $this->set($key, $value, $t);
        return $value;
    }

    /**
     * 获取缓存文件路径及文件名
     * @param string $key 键名
     * @return string
     */
    private function makeFilename($key) {
        $pos = strrpos($key, '/');
        $path = $this->cachePath;
        if (false !== $pos) {
            $path .= substr($key, 0, $pos);
            $key = substr($key, $pos + 1);
        }
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        return $path . '/' . urlencode($key) . '.cache.php';
    }

    /**
     * 清除所有Cache文件(保留目录结构)
     * @param string $path 缓存文件目录
     * @param boolean 是否全部Cache文件都删除成功
     */
    private function clearCacheFile($path) {
        if (is_dir($path)) {
            $d = dir($path);
            while (false !== ($file = $d->read())) {
                if ('.' != $file && '..' != $file) {
                    $file = $path . '/' . $file;
                    if (is_file($file)) {
                        unlink($file);
                    } else {
                        $this->clearCacheFile($file);
                    }
                }
            }
            $d->close();
            return true;
        } else {
            return false;
        }
    }
}
