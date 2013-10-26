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
define('HDOM_TYPE_ELEMENT',     1);
define('HDOM_TYPE_COMMENT',     2);
define('HDOM_TYPE_TEXT',        3);
define('HDOM_TYPE_ENDTAG',      4);
define('HDOM_TYPE_ROOT',        5);
define('HDOM_TYPE_UNKNOWN',     6);
define('HDOM_QUOTE_DOUBLE',     0);
define('HDOM_QUOTE_SINGLE',     1);
define('HDOM_QUOTE_NO',         3);
define('HDOM_INFO_BEGIN',       0);
define('HDOM_INFO_END',         1);
define('HDOM_INFO_QUOTE',       2);
define('HDOM_INFO_SPACE',       3);
define('HDOM_INFO_TEXT',        4);
define('HDOM_INFO_INNER',       5);
define('HDOM_INFO_OUTER',       6);
define('HDOM_INFO_ENDSPACE',    7);
/**
 * SimpleHtmlDom辅助器
 * 
 * 本辅助器代码根据 Simple Html Dom 项目修改而来，
 * 
 * Simple Html Dom 项目的版本及版权信息如下：
 *
 * Version: 1.11 ($Rev: 175 $)
 * 
 * Author: S.C. Chen <me578022@gmail.com>
 * 
 * Acknowledge: Jose Solorzano (https://sourceforge.net/projects/php-html/)
 * 
 * Contributions by:
 * 
 *     Yousuke Kumakura (Attribute filters)
 *     Vadim Voituk (Negative indexes supports of "find" method)
 *     Antcs (Constructor with automatically load contents either text or file/url)
 *     
 * Licensed under The MIT License
 * 
 * Redistributions of files must retain the above copyright notice.
 * 
 * @link http://sourceforge.net/projects/simplehtmldom/
 * @package vee-php\helpers
 */
class SimpleHtmlDomHelper {
    public $root = null;
    public $nodes = array();
    public $callback = null;
    public $lowercase = false;
    protected $pos;
    protected $doc;
    protected $char;
    protected $size;
    protected $cursor;
    protected $parent;
    protected $noise = array();
    protected $token_blank = " \t\r\n";
    protected $token_equal = ' =/>';
    protected $token_slash = " />\r\n\t";
    protected $token_attr = ' >';
    // use isset instead of in_array, performance boost about 30%...
    protected $self_closing_tags = array('img'=>1, 'br'=>1, 'input'=>1, 'meta'=>1, 'link'=>1, 'hr'=>1, 'base'=>1, 'embed'=>1, 'spacer'=>1);
    protected $block_tags = array('root'=>1, 'body'=>1, 'form'=>1, 'div'=>1, 'span'=>1, 'table'=>1);
    protected $optional_closing_tags = array(
        'tr'=>array('tr'=>1, 'td'=>1, 'th'=>1),
        'th'=>array('th'=>1),
        'td'=>array('td'=>1),
        'li'=>array('li'=>1),
        'dt'=>array('dt'=>1, 'dd'=>1),
        'dd'=>array('dd'=>1, 'dt'=>1),
        'dl'=>array('dd'=>1, 'dt'=>1),
        'p'=>array('p'=>1),
        'nobr'=>array('nobr'=>1),
    );

    private function __construct($str=null) {
        if ($str) {
            if (preg_match("/^http:\/\//i",$str) || is_file($str))
                $this->loadFromFile($str);
            else
                $this->load($str);
        }
    }

    function __destruct() {
        $this->clear();
    }

    /**
     * 返回 SimpleHtmlDomHelper 实例
     * @param string $str 需要被解析的HTML字符串或URL地址
     * @return SimpleHtmlDomHelper
     */
    static public function getInstance($str = null) {
        return new SimpleHtmlDomHelper($str);
    }

    /**
     * load html from string
     * 
     * 从字符串中载入HTML内容
     * @param string $str HTML字符串
     * @param boolean $lowercase 是否使用小写字母
     * @return unknown_type
     */
    function load($str, $lowercase=true) {
        // prepare
        $this->prepare($str, $lowercase);
        // strip out comments
        $this->removeNoise("'<!--(.*?)-->'is");
        // strip out cdata
        $this->removeNoise("'<!\[CDATA\[(.*?)\]\]>'is", true);
        // strip out <style> tags
        $this->removeNoise("'<\s*style[^>]*[^/]>(.*?)<\s*/\s*style\s*>'is");
        $this->removeNoise("'<\s*style\s*>(.*?)<\s*/\s*style\s*>'is");
        // strip out <script> tags
        $this->removeNoise("'<\s*script[^>]*[^/]>(.*?)<\s*/\s*script\s*>'is");
        $this->removeNoise("'<\s*script\s*>(.*?)<\s*/\s*script\s*>'is");
        // strip out preformatted tags
        $this->removeNoise("'<\s*(?:code)[^>]*>(.*?)<\s*/\s*(?:code)\s*>'is");
        // strip out server side scripts
        $this->removeNoise("'(<\?)(.*?)(\?>)'s", true);
        // strip smarty scripts
        $this->removeNoise("'(\{\w)(.*?)(\})'s", true);

        // parsing
        while ($this->parse());
        // end
        $this->root->_[HDOM_INFO_END] = $this->cursor;
    }

