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
 * Vee基础类
 * @package vee-php\core
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class VeeBase {
    /**
     * 隐藏属性列表，用于setter和getter方法
     * @var array
     */
    private $_properties = array();

    /**
     * 构造函数, 子孙类重载该方法时必须在最后调用parent::__construce，
     * 否则setter和getter支持将无效
     */
    function __construct() {
        $this->_properties = $this->enableGetterAndSetter();
    }

    /**
     * 启用getter和setter方法的支持
     * @return array 返回被隐藏的属性列表
     */
    final private function & enableGetterAndSetter() {
        static $cache;
        if (!isset($cache[get_class($this)])) {
            $cache[get_class($this)] = get_object_vars($this);
        }

        $item = array();
        foreach ($cache[get_class($this)] as $name => $value) {
            $methodName = ucfirst($name);
            if (method_exists($this, 'get' . $methodName)
                    || method_exists($this, 'set' . $methodName)) {
                $item[$name] = & $value;
                unset($this->$name);
            }
        }
        return $item;
    }

    /**
     * 获取隐藏在getter和setter后面的属性列表
     * @return array
     */
    final public function getHiddenProperties() {
        return $this->_properties;
    }

    /**
     * __set方法
     * @param string $name 属性名
     * @param mixed $value 属性值
     */
    final public function __set($name, $value) {
        $setter = 'set' . ucfirst($name);
        if (method_exists($this, $setter)) {
            $this->_properties[$name] = $this->$setter($value);
        } else {
            $this->_properties[$name] = $value;
        }
    }

    /**
     * __get方法
     * @param string $name 属性名
     * @return mixed 返回属性值
     */
    final public function __get($name) {
        if (!isset($this->_properties[$name])) {
            $this->_properties[$name] = null;
        }
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            return $this->$getter($this->_properties[$name]);
        } else {
            return $this->_properties[$name];
        }
    }

    /**
     * __isset方法
     * @param string $name 属性名
     * @return boolean 返回该属性是否存在
     */
    final function __isset($name) {
        return isset($this->_properties[$name])
            || is_null($this->_properties[$name]);
    }

    /**
     * __unset方法
     * @param string $name 属性名
     */
    final function __unset($name) {
        if (isset($this->_properties[$name])) {
            unset($this->_properties[$name]);
        }
    }
}