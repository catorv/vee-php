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
 * 实现CVS格式的Response类
 *
 * @package vee-php\response
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class CsvResponse extends AResponse {
    /**
     * 构造函数
     */
    public function __construct() {
        header('Pragma: cache');
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Accept-Ranges: bytes');
        header('Content-type: application/octet-stream');
    }

    /**
     * Response内容输出
     * 
     * @param boolean $filename CSV文件名
     */
    public function output($filename = null) {
        V::loadHelper('CsvHelper');
        $csv = CsvHelper::getInstance();
        if (isset($this->values['title'])) {
            $csv->setTitle($this->values['title']);
            unset($this->values['title']);
        }
        $str = $csv->putcsv($this->values);

        if (!$filename) {
            $filename = $this->getOption('filename', 'noname.csv');
        }

        header('Content-Length: ' . strlen($str));
        header('Content-Disposition: attachment; filename='
               . urlencode($filename));
        echo $str;
    }
}