    /**
     * load html from file
     * 
     * 从文件中装载HTML内容,参数内容同函数file_get_contents()
     */
    function loadFromFile() {
        $args = func_get_args();
        $this->load(call_user_func_array('file_get_contents', $args), true);
    }

    /**
     * set callback function
     * 
     * 设置回调函数
     * @param $function_name
     */
    function setCallback($functionName) {
        $this->callback = $functionName;
    }

    /**
     * remove callback function
     * 
     * 删除回调函数
     */
    function removeCallback() {
        $this->callback = null;
    }

    /**
     * save dom as string
     * 
     * 保存DOM为字符串,即将DOM对象序列化成HTML文本
     * @param string $filepath 文件名,如果指定$filepath,则保存HTML文件到该文件中
     * @return string 返回序列化后的HTML文本,无论是否指定$filepath
     */
    function save($filepath='') {
        $ret = $this->root->innertext();
        if ($filepath!=='') file_put_contents($filepath, $ret);
        return $ret;
    }

    /**
     * find dom node by css selector
     * 
     * 用CSS选择器查找元素,如果$idx不等于null,则返回所有匹配的就元素节点
     * @param string $selector CSS选择器
     * @param int $idx 如果指定该索引值,则返回第$idx个节点
     * @return SimpleHtmlDomNode
     */
    function find($selector, $idx=null) {
        return $this->root->find($selector, $idx);
    }

    /**
     * clean up memory due to php5 circular references memory leak...
     * 
     * 清除内存
     */
    function clear() {
        foreach($this->nodes as $n) {$n->clear(); $n = null;}
        if (isset($this->parent)) {$this->parent->clear(); unset($this->parent);}
        if (isset($this->root)) {$this->root->clear(); unset($this->root);}
        unset($this->doc);
        unset($this->noise);
    }

//    /**
//     * 输出DOM结构
//     */
//    function dump($show_attr=true) {
//        $this->root->dump($show_attr);
//    }

    /**
     * prepare HTML data and init everything
     * 
     * 预处理HTML数据并初始化对象属性
     * @param string $str HTML数据
     * @param boolean $lowercase 是否使用小写字母
     */
    protected function prepare($str, $lowercase=true) {
        $this->clear();
        $this->doc = $str;
        $this->pos = 0;
        $this->cursor = 1;
        $this->noise = array();
        $this->nodes = array();
        $this->lowercase = $lowercase;
        $this->root = new SimpleHtmlDomNode($this);
        $this->root->tag = 'root';
        $this->root->_[HDOM_INFO_BEGIN] = -1;
        $this->root->nodetype = HDOM_TYPE_ROOT;
        $this->parent = $this->root;
        // set the length of content
        $this->size = strlen($str);
        if ($this->size>0) $this->char = $this->doc[0];
    }

    /**
     * parse html content
     * 
     * 解析HTML内容
     * @return boolean
     */
    protected function parse() {
        if (($s = $this->copyUntilChar('<'))==='')
            return $this->readTag();

        // text
        $node = new SimpleHtmlDomNode($this);
        ++$this->cursor;
        $node->_[HDOM_INFO_TEXT] = $s;
        $this->linkNodes($node, false);
        return true;
    }

