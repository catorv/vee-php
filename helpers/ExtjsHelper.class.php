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
 * ExtJS框架服务端响应辅助器
 * @package vee-php\helpers
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class ExtjsHelper {
    /**
     * 请求成功状态
     * @var boolean
     */
    static public $success;
    /**
     * 返回的数据集
     * @var array
     */
    static public $data;
    /**
     * 数据集记录总数
     * @var int
     */
    static public $total;
    /**
     * 返回的消息内容
     * @var unknown_type
     */
    static public $message;
    /**
     * 返回的错误集
     * @var array
     */
    static public $errors;
    /**
     * AJAX的tid值
     * @var int
     */
    static public $tid;
    /**
     * 保留（暂时不知道用途）
     * @var unknown_type
     */
    static public $trace;

    /**
     * 设置请求成功状态
     * @param boolean $success 默认true
     * @param string $message 默认为null
     */
    static public function success($success = true, $message = null) {
        self::$success = ($success === true);
        if (null !== $message) {
            self::$message = $message;
        }
    }

    /**
     * 添加返回数据
     * @param array $data 单条记录数据
     */
    static public function addData($data) {
        if (null === self::$data) {
            self::$data = array();
        }
        self::$data[] = $data;
    }

    /**
     * 添加返回错误信息
     * @param array $error 错误信息，格式为 array('id'=>'fieldid/fieldname',
     *                                           'msg'=>'error message')
     */
    static public function addError($id, $msg) {
    if (null === self::$errors) {
            self::$errors = array();
        }
        self::$errors[] = array('id' => $id, 'msg' => $msg);
    }

    /**
     * 将响应数据应用到响应对象中
     * @param AResponse $response 如果不指定，默认使用JsonResponse
     */
    static public function response($response = null) {
        if (null === $response) {
            $response = V::response('Json');
        }
        if (null !== self::$success) {
            $response->value('success', self::$success);
        }
        if (null !== self::$message) {
            $response->value('message', self::$message);
        }
        if (null !== self::$total) {
            $response->value('total', self::$total);
        }
        if (null !== self::$data) {
            $response->value('data', self::$data);
        }
        if (null !== self::$errors) {
            $response->value('errors', self::$errors);
        }
        if (null !== self::$tid) {
            $response->value('tid', self::$tid);
        }
        if (null !== self::$trace) {
            $response->value('trace', self::$trace);
        }
    }

    /**
     * 获取客户端提交的分页信息
     */
    static public function pagerParams() {
        $start  = intval(v::get('start'));
        $limit  = v::get('limit') ? intval(v::get('limit')) : Config::$response['pageSize'];
        $sort   = v::get('sort');
        $dir    = v::get('dir');
        return array($start, $limit, $sort, $dir);
    }
}