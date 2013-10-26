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
 * 实现XML格式的Response类
 *
 * @package vee-php\response
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class XmlResponse extends AResponse {
    /** 默认根节点名称 */
    const DEFAULT_ROOT = 'root';
    /** 默认根节点名称 */
    const DEFAULT_DATA = 'data';

    /**
     * 构造函数
     */
    public function __construct() {
        header("Content-Type: text/xml; charset=" . Config::$response['charset']);
    }

    /**
     * Response内容输出
     * @param string $flag 根节点名称
     */
    public function output($flag = null) {
        if ($flag) {
            $this->values = array($flag => $this->values);
        } else if ($this->getOption(AResponse::OPTION_XML_AUTOWRAP, true)) {
            $this->values = array(
                    self::DEFAULT_ROOT => array(
                            self::DEFAULT_DATA => $this->values
                            )
                    );
        }

        if ('utf-8' == strtolower(Config::$response['charset'])) {
            echo chr(0xEF), chr(0xBB), chr(0xBF);
        }
        echo '<?xml version="1.0" encoding="'
                 . Config::$response['charset'] .'"?>'
                 . $this->serializeXml($this->values);
    }

    /**
     * 序列化输出XML字符串
     * @param array &$data 输出数据
     * @param int $level 级数
     * @param string $priorKey 前一级的键名
     * @return string
     */
    protected function & serializeXml(&$data, $level = 0, $priorKey = null) {
        $xml = '';
        foreach ($data as $key => $value) {
            if (false === strpos($key, ' ') && null !== $value) {
                $numericArray = false;
                $attributes = '';
                if (array_key_exists("{$key} attr", $data)) {
                    foreach ($data["{$key} attr"] as $attrName => $attrValue) {
                        $attrValue = & htmlspecialchars($attrValue, ENT_QUOTES);
                        $attributes .= " {$attrName}=\"{$attrValue}\"";
                    }
                }

                if (is_numeric($key)) {
                    $key = $priorKey;
                } else {
                    if (is_array($value) and array_key_exists(0, $value)) {
                        $numericArray = true;
                        $xml .= $this->serializeXml($value, $level, $key);
                    }
                }

                if (!$numericArray) {
                    $xml .= "<{$key}{$attributes}>";
                    if (is_array($value)) {
                        $inner = $this->serializeXml($value, $level+1);
                        if ($inner) {
                            $xml .= $inner;
                        }
                    } else {
                        $xml .= htmlspecialchars($value, ENT_QUOTES);
                    }
                    $xml .= "</{$key}>";
                }
            }
        }
        return $xml;
    }
}