    /**
     * read tag info
     * 
     * 读取标签信息
     * @return boolean
     */
    protected function readTag() {
        if ($this->char!=='<') {
            $this->root->_[HDOM_INFO_END] = $this->cursor;
            return false;
        }
        $begin_tag_pos = $this->pos;
        $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next

        // end tag
        if ($this->char==='/') {
            $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
            $this->skip($this->token_blank_t);
            $tag = $this->copyUntilChar('>');

            // skip attributes in end tag
            if (($pos = strpos($tag, ' '))!==false)
                $tag = substr($tag, 0, $pos);

            $parent_lower = strtolower($this->parent->tag);
            $tag_lower = strtolower($tag);

            if ($parent_lower!==$tag_lower) {
                if (isset($this->optional_closing_tags[$parent_lower]) && isset($this->block_tags[$tag_lower])) {
                    $this->parent->_[HDOM_INFO_END] = 0;
                    $org_parent = $this->parent;

                    while (($this->parent->parent) && strtolower($this->parent->tag)!==$tag_lower)
                        $this->parent = $this->parent->parent;

                    if (strtolower($this->parent->tag)!==$tag_lower) {
                        $this->parent = $org_parent; // restore origonal parent
                        if ($this->parent->parent) $this->parent = $this->parent->parent;
                        $this->parent->_[HDOM_INFO_END] = $this->cursor;
                        return $this->asTextNode($tag);
                    }
                }
                else if (($this->parent->parent) && isset($this->block_tags[$tag_lower])) {
                    $this->parent->_[HDOM_INFO_END] = 0;
                    $org_parent = $this->parent;

                    while (($this->parent->parent) && strtolower($this->parent->tag)!==$tag_lower)
                        $this->parent = $this->parent->parent;

                    if (strtolower($this->parent->tag)!==$tag_lower) {
                        $this->parent = $org_parent; // restore origonal parent
                        $this->parent->_[HDOM_INFO_END] = $this->cursor;
                        return $this->asTextNode($tag);
                    }
                }
                else if (($this->parent->parent) && strtolower($this->parent->parent->tag)===$tag_lower) {
                    $this->parent->_[HDOM_INFO_END] = 0;
                    $this->parent = $this->parent->parent;
                }
                else
                    return $this->asTextNode($tag);
            }

            $this->parent->_[HDOM_INFO_END] = $this->cursor;
            if ($this->parent->parent) $this->parent = $this->parent->parent;

            $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
            return true;
        }

        $node = new SimpleHtmlDomNode($this);
        $node->_[HDOM_INFO_BEGIN] = $this->cursor;
        ++$this->cursor;
        $tag = $this->copyUntil($this->token_slash);

        // doctype, cdata & comments...
        if (isset($tag[0]) && $tag[0]==='!') {
            $node->_[HDOM_INFO_TEXT] = '<' . $tag . $this->copyUntilChar('>');

            if (isset($tag[2]) && $tag[1]==='-' && $tag[2]==='-') {
                $node->nodetype = HDOM_TYPE_COMMENT;
                $node->tag = 'comment';
            } else {
                $node->nodetype = HDOM_TYPE_UNKNOWN;
                $node->tag = 'unknown';
            }

            if ($this->char==='>') $node->_[HDOM_INFO_TEXT].='>';
            $this->linkNodes($node, true);
            $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
            return true;
        }

        // text
        if ($pos=strpos($tag, '<')!==false) {
            $tag = '<' . substr($tag, 0, -1);
            $node->_[HDOM_INFO_TEXT] = $tag;
            $this->linkNodes($node, false);
            $this->char = $this->doc[--$this->pos]; // prev
            return true;
        }

        if (!preg_match("/^[\w-:]+$/", $tag)) {
            $node->_[HDOM_INFO_TEXT] = '<' . $tag . $this->copyUntil('<>');
            if ($this->char==='<') {
                $this->linkNodes($node, false);
                return true;
            }

            if ($this->char==='>') $node->_[HDOM_INFO_TEXT].='>';
            $this->linkNodes($node, false);
            $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
            return true;
        }

        // begin tag
        $node->nodetype = HDOM_TYPE_ELEMENT;
        $tag_lower = strtolower($tag);
        $node->tag = ($this->lowercase) ? $tag_lower : $tag;

        // handle optional closing tags
        if (isset($this->optional_closing_tags[$tag_lower]) ) {
            while (isset($this->optional_closing_tags[$tag_lower][strtolower($this->parent->tag)])) {
                $this->parent->_[HDOM_INFO_END] = 0;
                $this->parent = $this->parent->parent;
            }
            $node->parent = $this->parent;
        }

        $guard = 0; // prevent infinity loop
        $space = array($this->copySkip($this->token_blank), '', '');

        // attributes
        do {
            if ($this->char!==null && $space[0]==='') break;
            $name = $this->copyUntil($this->token_equal);
            if($guard===$this->pos) {
                $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
                continue;
            }
            $guard = $this->pos;

            // handle endless '<'
            if($this->pos>=$this->size-1 && $this->char!=='>') {
                $node->nodetype = HDOM_TYPE_TEXT;
                $node->_[HDOM_INFO_END] = 0;
                $node->_[HDOM_INFO_TEXT] = '<'.$tag . $space[0] . $name;
                $node->tag = 'text';
                $this->linkNodes($node, false);
                return true;
            }

            // handle mismatch '<'
            if($this->doc[$this->pos-1]=='<') {
                $node->nodetype = HDOM_TYPE_TEXT;
                $node->tag = 'text';
                $node->attr = array();
                $node->_[HDOM_INFO_END] = 0;
                $node->_[HDOM_INFO_TEXT] = substr($this->doc, $begin_tag_pos, $this->pos-$begin_tag_pos-1);
                $this->pos -= 2;
                $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
                $this->linkNodes($node, false);
                return true;
            }

            if ($name!=='/' && $name!=='') {
                $space[1] = $this->copySkip($this->token_blank);
                $name = $this->restoreNoise($name);
                if ($this->lowercase) $name = strtolower($name);
                if ($this->char==='=') {
                    $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
                    $this->parseAttr($node, $name, $space);
                }
                else {
                    //no value attr: nowrap, checked selected...
                    $node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_NO;
                    $node->attr[$name] = true;
                    if ($this->char!='>') $this->char = $this->doc[--$this->pos]; // prev
                }
                $node->_[HDOM_INFO_SPACE][] = $space;
                $space = array($this->copySkip($this->token_blank), '', '');
            }
            else
                break;
        } while($this->char!=='>' && $this->char!=='/');

        $this->linkNodes($node, true);
        $node->_[HDOM_INFO_ENDSPACE] = $space[0];

        // check self closing
        if ($this->copyUntilCharEscape('>')==='/') {
            $node->_[HDOM_INFO_ENDSPACE] .= '/';
            $node->_[HDOM_INFO_END] = 0;
        }
        else {
            // reset parent
            if (!isset($this->self_closing_tags[strtolower($node->tag)])) $this->parent = $node;
        }
        $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
        return true;
    }

