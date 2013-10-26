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
 * 实现Extjs的Response类
 *
 * @package vee-php\response
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class ExtjsResponse extends AResponse {
    /** 模板文件扩展名 */
    const FILEEXT_TPL       = '.ext.js';

    /**
     * Response内容输出
     * @param boolean $flag 无意义
     * @return string JSON结果
     */
    public function output($flag = null) {
        extract($this->values, EXTR_OVERWRITE);
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                  && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
            if (isset($_SERVER['HTTP_REMOTEVIEW'])
                    && $_SERVER['HTTP_REMOTEVIEW'] == 'FULL') {
                try {
                    $file = $this->getTemplateFilename(self::FILEEXT_TPL);
                    echo 'var config={};';
                    echo 'if (Ext.isEmpty(data)) data=',
                         json_encode($this->values), ';';
                    require $file;
                    echo ';return config;';
                } catch (Exception $e) {
                    echo 'return ' . json_encode(array(
                        'iconCls' => 'icon-default',
                        'title' => '模板调用错误',
                        'html' => '错误信息：' . $e->getMessage(),
                    )) . ';';
                }
            } else {
                echo json_encode($this->values);
            }
        } else {
            $url = V::makeUrl();
            if (is_file(PATH_APP_CONTROLLERS . 'Admin_Frame.tpl.php')) {
                require PATH_APP_CONTROLLERS . 'Admin_Frame.tpl.php';
            } else {
                require PATH_APP_VIEWS . 'Admin_Frame.tpl.php';
            }
        }
    }
}