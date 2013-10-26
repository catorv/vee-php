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
 * HTTP数据请求操作辅助器
 * @package vee-php\helpers
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 * @see CurlHepler
 */
class HttpHelper {
    static public $timeout = 10;

    static public function post($data, $host, $path = '/', $port = 80, $header = '') {
        $socket = fsockopen($host, $port, $errno, $errstr, self::$timeout);
        if ($socket) {
            $len = strlen($data);
            $http = "POST {$path} HTTP/1.1\r\nHost: {$host}\r\nContent-Length: {$len}\r\nConnection: Close\r\n{$header}\r\n\r\n{$data}";
            V::log()->write('http_post', "REQUEST>>>\n" . $http);
            fputs($socket, $http, strlen($http));
            $response = '';
            while (!feof($socket)) {
                $response .= fgets($socket, 1024);
            }
            fclose($socket);
            V::log()->write('http_post', "RESPONSE>>>\n" . $response);

            return $response;
        }
        return false;
    }

    static public function get($host, $path = '/', $port = 80, $header = '') {
        $socket = fsockopen($host, $port, $errno, $errstr, self::$timeout);
        if ($socket) {
            if (empty($header)) {
                $http = "GET {$path} HTTP/1.1\r\nHost: {$host}\r\nConnection: Close\r\n\r\n";
            } else {
                $http = "GET {$path} HTTP/1.1\r\nHost: {$host}\r\nConnection: Close\r\n{$header}\r\n\r\n";
            }
            V::log()->write('http_get', "REQUEST>>>\n" . $http);
            fputs($socket, $http, strlen($http));
            $response = '';
            while (!feof($socket)) {
                $response .= fgets($socket, 1024);
            }
            fclose($socket);
            V::log()->write('http_get', "RESPONSE>>>\n" . $response);

            return $response;
        }
        return false;
    }
}