    /**
     * parse attributes
     * 
     * 解析属性
     * @param SimpleHtmlDomNode $node 节点
     * @param string $name 属性名
     * @param array $space 空白字符
     */
    protected function parseAttr($node, $name, &$space) {
        $space[2] = $this->copySkip($this->token_blank);
        switch($this->char) {
            case '"':
                $node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_DOUBLE;
                $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
                $node->attr[$name] = $this->restoreNoise($this->copyUntilCharEscape('"'));
                $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
                break;
            case '\'':
                $node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_SINGLE;
                $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
                $node->attr[$name] = $this->restoreNoise($this->copyUntilCharEscape('\''));
                $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
                break;
            default:
                $node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_NO;
                $node->attr[$name] = $this->restoreNoise($this->copyUntil($this->token_attr));
        }
    }

    /**
     * link node's parent
     * 
     * 链接节点的父节点
     * @param SimpleHtmlDomNode $node 节点
     * @param boolean $isChild 是否是子节点
     */
    protected function linkNodes(&$node, $isChild) {
        $node->parent = $this->parent;
        $this->parent->nodes[] = $node;
        if ($isChild)
            $this->parent->children[] = $node;
    }

    /**
     * as a text node
     * 
     * 作为一个TEXT节点
     * @param string $tag 标签
     * @return boolean
     */
    protected function asTextNode($tag) {
        $node = new SimpleHtmlDomNode($this);
        ++$this->cursor;
        $node->_[HDOM_INFO_TEXT] = '</' . $tag . '>';
        $this->linkNodes($node, false);
        $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
        return true;
    }

