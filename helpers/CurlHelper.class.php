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
 * CURL辅助器
 * @package vee-php\helpers
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class CurlHelper {
    /** 默认的保存Cookie数据的文件路径 */
    static public $cookie = '/tmp/cookie.txt';

    /** 最后一次执行CURL返回的 HTTP Code */
    static public $httpCode = 0;
    /** 最后一次执行CURL返回的 Content Type */
    static public $contentType = '';

    /** 全局CURL参数数组，改变该数组后，对所有CURL操作有效 */
    static public $options = array(
            CURLOPT_SSL_VERIFYPEER    => false,
            CURLOPT_SSL_VERIFYHOST    => 2,
            CURLOPT_USERAGENT         => 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0)',
            CURLOPT_RETURNTRANSFER    => true,
            CURLOPT_TIMEOUT           => 10,
            );

    /**
     * 通过 GET 方式调用 CURL
     *
     * @param string $url 将要访问的URL
     * @param mixed $data string|array，将要传递的参数
     * @param mixed $usecookie true|false|实际文件路径，如果为true，cookie保存在默认路径 self::$cookie
     * @return string
     */
    static public function get($url, $data = '', $usecookie = true) {
        if ($data) {
            if (is_array($data)) {
                $data = http_build_query($data);
            }
            $url .= (strpos($url, '?') === false ? '?' : '&') . $data;
        }
        return self::open($url, null, $usecookie);
    }

    /**
     * 通过 POST 方式调用 CURL
     *
     * @param string $url 将要访问的URL
     * @param mixed $data string|array，将要传递的参数
     * @param mixed $usecookie true|false|实际文件路径，如果为true，cookie保存在默认路径 self::$cookie
     * @return string
     */
    static public function post($url, $data = '', $usecookie = true) {
        return self::open($url, array(
                    CURLOPT_POST        => true,
                    CURLOPT_POSTFIELDS  => $data,
                    ), $usecookie);
    }

    /**
     * 调用 CURL
     *
     * @param string $url URL
     * @param array $options CURL参数数组
     * @param mixed $usecookie true|false|实际文件路径，如果为true，cookie保存在默认路径 self::$cookie
     * @return string
     */
    static public function open($url, $options = null, $usecookie = true) {
        if ($usecookie === true) {
            $usecookie = self::$cookie;
        }
        if ($usecookie && (!file_exists($usecookie) || !is_writable($usecookie))) {
            $usecookie = false;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, self::$options);

        if ($usecookie) {
            $options[CURLOPT_COOKIEJAR] = $usecookie;
            $options[CURLOPT_COOKIEFILE] = $usecookie;
        }

        if ($options && is_array($options)) {
            curl_setopt_array($ch, $options);
        }

        $result = curl_exec($ch);

        self::$httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        self::$contentType  = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        curl_close($ch);

        return $result;
    }
}
