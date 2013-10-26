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
 * 文件上传处理辅助器
 * @package vee-php\helpers
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class UploadHelper {
    const ERROR_SIZE = -1;
    const ERROR_EXT = -2;
    const ERROR_OTHER = -3;

    static public function image($field, $allowTypes = null) {
        $path = PATH_WEBROOT_UPLOAD . 'images/' . date('Ym/dH/');
        $result = self::save($field, $path, 0, $allowTypes ? $allowTypes : array('png', 'jpg', 'gif'));
        if ($result == UPLOAD_ERR_OK) {
            $image = getimagesize($_FILES[$field]['file']);
            $_FILES[$field]['file'] = substr($_FILES[$field]['file'], strlen(PATH_WEBROOT));
            $_FILES[$field]['width'] = $image[0];
            $_FILES[$field]['height'] = $image[1];
        }
        return $result;
    }

    static public function video($field, $allowTypes = null) {
        $path = PATH_WEBROOT_UPLOAD . 'video/' . date('Ym/dH/');
        $result = self::save($field, $path, 0, $allowTypes ? $allowTypes : array('3gp', 'mp4'));
        if ($result == UPLOAD_ERR_OK) {
            $_FILES[$field]['file'] = substr($_FILES[$field]['file'], strlen(PATH_WEBROOT));
        }
        return $result;
    }

    static public function music($field, $allowTypes = null) {
        $path = PATH_WEBROOT_UPLOAD . 'music/' . date('Ym/dH/');
        $result = self::save($field, $path, 0, $allowTypes ? $allowTypes : array('mp3'));
        if ($result == UPLOAD_ERR_OK) {
            $_FILES[$field]['file'] = substr($_FILES[$field]['file'], strlen(PATH_WEBROOT));
        }
        return $result;
    }

    static public function attach($field, $allowTypes = array()) {
        $path = PATH_WEBROOT_UPLOAD . 'attach/' . date('Ym/dH/');
        $result = self::save($field, $path, 0, $allowTypes);
        if ($result == UPLOAD_ERR_OK) {
            $_FILES[$field]['file'] = substr($_FILES[$field]['file'], strlen(PATH_WEBROOT));
        }
        return $result;
    }

    static public function save($field, $path = null, $maxSize = 0,
                                  $allowTypes = array()) {
        if ($_FILES[$field]['error'] != UPLOAD_ERR_OK) {
            return $_FILES[$field]['error'];
        }

        if ($maxSize > 0 && $_FILES[$field]['size'] > $maxSize) {
            return self::ERROR_SIZE;
        }

        $_FILES[$field]['name'] = self::encode($_FILES[$field]['name']);
        $pos = strrpos($_FILES[$field]['name'], '.');
        if ($pos === false) {
            $name = $_FILES[$field]['name'];
            $_FILES[$field]['ext'] = '';
        } else {
            $name = substr($_FILES[$field]['name'], 0, $pos);
            $_FILES[$field]['ext'] = strtolower(substr($_FILES[$field]['name'], $pos+1));
        }
        if ($allowTypes && !in_array($_FILES[$field]['ext'], $allowTypes)) {
            return self::ERROR_EXT;
        }

        if (!$path) {
            $path = PATH_APP_UPLOAD . date('Ym/dH/');
        }
        if (!is_dir($path)) { //如果目录不存在,则自动创建
            mkdir($path, 0755, true);
        }

        $_FILES[$field]['file'] = $path . $_FILES[$field]['name'];
        $i = 1;
        while (is_file($_FILES[$field]['file'])) {
            $_FILES[$field]['file'] = $path . $name . '_' . ($i++) . '.' . $_FILES[$field]['ext'];
        }

        if (move_uploaded_file($_FILES[$field]['tmp_name'], $_FILES[$field]['file'])) {
            $_FILES[$field]['file'] = strtr($_FILES[$field]['file'], '\\', '/');
            return UPLOAD_ERR_OK;
        } else {
            return self::ERROR_OTHER;
        }
    }

    static public function encode($s) {
        return strtr(urlencode($s), '%', '~');
    }

    static public function decode($s) {
        return urldecode(strtr($s, '~', '%'));
    }
}