    /**
     * 跳过$chars中指定的字符
     * @param string $chars 字符串
     */
    protected function skip($chars) {
        $this->pos += strspn($this->doc, $chars, $this->pos);
        $this->char = ($this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
    }

    /**
     * 复制并跳过$chars中指定的字符
     * @param string $chars 字符串
     * @return string
     */
    protected function copySkip($chars) {
        $pos = $this->pos;
        $len = strspn($this->doc, $chars, $pos);
        $this->pos += $len;
        $this->char = ($this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
        if ($len===0) return '';
        return substr($this->doc, $pos, $len);
    }

    /**
     * 复制直到$chars中指定的字符
     * @param string $chars 字符串
     * @return string
     */
    protected function copyUntil($chars) {
        $pos = $this->pos;
        $len = strcspn($this->doc, $chars, $pos);
        $this->pos += $len;
        $this->char = ($this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
        return substr($this->doc, $pos, $len);
    }

    /**
     * 复制直到遇到字符$char
     * @param string $char 字符
     * @return string
     */
    protected function copyUntilChar($char) {
        if ($this->char===null) return '';

        if (($pos = strpos($this->doc, $char, $this->pos))===false) {
            $ret = substr($this->doc, $this->pos, $this->size-$this->pos);
            $this->char = null;
            $this->pos = $this->size;
            return $ret;
        }

        if ($pos===$this->pos) return '';
        $pos_old = $this->pos;
        $this->char = $this->doc[$pos];
        $this->pos = $pos;
        return substr($this->doc, $pos_old, $pos-$pos_old);
    }

    /**
     * 复制直到遇到字符$char
     * @param string $char 字符
     * @return string
     */
    protected function copyUntilCharEscape($char) {
        if ($this->char===null) return '';

        $start = $this->pos;
        while(1) {
            if (($pos = strpos($this->doc, $char, $start))===false) {
                $ret = substr($this->doc, $this->pos, $this->size-$this->pos);
                $this->char = null;
                $this->pos = $this->size;
                return $ret;
            }

            if ($pos===$this->pos) return '';

            if ($this->doc[$pos-1]==='\\') {
                $start = $pos+1;
                continue;
            }

            $pos_old = $this->pos;
            $this->char = $this->doc[$pos];
            $this->pos = $pos;
            return substr($this->doc, $pos_old, $pos-$pos_old);
        }
    }

    /**
     * remove noise from html content
     * 
     * 从HTML内容中删除干扰因素
     * @param string $pattern 正则模板
     * @param boolean $removeTag 是否一起标签
     */
    protected function removeNoise($pattern, $removeTag=false) {
        $count = preg_match_all($pattern, $this->doc, $matches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE);

        for ($i=$count-1; $i>-1; --$i) {
            $key = '___noise___'.sprintf('% 3d', count($this->noise)+100);
            $idx = ($removeTag) ? 0 : 1;
            $this->noise[$key] = $matches[$i][$idx][0];
            $this->doc = substr_replace($this->doc, $key, $matches[$i][$idx][1], strlen($matches[$i][$idx][0]));
        }

        // reset the length of content
        $this->size = strlen($this->doc);
        if ($this->size>0) $this->char = $this->doc[0];
    }

    /**
     * restore noise to html content
     * 
     * 恢复HTML内容中的干扰因素
     * @param string $text HTML内容字符串
     * @return string
     */
    function restoreNoise($text) {
        while(($pos=strpos($text, '___noise___'))!==false) {
            $key = '___noise___'.$text[$pos+11].$text[$pos+12].$text[$pos+13];
            if (isset($this->noise[$key]))
                $text = substr($text, 0, $pos).$this->noise[$key].substr($text, $pos+14);
        }
        return $text;
    }

    function __toString() {
        return $this->root->innertext();
    }

    function __get($name) {
        switch($name) {
            case 'outertext': return $this->root->innertext();
            case 'innertext': return $this->root->innertext();
            case 'plaintext': return $this->root->text();
        }
    }

    // camel naming conventions
    function childNodes($idx=-1) {return $this->root->childNodes($idx);}
    function firstChild() {return $this->root->first_child();}
    function lastChild() {return $this->root->last_child();}
    function getElementById($id) {return $this->find("#$id", 0);}
    function getElementsById($id, $idx=null) {return $this->find("#$id", $idx);}
    function getElementByTagName($name) {return $this->find($name, 0);}
    function getElementsByTagName($name, $idx=-1) {return $this->find($name, $idx);}
    function loadFile() {$args = func_get_args();$this->load(call_user_func_array('file_get_contents', $args), true);}
}

/**
 * Simple HTML DOM 节点对象
 * 
 * 本辅助器代码根据 Simple Html Dom 项目修改而来，
 * 
 * Simple Html Dom 项目的版本及版权信息如下：
 * 
 *
 * Version: 1.11 ($Rev: 175 $)
 * 
 * Author: S.C. Chen <me578022@gmail.com>
 * 
 * Acknowledge: Jose Solorzano (https://sourceforge.net/projects/php-html/)
 * 
 * Contributions by:
 * 
 *     Yousuke Kumakura (Attribute filters)
 *     Vadim Voituk (Negative indexes supports of "find" method)
 *     Antcs (Constructor with automatically load contents either text or file/url)
 *     
 * Licensed under The MIT License
 * 
 * Redistributions of files must retain the above copyright notice.
 * 
 * @link http://sourceforge.net/projects/simplehtmldom/
 * @package vee-php\helpers
 */
class SimpleHtmlDomNode {
    public $nodetype = HDOM_TYPE_TEXT;
    public $tag = 'text';
    public $attr = array();
    public $children = array();
    public $nodes = array();
    public $parent = null;
    public $_ = array();
    private $dom = null;

    function __construct($dom) {
        $this->dom = $dom;
        $dom->nodes[] = $this;
    }

    function __destruct() {
        $this->clear();
    }

    function __toString() {
        return $this->outertext();
    }

    /**
     * 清除内存
     */
    function clear() {
        $this->dom = null;
        $this->nodes = null;
        $this->parent = null;
        $this->children = null;
    }

//    /**
//     * 输出DOM结构
//     */
//    function dump($show_attr=true) {
//        dump_html_tree($this, $show_attr);
//    }

    /**
     * 获取父节点
     * @return SimpleHtmlDomNode
     */
    function parent() {
        return $this->parent;
    }

    /**
     * 获取指定的子节点,如果$idx=-1,则返回包含所有子节点的数组
     * @param int $idx 子节点的索引号
     * @return SimpleHtmlDomNode
     */
    function children($idx=-1) {
        if ($idx===-1) return $this->children;
        if (isset($this->children[$idx])) return $this->children[$idx];
        return null;
    }

    /**
     * 获取第一个子节点
     * @return SimpleHtmlDomNode
     */
    function firstChild() {
        if (count($this->children)>0) return $this->children[0];
        return null;
    }

    /**
     * 获取最后一个子节点
     * @return SimpleHtmlDomNode
     */
    function lastChild() {
        if (($count=count($this->children))>0) return $this->children[$count-1];
        return null;
    }

    /**
     * 获取后一个兄弟节点
     * @return SimpleHtmlDomNode
     */
    function nextSibling() {
        if ($this->parent===null) return null;
        $idx = 0;
        $count = count($this->parent->children);
        while ($idx<$count && $this!==$this->parent->children[$idx])
            ++$idx;
        if (++$idx>=$count) return null;
        return $this->parent->children[$idx];
    }

    /**
     * 获取前一个兄弟节点
     * @return SimpleHtmlDomNode
     */
    function previousSibling() {
        if ($this->parent===null) return null;
        $idx = 0;
        $count = count($this->parent->children);
        while ($idx<$count && $this!==$this->parent->children[$idx])
            ++$idx;
        if (--$idx<0) return null;
        return $this->parent->children[$idx];
    }

    /**
     * 获取节点的innerHTML内容
     * @return string
     */
    function innertext() {
        if (isset($this->_[HDOM_INFO_INNER])) return $this->_[HDOM_INFO_INNER];
        if (isset($this->_[HDOM_INFO_TEXT])) return $this->dom->restoreNoise($this->_[HDOM_INFO_TEXT]);

        $ret = '';
        foreach($this->nodes as $n)
            $ret .= $n->outertext();
        return $ret;
    }

    /**
     * get dom node's outer text (with tag)
     * 
     * 获取DOM节点的outer text内容(包含标签)
     * @return string
     */
    function outertext() {
        if ($this->tag==='root') return $this->innertext();

        // $if
        if ($this->tag == '$if') {
            return '<?php if (' . $this->condition . ') { ?>'
                    . $this->innertext()
                    . '<?php } ?>';
        }
        // $for
        if ($this->tag == '$for') {
            return '<?php for (' . $this->var . '=' . $this->from . '; '
                                . $this->var . '<=' . $this->to . '; ++'
                                . $this->var . ') { ?>'
                    . $this->innertext()
                    . '<?php } ?>';
        }
        // $foreach
        if ($this->tag == '$foreach') {
            return '<?php foreach (' . $this->var . ' as ' . $this->key . ' => & ' . $this->value . ') { ?>'
                    . $this->innertext()
                    . '<?php } ?>';
        }
        // $embed
        if ($this->tag == '$embed') {
            return '<?php $this->embed(' . var_export($this->action, true) . '); ?>';
        }

        // trigger callback
        if ($this->dom->callback!==null)
            call_user_func_array($this->dom->callback, array($this));

        if (isset($this->_[HDOM_INFO_OUTER])) return $this->_[HDOM_INFO_OUTER];
        if (isset($this->_[HDOM_INFO_TEXT])) return $this->dom->restoreNoise($this->_[HDOM_INFO_TEXT]);

        // render begin tag
        $ret = $this->dom->nodes[$this->_[HDOM_INFO_BEGIN]]->makeup();

        // render inner text
        if (isset($this->_[HDOM_INFO_INNER]))
            $ret .= $this->_[HDOM_INFO_INNER];
        else {
            foreach($this->nodes as $n)
                $ret .= $n->outertext();
        }

        // render end tag
        if(isset($this->_[HDOM_INFO_END]) && $this->_[HDOM_INFO_END]!=0)
            $ret .= '</'.$this->tag.'>';
        return $ret;
    }

    /**
     * get dom node's plain text
     * 
     * 获取DOM节点的纯文本内容
     * @return string
     */
    function text() {
        if (isset($this->_[HDOM_INFO_INNER])) return $this->_[HDOM_INFO_INNER];
        switch ($this->nodetype) {
            case HDOM_TYPE_TEXT: return $this->dom->restoreNoise($this->_[HDOM_INFO_TEXT]);
            case HDOM_TYPE_COMMENT: return '';
            case HDOM_TYPE_UNKNOWN: return '';
        }
        if (strcasecmp($this->tag, 'script')===0) return '';
        if (strcasecmp($this->tag, 'style')===0) return '';

        $ret = '';
        foreach($this->nodes as $n)
            $ret .= $n->text();
        return $ret;
    }

    /**
     * 获取XML文本值(即CDATA内的内容)
     * @return string
     */
    function xmltext() {
        $ret = $this->innertext();
        $ret = str_ireplace('<![CDATA[', '', $ret);
        $ret = str_replace(']]>', '', $ret);
        return $ret;
    }

    /**
     * build node's text with tag
     * 
     * 生产节点的带标签的文本
     * @return string
     */
    function makeup() {
        // text, comment, unknown
        if (isset($this->_[HDOM_INFO_TEXT])) return $this->dom->restoreNoise($this->_[HDOM_INFO_TEXT]);

        $ret = '<'.$this->tag;
        $i = -1;

        foreach($this->attr as $key=>$val) {
            ++$i;

            // skip removed attribute
            if ($val===null || $val===false)
                continue;

            $ret .= $this->_[HDOM_INFO_SPACE][$i][0];
            //no value attr: nowrap, checked selected...
            if ($val===true)
                $ret .= $key;
            else {
                switch($this->_[HDOM_INFO_QUOTE][$i]) {
                    case HDOM_QUOTE_DOUBLE: $quote = '"'; break;
                    case HDOM_QUOTE_SINGLE: $quote = '\''; break;
                    default: $quote = '';
                }
                $ret .= $key.$this->_[HDOM_INFO_SPACE][$i][1].'='.$this->_[HDOM_INFO_SPACE][$i][2].$quote.$val.$quote;
            }
        }
        $ret = $this->dom->restoreNoise($ret);
        return $ret . $this->_[HDOM_INFO_ENDSPACE] . '>';
    }

    /**
     * find elements by css selector
     * 
     * 用CSS选择器查找元素,如果$idx不等于null,则返回所有匹配的就元素节点
     * @param string $selector CSS选择器
     * @param string $idx 如果指定该索引值,则返回第$idx个节点
     * @return SimpleHtmlDomNode
     */
    function find($selector, $idx=null) {
        $selectors = $this->parseSelector($selector);
        if (($count=count($selectors))===0) return array();
        $found_keys = array();

        // find each selector
        for ($c=0; $c<$count; ++$c) {
            if (($levle=count($selectors[0]))===0) return array();
            if (!isset($this->_[HDOM_INFO_BEGIN])) return array();

            $head = array($this->_[HDOM_INFO_BEGIN]=>1);

            // handle descendant selectors, no recursive!
            for ($l=0; $l<$levle; ++$l) {
                $ret = array();
                foreach($head as $k=>$v) {
                    $n = ($k===-1) ? $this->dom->root : $this->dom->nodes[$k];
                    $n->seek($selectors[$c][$l], $ret);
                }
                $head = $ret;
            }

            foreach($head as $k=>$v) {
                if (!isset($found_keys[$k]))
                    $found_keys[$k] = 1;
            }
        }

        // sort keys
        ksort($found_keys);

        $found = array();
        foreach($found_keys as $k=>$v)
            $found[] = $this->dom->nodes[$k];

        // return nth-element or array
        if (is_null($idx)) return $found;
        else if ($idx<0) $idx = count($found) + $idx;
        return (isset($found[$idx])) ? $found[$idx] : null;
    }

    /**
     * seek for given conditions
     * @param array $selector
     * @param array &$ret
     */
    protected function seek($selector, &$ret) {
        list($tag, $key, $val, $exp, $no_key) = $selector;

        // xpath index
        if ($tag && $key && is_numeric($key)) {
            $count = 0;
            foreach ($this->children as $c) {
                if ($tag==='*' || $tag===$c->tag) {
                    if (++$count==$key) {
                        $ret[$c->_[HDOM_INFO_BEGIN]] = 1;
                        return;
                    }
                }
            }
            return;
        }

        $end = (!empty($this->_[HDOM_INFO_END])) ? $this->_[HDOM_INFO_END] : 0;
        if ($end==0) {
            $parent = $this->parent;
            while (!isset($parent->_[HDOM_INFO_END]) && $parent!==null) {
                $end -= 1;
                $parent = $parent->parent;
            }
            $end += $parent->_[HDOM_INFO_END];
        }

        for($i=$this->_[HDOM_INFO_BEGIN]+1; $i<$end; ++$i) {
            $node = $this->dom->nodes[$i];
            $pass = true;

            if ($tag==='*' && !$key) {
                if (in_array($node, $this->children, true))
                    $ret[$i] = 1;
                continue;
            }

            // compare tag
            if ($tag && $tag!=$node->tag && $tag!=='*') {$pass=false;}
            // compare key
            if ($pass && $key) {
                if ($no_key) {
                    if (isset($node->attr[$key])) $pass=false;
                }
                else if (!isset($node->attr[$key])) $pass=false;
            }
            // compare value
            if ($pass && $key && $val  && $val!=='*') {
                $check = $this->match($exp, $val, $node->attr[$key]);
                // handle multiple class
                if (!$check && strcasecmp($key, 'class')===0) {
                    foreach(explode(' ',$node->attr[$key]) as $k) {
                        $check = $this->match($exp, $val, $k);
                        if ($check) break;
                    }
                }
                if (!$check) $pass = false;
            }
            if ($pass) $ret[$i] = 1;
            unset($node);
        }
    }

    /**
     * 根据表达式判断$pattern与$value是否匹配
     * @param string $exp 表达式
     * @param string $pattern 模板
     * @param string $value 值
     * @return boolean
     */
    protected function match($exp, $pattern, $value) {
        switch ($exp) {
            case '=':
                return ($value===$pattern);
            case '!=':
                return ($value!==$pattern);
            case '^=':
                return preg_match("/^".preg_quote($pattern,'/')."/", $value);
            case '$=':
                return preg_match("/".preg_quote($pattern,'/')."$/", $value);
            case '*=':
                if ($pattern[0]=='/')
                    return preg_match($pattern, $value);
                return preg_match("/".$pattern."/i", $value);
        }
        return false;
    }

    /**
     * 解析选择器
     * @return array
     */
    protected function parseSelector($selector_string) {
        // pattern of CSS selectors, modified from mootools
        $pattern = "/([\w-:\*]*)(?:\#([\w-]+)|\.([\w-]+))?(?:\[@?(!?[\w-]+)(?:([!*^$]?=)[\"']?(.*?)[\"']?)?\])?([\/, ]+)/is";
        preg_match_all($pattern, trim($selector_string).' ', $matches, PREG_SET_ORDER);
        $selectors = array();
        $result = array();
        //print_r($matches);

        foreach ($matches as $m) {
            $m[0] = trim($m[0]);
            if ($m[0]==='' || $m[0]==='/' || $m[0]==='//') continue;
            // for borwser grnreated xpath
            if ($m[1]==='tbody') continue;

            list($tag, $key, $val, $exp, $no_key) = array($m[1], null, null, '=', false);
            if(!empty($m[2])) {$key='id'; $val=$m[2];}
            if(!empty($m[3])) {$key='class'; $val=$m[3];}
            if(!empty($m[4])) {$key=$m[4];}
            if(!empty($m[5])) {$exp=$m[5];}
            if(!empty($m[6])) {$val=$m[6];}

            // convert to lowercase
            if ($this->dom->lowercase) {$tag=strtolower($tag); $key=strtolower($key);}
            //elements that do NOT have the specified attribute
            if (isset($key[0]) && $key[0]==='!') {$key=substr($key, 1); $no_key=true;}

            $result[] = array($tag, $key, $val, $exp, $no_key);
            if (trim($m[7])===',') {
                $selectors[] = $result;
                $result = array();
            }
        }
        if (count($result)>0)
            $selectors[] = $result;
        return $selectors;
    }

    /**
     * 获取DOM节点的属性值
     * @param string $name 属性名
     * @return string
     */
    function __get($name) {
        if (isset($this->attr[$name])) return $this->attr[$name];
        switch($name) {
            case 'outertext': return $this->outertext();
            case 'innertext': return $this->innertext();
            case 'plaintext': return $this->text();
            case 'xmltext': return $this->xmltext();
            default: return array_key_exists($name, $this->attr);
        }
    }
    /**
     * 设置DOM节点的属性值
     * @param string $name 属性名
     * @param string $value 属性值
     */
    function __set($name, $value) {
        switch($name) {
            case 'outertext': return $this->_[HDOM_INFO_OUTER] = $value;
            case 'innertext':
                if (isset($this->_[HDOM_INFO_TEXT])) return $this->_[HDOM_INFO_TEXT] = $value;
                if (!isset($this->_[HDOM_INFO_END]) || $this->_[HDOM_INFO_END] == 0) {
                    $this->_[HDOM_INFO_END] = -1;
                    $this->_[HDOM_INFO_ENDSPACE] = substr($this->_[HDOM_INFO_ENDSPACE], 0, -1);
                }
                return $this->_[HDOM_INFO_INNER] = $value;
        }
        if (!isset($this->attr[$name])) {
            $this->_[HDOM_INFO_SPACE][] = array(' ', '', '');
            $this->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_DOUBLE;
        }
        $this->attr[$name] = $value;
    }

    /**
     * 判断DOM节点的属性是否存在
     * @param string $name 属性名
     * @return string
     */
    function __isset($name) {
        switch($name) {
            case 'outertext': return true;
            case 'innertext': return true;
            case 'plaintext': return true;
        }
        //no value attr: nowrap, checked selected...
        return (array_key_exists($name, $this->attr)) ? true : isset($this->attr[$name]);
    }

    /**
     * 销毁DOM节点的属性
     * @param string $name 属性名
     */
    function __unset($name) {
        if (isset($this->attr[$name]))
            unset($this->attr[$name]);
    }

    // camel naming conventions
    function getAllAttributes() {return $this->attr;}
    function getAttribute($name) {return $this->__get($name);}
    function setAttribute($name, $value) {$this->__set($name, $value);}
    function hasAttribute($name) {return $this->__isset($name);}
    function removeAttribute($name) {$this->__set($name, null);}
    function getElementById($id) {return $this->find("#$id", 0);}
    function getElementsById($id, $idx=null) {return $this->find("#$id", $idx);}
    function getElementByTagName($name) {return $this->find($name, 0);}
    function getElementsByTagName($name, $idx=null) {return $this->find($name, $idx);}
    function parentNode() {return $this->parent();}
    function childNodes($idx=-1) {return $this->children($idx);}
//    function firstChild() {return $this->first_child();}
//    function lastChild() {return $this->last_child();}
//    function nextSibling() {return $this->next_sibling();}
//    function previousSibling() {return $this->prev_sibling();}
}