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
 * 身份验证辅助器
 * @package vee-php\helpers
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class AuthHelper {
    const SESSION_AUTH = 'SESSID'; // Session ID
    const SESSION_TIME = 'SSTIME'; // Session create & last modified timestamp
    const SESSION_DATA = 'SSDATA'; // Session data

    static public $sessionId = null;    // id of current session
    static public $created = 0;         // create timestamp of current session
    static public $modified = 0;        // last modified timestamp of current session
    static public $data = null;         // data of current session

    static public function create($data = null) {
        if (null === $data) {
            $data = self::$data;
        } else {
            self::$data = $data;
        }

        $crypter = new CrypterHelper();
        $logintime = $_SERVER['REQUEST_TIME'];
        $md5 = md5($logintime . $data[Config::VAR_UNAME]);
        $key = substr($md5, 24, 8);
        $s1 = substr($md5, 16, 8);
        $n1 = hexdec($s1);
        $n2 = hexdec($key) ^ filemtime(__FILE__);
        $code = uniqid(substr($md5, 13, 11) . substr(sprintf('%08x', ~$n1 ^ $n2), -8));
        $str = base64_encode($crypter->encrypt(serialize($data), $key . $s1));

        V::set(self::SESSION_AUTH, $code, C::VARST_COOKIE);
        V::set(self::SESSION_TIME, $logintime . ',' . $logintime, C::VARST_COOKIE);
        V::set(self::SESSION_DATA, $str, C::VARST_COOKIE);
    }

    static public function clear() {
        V::set(self::SESSION_AUTH, '', C::VARST_COOKIE);
        V::set(self::SESSION_TIME, '', C::VARST_COOKIE);
        V::set(self::SESSION_DATA, '', C::VARST_COOKIE);
    }

    static public function read() {
        if (null !== self::$data) {
            return self::$data;
        }

        $crypter = new CrypterHelper();

        self::$sessionId = V::get(self::SESSION_AUTH);
        $time = V::get(self::SESSION_TIME);
        $data = V::get(self::SESSION_DATA);

        if (empty(self::$sessionId) || empty($time) || empty($data)) {
            return false;
        }

        $pos = strpos($time, ',');
        if (false === $pos) {
            return false;
        }
        list(self::$created, self::$modified) = explode(',', $time);
        V::set(self::SESSION_TIME, self::$created . ',' . $_SERVER['REQUEST_TIME'], C::VARST_COOKIE);

        $s1 = substr(self::$sessionId, 3, 8);
        $s2 = substr(self::$sessionId, 11, 8);

        $n1 = hexdec($s1);
        $n2 = hexdec($s2)  ^ filemtime(__FILE__);
        $key = substr(sprintf('%08x', ~$n1 ^ $n2), -8);

        self::$data = unserialize($crypter->decrypt(base64_decode($data), $key . $s1));

        return self::$data;
    }

    static public function check($url = '') {
        if (!self::isValid()) {
            if ($url !== false) {
                V::redirect($url ? $url : APP_URL_BASE);
            } else {
                return false;
            }
        } else if (!defined('IN_AGREEMENT') && !self::$data['agreement']) {
            if ($url !== false) {
                V::redirect(makeUrl('agreement'));
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    static public function isValid() {
        return !empty(self::$data);
    }

    static public function getValue($name) {
        return isset(self::$data[$name]) ? self::$data[$name] : null;
    }

    static public function setValue($name, $value) {
        if (!isset(self::$data[$name]) || self::$data[$name] != $value) {
            self::$data[$name] = $value;
            self::create();
        }
    }

    static public function getUserId() {
        return self::getValue(Config::VAR_UID);
    }

    static public function getUserName() {
        return self::getValue(Config::VAR_UNAME);
    }
}