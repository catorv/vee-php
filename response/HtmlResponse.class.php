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
 * 实现HTML输出的Response类
 *
 * @package vee-php\response
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class HtmlResponse extends AResponse {

    /**
     * Response内容输出
     * @param boolean $flag 是否清空输出缓存
     */
    public function output($flag = true) {
        header('Content-Type: text/html; charset=' . Config::$response['charset']);
        if ($flag) {
            $this->flush();
        } else {
            $this->clean();
        }
        Config::$response['autoOutput'] = false;
    }
}
