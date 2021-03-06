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
/** @see V */
require PATH_VEE_ROOT . 'core/V.class.php';
V::initialize();

if (!defined('APP_ID')) {
    /** 应用程序ID */
    define('APP_ID', 0);
}

if (!defined('APP_NAME')) {
    /** 应用程序名称 */
    define('APP_NAME', basename(dirname(dirname($_SERVER['SCRIPT_FILENAME']))));
}

/** 应用程序开始请求时间 */
define('APP_REQUEST_TIME', $_SERVER['REQUEST_TIME']);

if (!defined('DEBUG_LEVEL')) {
    /** DEBUG等级 */
    define('DEBUG_LEVEL', 0);
}

if (!defined('DEFAULT_CONTROLLER')) {
    /** 默认的控制器名称 */
    define('DEFAULT_CONTROLLER', 'Index');
}

if (!defined('DEFAULT_ACTION')) {
    /** 默认的动作名称 */
    define('DEFAULT_ACTION', 'default');
}

if (!defined('DEFAULT_LANGUAGE')) {
    /** 默认语言 */
    define('DEFAULT_LANGUAGE', 'zh-cn');
}

if (!defined('PATH_HELPERS')) {
    /** VEE架构辅助器类路径 */
    define('PATH_HELPERS', PATH_VEE_ROOT . 'helpers/');
}

if (!defined('PATH_EXTERNALS')) {
    /** 扩展插件系统根目录 */
    define('PATH_EXTERNALS', PATH_VEE_ROOT . '/externals/');
}

if (!defined('PATH_APP_ROOT')) {
    /** 应用程序根路径 */
    define('PATH_APP_ROOT', dirname(PATH_VEE_ROOT) . '/' . APP_NAME . '/');
}

if (!defined('PATH_APP_MODELS')) {
    /** 模型文件根路径 */
    define('PATH_APP_MODELS', PATH_APP_ROOT . 'mvc/models/');
}

if (!defined('PATH_APP_ENTITIES')) {
    /** 数据库实体类文件根路径 */
    define('PATH_APP_ENTITIES', PATH_APP_MODELS . 'entities/');
}

if (!defined('PATH_APP_VIEWS')) {
    /** 视图文件根路径 */
    define('PATH_APP_VIEWS', PATH_APP_ROOT . 'mvc/views/');
}

if (!defined('PATH_APP_CONTROLLERS')) {
    /** 控制器文件根路径 */
    define('PATH_APP_CONTROLLERS', PATH_APP_ROOT . 'mvc/controllers/');
}

if (!defined('PATH_APP_HELPERS')) {
    /** 应用系统自定义辅助器类路径 */
    define('PATH_APP_HELPERS', PATH_APP_ROOT . 'helpers/');
}

if (!defined('PATH_APP_LANGUAGE')) {
    /** 语言包文件根路径 */
    define('PATH_APP_LANGUAGE', PATH_APP_ROOT . 'language/');
}

if (!defined('PATH_APP_CONFIG')) {
    /** 配置文件根路径 */
    define('PATH_APP_CONFIG', PATH_APP_ROOT . 'config/');
}

if (!defined('PATH_APP_DATA')) {
    /** 数据文件根路径 */
    define('PATH_APP_DATA', PATH_APP_ROOT . 'data/');
}

if (!defined('PATH_APP_CACHE')) {
    /** 缓存文件保存路径 */
    define('PATH_APP_CACHE', PATH_APP_DATA . 'cache/');
}

if (!defined('PATH_APP_UPLOAD')) {
    /** 用户上传文件保存路径 */
    define('PATH_APP_UPLOAD', PATH_APP_DATA . 'upload/');
}

if (!defined('PATH_APP_TMP')) {
    /** 临时文件保存路径 */
    define('PATH_APP_TMP', PATH_APP_DATA . 'tmp/');
}

if (!defined('PATH_APP_LOG')) {
    /** 日志文件保存路径 */
    define('PATH_APP_LOG', PATH_APP_DATA . 'logs/');
}

if (!defined('PATH_WEBROOT')) {
    /** 应用程序WEB文档根目录 */
    define('PATH_WEBROOT', PATH_APP_ROOT . 'htdocs/');
}

if (!defined('PATH_WEBROOT_UPLOAD')) {
    /** 应用程序WEB文档上传文件目录 */
    define('PATH_WEBROOT_UPLOAD', PATH_WEBROOT . 'upload/');
}

if (!defined('APP_DOMAIN')) {
    if (!isset($_SERVER['HTTP_HOST'])) {
        /** 应用程序域名 */
        define('APP_DOMAIN', 'localhost');
    } else if (false === strpos($_SERVER['HTTP_HOST'], ':')) {
        /** 应用程序域名 */
        define('APP_DOMAIN', $_SERVER['HTTP_HOST']);
    } else {
        list($hostname) = explode(':', $_SERVER['HTTP_HOST'], 2);
        /** 应用程序域名 */
        define('APP_DOMAIN', $hostname);
    }
}

