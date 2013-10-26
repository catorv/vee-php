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
 * 实现数据库存储的Log类
 *
 * @package vee-php\log
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class DbLog {
    /** 日志创建时间戳变量名 */
    const VAR_TIMESTAMP = 'creatime';

    /** 日志消息变量名 */
    const VAR_MESSAGE = 'message';

    /**
     * 数据库连接对象
     * @var Db
     */
    private $db;

    /**
     * 构造函数
     * @param array $params 配置参数
     */
    public function __construct($params) {
        if (!isset($params['db'])) {
            $params['db'] = 0;
        }
        $this->db = V::db($params['db']);
    }


    /**
     * 记录日志
     * @param string $type 日志分类 (对应一个数据表的名称)
     * @param array $data 日志数据(字符串|二维键值对数组)
     * @return boolean 返回是否记录成功
     */
    public function write($type, $data) {
        if (is_array($data)) {
            $data[self::VAR_TIMESTAMP] = time();
        } else {
            $data = array(
                    self::VAR_MESSAGE       => strval($data),
                    self::VAR_TIMESTAMP     => time(),
                    );
        }
        return $this->db->query()->addTable($type)
                                 ->insert($data);
    }

    /**
     * 读取日志信息
     * @param string $type 日志分类 (对应一个数据表的名称)
     * @param int $start 开始读取位置
     * @param int $length 读取日志条目数
     * @return array
     */
    public function read($type, $start = 0, $length = 100) {
        return $this->db->query()->addTable($type)
                                 ->getList(null, $start, $length);
    }

    /**
     * 统计日志条目总数
     * @param string $type 日志分类
     * @return array
     */
    public function count($type) {
        return $this->db->query()->addTable($type)
                                 ->getRecordCount();
    }
}