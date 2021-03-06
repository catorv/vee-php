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
ob_start();

/** VEE架构根路径 */
define('PATH_VEE_ROOT', dirname(__FILE__) . '/');

/**
 * VEE参量定义类，"C"是"Constant"的缩写
 * @package vee-php\core
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class C {
    /** DEBUG等级：全部 */
    const DEBUG_ALL = 65535;
    /** DEBUG等级：错误 */
    const DEBUG_ERROR = 1;
    /** DEBUG等级：异常 */
    const DEBUG_EXCEPTION = 2;
    /** DEBUG等级：调试信息 */
    const DEBUG_MESSAGE = 4;
    /** DEBUG等级：跟踪调试信息 */
    const DEBUG_TRACE = 8;
    /** DEBUG等级：ORM调试信息 */
    const DEBUG_ORM = 16;

    /** DEBUG类型名称：ORM调试信息 */
    const DEBUGTYPE_ORM = '$$ORM$$';
    /** DEBUG类型名称：跟踪调试信息 */
    const DEBUGTYPE_TRACE = '$$TRACE$$';
    /** DEBUG类型名称：调试信息 */
    const DEBUGTYPE_DEBUG = '$$DEBUG$$';

    /** 计时器返回类型：全部 */
    const TIMER_ALL = 65535;
    /** 计时器返回类型：当前点计时 */
    const TIMER_CURRENT = 1;
    /** 计时器返回类型：累计总计时 */
    const TIMER_TOTAL = 2;

    /** 变量存储类型：session */
    const VARST_SESSIOIN = 'session';
    /** 变量存储类型：cookie */
    const VARST_COOKIE = 'cookie';
    /** 变量存储类型：$_REQUEST变量 */
    const VARST_REQUSET = 'request';


    /**
     * PHP错误类型，用于对应到语言包的键值
     * @var array
     */
    static public $errorType = array (
            0                       => '未知错误',    //Unknown Error
            E_ERROR                 => '错误',        //Error
            E_WARNING               => '警告',        //Warning
            E_PARSE                 => '解析错误',    //Parsing Error
            E_NOTICE                => '提示',        //Notice
            E_CORE_ERROR            => '内核错误',    //Core Error
            E_CORE_WARNING          => '内核警告',    //Core Warning
            E_COMPILE_ERROR         => '编译错误',    //Compile Error
            E_COMPILE_WARNING       => '编译警告',    //Compile Warning
            E_USER_ERROR            => '用户错误',    //User Error
            E_USER_WARNING          => '用户警告',    //User Warning
            E_USER_NOTICE           => '用户提示',    //User Notice
            E_STRICT                => '运行时提示',  //Runtime Notice
            // E_RECOVERABLE_ERRROR    => '可捕获的致命错误',   //Catchable Fatal Error (since PHP 5.2.0)
            // E_DEPRECATED            => '运行时提示',        //Run-time notices (since PHP 5.3.0)
            // E_USER_DEPRECATED       => '用户生成的警告信息', //User-generated warning message (since PHP 5.3.0)
            );
}