if (!defined('APP_URL_BASE')) {
    // 你的服务器必须被配置以便产生正确的链接地址。
    // 例如在 Apache 中，你需要在 httpd.conf 中设置 HostnameLookups On
    /** 应用程序URL根地址 */
    define('APP_URL_BASE', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] 
                              && $_SERVER['HTTPS'] != 'off'
                              ? 'https://' : 'http://')
                         . (isset($_SERVER['HTTP_HOST']) 
                              ? $_SERVER['HTTP_HOST'] : 'localhost') 
                         . '/');
}

/** 应用程序URL */
//if (!defined('APP_URL')) {
//    define('APP_URL', APP_URL_BASE . substr($_SERVER['REQUEST_URI'], 1));
//}

if (!defined('APP_URL_JS')) {
    /** URL for JavaScript files */
    define('APP_URL_JS', APP_URL_BASE . 'js/');
}

if (!defined('APP_URL_CSS')) {
    /** URL for CSS files */
    define('APP_URL_CSS', APP_URL_BASE . 'css/');
}

if (!defined('APP_URL_IMAGES')) {
    /** URL for image files */
    define('APP_URL_IMAGES', APP_URL_BASE . 'images/');
}

if (!defined('APP_URL_UPLOAD')) {
    /** URL for upload files */
    define('APP_URL_UPLOAD', APP_URL_BASE . 'upload/');
}

/** @see Config */
require PATH_APP_CONFIG . 'application.cfg.php';

/* 判断应用是否被禁用 */
if (Config::DISABLED) {
    exit('Sorry, the application was disabled.');
}

/* 初始化时区及本地设置 */
date_default_timezone_set(Config::TIMEZONE);
setlocale(LC_ALL, Config::LOCALE);

/*
 * 初始化php.ini相关设置
 */
set_include_path('.' . PATH_SEPARATOR . PATH_APP_MODELS
                     . PATH_SEPARATOR . PATH_APP_ENTITIES
                     . PATH_SEPARATOR . PATH_VEE_ROOT . 'database'
                     . PATH_SEPARATOR . PATH_APP_HELPERS
                     . PATH_SEPARATOR . PATH_HELPERS
                     . PATH_SEPARATOR . get_include_path()
                     );

/* 自动识别语言包 */
if ('AUTO' == Config::$response['language']) {
    $lang = V::get(Config::VAR_LANG); // 取自Cookie
    if (empty($lang) && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $pos = strpos($lang, ',');
        if (false !== $pos) {
            $lang = substr($lang, 0, $pos);
        }
        $pos = strpos($lang, ';');
        if (false !== $pos) {
            $lang = substr($lang, 0, $pos);
        }
        $lang = strtolower($lang);
    }
    if (empty($lang) || !is_dir(PATH_APP_LANGUAGE . $lang)) {
        Config::$response['language'] = DEFAULT_LANGUAGE;
    } else {
        Config::$response['language'] = $lang;
    }
}

/* 初始化调试器 */
if (Config::DEBUG && ini_get('display_errors')) {
    if (Config::FIREPHP_ENABLED && isset($_SERVER['HTTP_USER_AGENT'])
            && false !== strpos($_SERVER['HTTP_USER_AGENT'], 'FirePHP') ) {
        $debugger = 'FirephpDebugger';
    } else if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
                  && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
        $debugger = 'TextDebugger';
    } else {
        $debugger = 'HtmlDebugger';
    }
} else if (-1 !== Config::SYS_LOG_ID) {
    $debugger = 'LogDebugger';
} else if (Config::ERROR_LOG_ENABLED) { //使用error_log()实现的高效日志
    ini_set('log_errors', 1);
}

if (isset($debugger)) {
    require PATH_VEE_ROOT . 'debug/' . $debugger . '.class.php';
    set_error_handler($debugger . '::errorHandler', E_ALL);
    set_exception_handler($debugger . '::exceptionHandler');
    if (Config::DEBUG & C::DEBUG_MESSAGE) {
        V::debug('完成调试器初始化(' . $debugger
                 . ')与语言包识别(' . Config::$response['language'] . ')');
    }
} else {
    if (Config::DEBUG & C::DEBUG_MESSAGE) {
        V::debug('完成语言包识别(' . Config::$response['language'] . ')');
    }
}

/* 启动session */
if (defined('SESSION_ENABLED') && SESSION_ENABLED) {
    if (Config::DEBUG & C::DEBUG_MESSAGE) {
        V::debug('启动Session');
    }
    session_start();
}

require PATH_HELPERS . 'StringHelper.class.php';
/* 消除自动魔术引号替换 */
if (get_magic_quotes_gpc()) {
    if (Config::DEBUG & C::DEBUG_MESSAGE) {
        V::debug('消除自动魔术引号替换（建议设置php.ini中的magic_quotes_gpc=Off）');
    }
    StringHelper::stripSlashesForArray($_GET);
    StringHelper::stripSlashesForArray($_POST);
    StringHelper::stripSlashesForArray($_REQUEST);
    StringHelper::stripSlashesForArray($_COOKIE);
}

if (Config::DEBUG & C::DEBUG_MESSAGE) {
    V::debug('VEE架构系统及配置环境载入完成');
}
