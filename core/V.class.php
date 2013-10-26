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
 * VEE应用程序核心类
 * @package vee-php\core
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class V {
    /** 程序启动标记时间 */
    static public $timeFirst = 0;
    /** 程序执行的上一个标记时间 */
    static public $timeLast = 0;

    /**
     * URI协议解析后的数组
     * @var array
     */
    static public $uri = array();

    /**
     * 保存当前控制器对象
     * @var Controller
     */
    static public $controller = null;

    /**
     * 保存当前Response对象
     * @var AResponse
     */
    static public $response = null;
    
    /**
     * 运行应用程序，如果未指定URI协议字符串则自动从URL中获取。
     * @param string $action URI协议字符串
     * @return array 返回包含两个元素的数组，第一个元素是控制器类对象，第二个是$uri值
     */
    static public function run($action = null) {
        $controller = self::$controller;
        $uri = self::$uri;

        if (Config::DEBUG & C::DEBUG_MESSAGE) {
            V::debug('开始运行控制器');
        }

        if (is_array($action)) {
            self::$uri = $action;
        } else {
            if (null === $action) {
               $uriProtocol = self::getUriProtocol(Config::URI_PROTOCOL);
               $protocol = & $uriProtocol['protocol'];
               $action = & $uriProtocol['text'];
            } else {
                $protocol = 'NONE';
            }

            /* 路由规则判断 */
            foreach (Config::$route as $from => $to) {
                if ($from && preg_match($from, $action)) {
                    $newAction = preg_replace($from, $to, $action);
                    if (Config::DEBUG & C::DEBUG_MESSAGE) {
                        V::debug('URI路由转向：' . $action . '→' . $newAction);
                    }
                    $action = $newAction;
                    break;
                }
            }

            self::$uri = self::parseUriProtocol($action);
            self::$uri['protocol'] = $protocol;
        }

        $file = PATH_APP_CONTROLLERS . self::$uri['path'] . '.do.php';
        if (!is_file($file)) {
            if (Config::DEBUG & C::DEBUG_MESSAGE) {
                V::debug('未找到控制器：' . $file . '。');
            }
            self::$uri['controller'] = 'Controller';
        }

        $controllerClass = self::$uri['controller'];
        if (!class_exists($controllerClass, false)) {
            require $file;
        }

        $pathfile = dirname($file) . '/path.inc.php';
        if (is_file($pathfile)) {
            require_once $pathfile;
        }

        self::$controller = new $controllerClass();
        self::$controller->action(self::$uri['action'], 
                                  self::$uri['arguments']);

        if (Config::DEBUG & C::DEBUG_MESSAGE) {
            V::debug('控制器运行结束');
        }

        $result = array(self::$controller, self::$uri);
        if ($controller) {
            self::$controller = $controller;
        }
        if ($uri) {
            self::$uri = $uri;
        }

        if (null === self::$response) {
            V::response();
        }

        return $result;
    }

    /**
     * 返回Response对象,第一次调用时会自动根据配置文件自动创建实例
     * @param string $engine Response引擎,默认按 application.cfg.php 中的
     *                        Config::$response['engine'] 设置
     * @param boolean $break 是否中断执行后面的程序
     * @return AResponse
     */
    static public function response($engine = null, $break = false) {
        if (is_array($engine)) {
            self::response()->value($engine);
            return self::$response;
        }

        if (true === $engine) {
            $break = $engine;
            $engine = null;
        }

        if (null === self::$response) {
            if (null === $engine) {
                $engine = Config::$response['engine'];
            }
            self::$response = AResponse::getInstance($engine);
        }

        if ($break) {
            exit;
        }

        return self::$response;
    }

    /**
     * 返回Cache对象,第一次调用时会自动根据配置文件自动创建实例
     * @param int $name 配置项名称,对应Config::$cache的键名,默认为0
     * @return ICache
     */
    static public function cache($name = 0) {
        static $caches = array();

        if (!isset($caches[$name])) {
            if (isset(Config::$cache[$name])) {
                $params = & Config::$cache[$name];
                $className = ucfirst($params['engine']) . 'Cache';
                if (!class_exists($className, false)) {
                    $classFile = PATH_VEE_ROOT . 'cache/' 
                               . $className . '.class.php';
                    if (is_file($classFile)) {
                        include $classFile;
                    } else {
                        throw new Exception('未知的CACHE引擎:' 
                                            . $params['engine'], 3002);
                    }
                }
                $caches[$name] = new $className($params);
            } else {
                throw new Exception("CACHE配置项({$name})不存在", 3001);
            }
        }

        return $caches[$name];
    }

    /**
     * 返回Log对象,第一次调用时会自动根据配置文件自动创建实例
     * @param int $name 配置项名称,对应Config::$log的键名,默认为0
     * @return ILog
     */
    static public function log($name = 0) {
        static $logs = array();

        if (!isset($logs[$name])) {
            if (isset(Config::$log[$name])) {
                $params = & Config::$log[$name];
                $className = ucfirst($params['engine']) . 'Log';
                if (!class_exists($className, false)) {
                    $classFile = PATH_VEE_ROOT . 'log/' . $className
                                                         . '.class.php';
                    if (is_file($classFile)) {
                        include $classFile;
                    } else {
                        throw new Exception('未知的LOG引擎:' 
                                            . $params['engine'], 7002);
                    }
                }
                $logs[$name] = new $className($params);
            } else {
                throw new Exception("LOG配置项({$name})不存在", 7001);
            }
        }

        return $logs[$name];
    }

    /**
     * 返回数据库对象,第一次调用时会自动根据配置文件自动创建实例
     * @param int $name 配置项名称,对应Config::$db的键名,默认为0
     * @return Db
     */
    static public function db($name = 0) {
        static $dbs = array();
        
        if (!isset($dbs[$name])) {
            if (isset(Config::$db[$name])) {
                if (!class_exists('Db', false)) {
                    require PATH_VEE_ROOT . 'database/Db.class.php';
                }
                $dbs[$name] = new Db(Config::$db[$name]['params'],
                                     Config::$db[$name]['options'] );
            } else {
                throw new Exception("数据库配置项($name)不存在", 4001);
            }
        }

        return $dbs[$name];
    }

    /**
     * 返回Language对象,第一次调用时会自动根据配置文件自动创建实例
     * @param int $lang 语种, 默认使用Config::$response['language']
     * @return Language
     */
    static public function language($lang = null) {
        static $languages = array();

        if (empty($lang)) {
            $lang = Config::$response['language'];
        }
        if (!isset($languages[$lang])) {
            $languages[$lang] = new Language($lang);
        }

        return $languages[$lang];
    }

    /**
     * 使用默认语种(Config::$response['language'])说话, 
     * 参数参考Language->say()
     * @return string
     */
    static public function say() {
        return self::language()->say(func_get_args());
    }

    /**
     * 返回客户端IP
     * HTTP_CLIENT_IP 和 HTTP_X_FORWARDED_FOR 有被伪造的风险，
     * 如无特殊需求，建议直接使用$_SERVER['REMOTE_ADDR']获取客户端IP地址
     * @return string
     */
    static public function ip() {
        if (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP']
                 && 'unknown' != $_SERVER['HTTP_CLIENT_IP']) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
                && $_SERVER['HTTP_X_FORWARDED_FOR']
                && 'unknown' != $_SERVER['HTTP_X_FORWARDED_FOR']) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * 页面重定向
     * 
     * 重定向模式:
     * 
     *     0  通过PHP的header()函数实现, 当调用该函数前已经有内容输出, 则自动选用模式2 
     *     1  通过JavaScript的Location实现 
     *     2  通过JavaScript的Location.replace实现
     * 
     * @param string $url 重定向目标URL
     * @param int $mode 重定向模式, 默认值为0
     */
    static public function redirect($url, $mode = 0) {
        // 如果页面已经有内容输出, 自动改用JavaScript方式跳转
        if ($mode == 0 && headers_sent()) {
            $mode = 2;
        }
        switch ($mode) {
            case 1: exit('<script>location="' . $url . '";</script>');
            case 2: exit('<script>location.replace("' . $url . '");</script>');
            default: header('Location: ' . $url); exit;
        }
    }

    /**
     * 获取SESSION/COOKIE/REQUEST的变量值,
     * 变量值获取优先顺序 SESSION - COOKIE - REQUEST - $defaultValue
     * @param string $name 预设变量名,该参数可以指定一个数组以便批量返回变量（数组）
     * @param string $defaultValue 变量默认值
     * @param array $var 来源变量
     * @return mixed
     */
    static public function get($name, $defaultValue = null, $var = null) {
        if (is_array($name)) {
            $result = array();
            foreach ($name as $key => $item) {
                if (is_int($key)) {
                    $result[$item] = self::get($item, $defaultValue, $var);
                } else {
                    $result[$key] = self::get($key, $item, $var);
                }
            }
            return $result;
        } else {
            if ($var !== null) {
                if (is_array($var) && isset($var[$name])) {
                    return $var[$name];
                }
            } else {
                if (isset($_SESSION[$name])) {
                    return $_SESSION[$name];
                } else if (isset($_COOKIE[Config::COOKIES_PREFIX . $name])) {
                    return $_COOKIE[Config::COOKIES_PREFIX . $name];
                } else if (isset($_REQUEST[$name])) {
                    return $_REQUEST[$name];
                }
            }
            return $defaultValue;
        }
    }

    /**
     * 设置SESSION/COOKIE/REQUEST的变量值
     * (对$_REQUEST/$_POST/$_GET赋值等建议使用直接赋值)
     * 
     * 预设变量保存类型的值可为 
     *   C::VARST_SESSIOIN | C::VARST_COOKIE | C::VARST_REQUSET
     *   
     * @param string $name 预设变量名
     * @param mixed $value 预设变量值
     * @param string $type 预设变量保存类型, 默认为session
     */
    static public function set($name, $value, $type = C::VARST_SESSIOIN) {
        if ($type == C::VARST_SESSIOIN && session_id()) {
            $_SESSION[$name] = $value;
        } else if ($type != C::VARST_REQUSET
                  && setcookie(Config::COOKIES_PREFIX . $name,
                               $value,
                               (0 !== Config::COOKIES_EXPIRE)
                                    ? $_SERVER['REQUEST_TIME'] 
                                        + Config::COOKIES_EXPIRE
                                    : 0,
                               Config::COOKIES_PATH,
                               Config::COOKIES_DOMAIN)) {
            $_COOKIE[$name] = $value;
        } else {
            $_REQUEST[$name] = $value;
        }
    }

    /**
     * 类自动加载函数
     * @param string $name 类名
     * @param string $ext 类文件扩展名
     */
    static public function autoload($name, $ext = '.class.php') {
        if (Config::DEBUG & C::DEBUG_MESSAGE) {
            V::debug('自动载入类：' . $name . ' （使用require/include/V::loadHelper/V::loadModel等方法主动载入类的效率将会更高）');
        }
        if (isset(Config::$autoload[$name])) {
            require Config::$autoload[$name];
        } else {
            require $name . $ext;
        }
    }

    /**
     * 加载VEE-PHP类库中的类
     * @param string $class 类名（包含类路径）
     */
    static public function loadClass($class) {
        require_once PATH_VEE_ROOT . $class . '.class.php';
    }

    /**
     * 加载辅助器
     * @param string $helper 辅助器名称
     */
    static public function loadHelper($helper) {
        if (!class_exists($helper, false)) {
            $helper .= '.class.php';
            if (is_file($file = PATH_HELPERS . $helper)) {
                require $file;
            } else {
                require PATH_APP_HELPERS . $helper;
            }
        }
    }

    /**
     * 加载模型(model)文件
     * @param string $model 模型名称
     */
    static public function loadModel($model) {
        $pos = strrpos($model, '/');
        if (false === $pos) {
            $modelname = $model;
        } else {
            $modelname = substr($model, $pos + 1);
        }
        if (!class_exists($modelname, false)) {
            require PATH_APP_MODELS . $model . '.class.php';
        }
    }
    
    /**
     * 载入制定的配置文件，
     * 所有配置文件都是以 $filename.cfg.php 的形式保存于PATH_APP_CONFIG
     * @param string $name 配置项名称
     * @param string $filename 配置文件名称, 如果未指定， 默认取 $name 的值
     * @param string $ext 配置文件扩展名
     * @return mixed
     */
    static public function loadConfig($name, $filename = null, 
                                      $ext = '.cfg.php') {
        if (empty($filename)) {
            $filename = $name;
        }
        $config = include(PATH_APP_CONFIG . $filename . $ext);
        if (property_exists(Config, $name)) {
            Config::${$name} = $config;
        }
        return $config;
    }

    /**
     * 初始化
     */
    static public function initialize() {
        self::$timeFirst = microtime(true);
        self::$timeLast = self::$timeFirst;
        /* 类自动载入函数注册 */
        spl_autoload_extensions('.class.php');
        // spl_autoload_register();
        spl_autoload_register(array(__CLASS__, 'autoload'));
        if (function_exists('__autoload')) {
            spl_autoload_register('__autoload');
        }
    }

    /**
     * 跟踪点计时器
     * @param int $type 返回参数类型,默认为C::TIMER_CURRENT
     * @return mixed 返回时间单位均为毫秒(ms)
     */
    static public function timer($type = C::TIMER_CURRENT) {
        $now = microtime(true);
        switch ($type) {
            case C::TIMER_CURRENT :
                $result = ($now - self::$timeLast) * 1000;
                break;
            case C::TIMER_TOTAL :
                $result = ($now - self::$timeFirst) * 1000;
                break;
            default :
                $result = array( ($now - self::$timeLast) * 1000,
                                 ($now - self::$timeFirst) * 1000 );
                break;
        }
        self::$timeLast = $now;
        return $result;
    }

    /**
     * 输出跟踪堆栈
     * @param mixed $vars 变量列表，参数个数不限
     */
    static public function trace() {
        extract(func_get_args(), EXTR_PREFIX_ALL, '');
        trigger_error(C::DEBUGTYPE_TRACE);
    }

    /**
     * 调试信息
     * @param string $message 调试信息提示内容
     * @param string $type 调试信息类型
     */
    static public function debug($message = 'debug', $type = 'main') {
        trigger_error(C::DEBUGTYPE_DEBUG);
    }

    /**
     * 获取URI协议及协议字符串
     * @param string $protocol URI协议
     * @return string
     */
    static public function getUriProtocol($protocol) {
        if ('PATH_INFO' == $protocol || 'AUTO' == $protocol
                    && isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO']) {
            $protocol = 'PATH_INFO';
            $string = $_SERVER['PATH_INFO'];
        } else if ('REQUEST' == $protocol || 'AUTO' == $protocol
                    && isset($_REQUEST[Config::URI_ACTION_KEY])
                    && $_REQUEST[Config::URI_ACTION_KEY]) {
            $protocol = 'REQUEST';
            $string = $_REQUEST[Config::URI_ACTION_KEY];
        } else if ('QUERY_STRING' == $protocol || 'AUTO' == $protocol
                    && $_SERVER['QUERY_STRING']) {
            $protocol = 'QUERY_STRING';
            $string = $_SERVER['QUERY_STRING'];
        } else if ('REQUEST_URI' == $protocol || 'AUTO' == $protocol
                    && $_SERVER['REQUEST_URI']) {
            $protocol = 'REQUEST_URI';
            $string = $_SERVER['REQUEST_URI'];
            $pos = strpos($string, '?');
            if (false !== $pos) {
                $string = substr($string, 0, $pos);
            }
        } else if ('AUTO' == $protocol) {
            $protocol = 'REQUEST';
            $string = '';
        } else {
            return self::getUriProtocol('AUTO');
        }
        if ($string) {
            $pos = strpos($string, Config::URI_END_STRING);
            if ($pos !== false) {
                $string = substr($string, 0, $pos);
            }
            $string = trim($string, '/');
        }
        return array(
            'protocol'  => $protocol,
            'text'      => $string,
        );
    }

    /**
     * 解析URI协议
     * @param string $protocolString URI协议字符串
     * @return array
     */
    static public function parseUriProtocol($protocolString) {
        $responseIsset = false;
        if (false !== strpos($protocolString, '.')) {
            list($protocolString, Config::$response['engine']) = explode('.', $protocolString, 2);
            $responseIsset = true;
        }

        $arguments = explode('/', $protocolString);
        foreach ($arguments as & $argument) {
            if ('~' === $argument) {
                $argument = '';
            } else {
                $argument = rawurldecode($argument);
            }
        }

        $controller = array_shift($arguments);
        if ($controller) {
            if ('_' == $controller[0]) {
                $action = strtolower($_SERVER['REQUEST_METHOD']);
                $controller = substr($controller, 1);
            }
            $pos = strrpos($controller, '_');
            if (false !== $pos) {
                $controller[$pos+1] = strtoupper($controller[$pos+1]);
            } else {
                $controller = '_' . ucfirst($controller);
            }
        } else {
            $controller = '_' . DEFAULT_CONTROLLER;
        }
        if (!$responseIsset) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                    && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
                Config::$response['engine'] = 'Json';
            } else if (isset($_REQUEST[Config::URI_RESPONSE_KEY])) {
                Config::$response['engine'] = $_REQUEST[Config::URI_RESPONSE_KEY];
            }
        }

        if (!isset($action)) {
            $action = array_shift($arguments);
            if ($action) {
                $action = $action;
            } else {
                $action = DEFAULT_ACTION;
            }
        }

        if ($_SERVER['PHP_SELF']) {
            $portal = $_SERVER['PHP_SELF'];
        } else {
            $portal = $_SERVER['SCRIPT_NAME'];
        }
        $pos = strpos($portal, '.php');
        if (false === $pos) {
            $portal = 'index.php';
        } else {
            $portal = substr($portal, 1, $pos + 3);
        }
        return array(
            'portal'            => $portal,
            'text'              => $protocolString,
            'controller'        => $controller,
            'action'            => $action,
            'arguments'         => $arguments,
            'responseEngine'    => Config::$response['engine'],
            'path'              => strtr(ltrim($controller, '_'), '_', '/'),
        );
    }

    /**
     * 生成一个当前页面的URL
     * (所传入的参数只用于生成URL，并不真正更新 $uri['arguments'] 与 $_REQUEST 的值)
     * 
     * 本函数接受两种参数调用方式：
     * 
     *     1. fn($action, $controller, $module, $addList, $removeList)
     *     2. fn($addList, $removeList)
     *
     * @param string $action 修改动作名称
     * @param string $controller 修改控制器名称
     * @param array $addList 增加/修改参数项(包括 $uri['arguments'] 与 $_REQUEST)
     * @param array $removeList 删除参数项(包括 $uri['arguments'] 与 $_REQUEST)
     * @return string
     */
    static public function makeUrl($action = null, $controller = null,
                                $addList = null, $removeList = null) {
        $uri = V::$uri;
        $request = array_merge($_GET, $_POST);
        if (is_array($action)) {
            $addList = & $action;
            $removeList = & $controller;
        } else {
            if (null !== $action) {
                $uri['action'] = $action;
            }
            if (null !== $controller) {
                $uri['controller'] = $controller;
            } else {
                $uri['controller'] = ltrim($uri['controller'], '_');
            }
        }
        if (is_array($addList)) {
            foreach ($addList as $key => $value) {
                if (is_int($key)) {
                    $uri['arguments'][$key] = $value;
                } else {
                    $request[$key] = $value;
                }
            }
        }
        if (is_array($removeList)) {
            foreach ($removeList as $key) {
                if (is_int($key)) {
                    if (isset($uri['arguments'][$key])) {
                        $uri['arguments'][$key] = '';
                    }
                } else {
                    if (isset($request[$key])) {
                        unset($request[$key]);
                    }
                }
            }
        }

        $string = $uri['controller'] . '/' . $uri['action'];
        foreach ($uri['arguments'] as $argument) {
            if ('' === $argument || null === $argument || false === $argument) {
                $string .= '/~';
            } else {
                $argument = rawurlencode($argument);
                $string .= '/' . $argument;
            }
        }
        if ('REQUEST' == $uri['protocol']) {
            $url = APP_URL_BASE . $uri['portal'];
            $request[Config::URI_ACTION_KEY] = $string;
        } else if ('QUERY_STRING' == $uri['protocol']) {
            $url = APP_URL_BASE . $uri['portal'] . '?' . $string;
            return $url;
        } else if ('PATH_INFO' == $uri['protocol']) {
            $url = APP_URL_BASE . $uri['portal']
                                . '/' . $string . Config::URI_END_STRING;
        } else { // REQUEST_URI
            $url = APP_URL_BASE . $string . Config::URI_END_STRING;
        }

        if ($request) {
            $queryString = http_build_query($request);
            $url .= '?' . $queryString;
        }

        return $url;
    }

    /**
     * 生成一个符合协议的URI
     * (所传入的参数只用于生成URI，并不真正更新 $uri['arguments'] 与 $_REQUEST 的值)
     * 
     * 本函数接受两种参数调用方式：
     * 
     *     1. fn($action, $controller, $module, $modifyList)
     *     2. fn($modifyList)
     *
     * @param string $action 修改动作名称
     * @param string $controller 修改控制器名称
     * @param array $modifyList 修改参数项($uri['arguments'])
     * @return string
     */
    static public function makeUri($action = null, $controller = null,
                            $modifyList = null) {
        $uri = V::$uri;

        if (is_array($action)) {
            $modifyList = & $action;
        } else {
            if (null !== $action) {
                $uri['action'] = $action;
            }
            if ($controller) {
                $uri['controller'] = $controller;
            } else {
                $uri['controller'] = ltrim($uri['controller'], '_');
            }
        }
        if (is_array($modifyList)) {
            foreach ($modifyList as $key => $value) {
                if (is_int($key)) {
                    $uri['arguments'][$key] = $value;
                }
            }
        }

        $string = $uri['controller'] . '/' . $uri['action'];
        foreach ($uri['arguments'] as $argument) {
            if ('' === $argument || null === $argument || false === $argument) {
                $string .= '/~';
            } else {
                $argument = rawurlencode($argument);
                $string .= '/' . $argument;
            }
        }

        return $string;
    }
}

/**
 * 控制器类（Controller）
 * @package vee-php\core
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class Controller {
    /**
     * 调用本控制器对象范围内的Action
     * @param string $name Action名称
     * @param array $args 参数列表
     */
    public function action($name, $args = null) {
        static $inDefault = false;
        V::$uri['action'] = $name;
        $action = 'do' . ucfirst(V::$uri['action']);
        if (method_exists($this, $action)) {
            if (DEFAULT_ACTION == $name) {
                $inDefault = true;
            }
            if (Config::DEBUG & C::DEBUG_MESSAGE) {
                V::debug('调用控制器方法：' . V::$uri['controller'] . '->'
                                           . $action);
            }
        } else {
            $name = DEFAULT_ACTION;
            $action = 'do' . ucfirst(DEFAULT_ACTION);
            if (!$inDefault && method_exists($this, $action)) {
                $inDefault = true;
                array_unshift(V::$uri['arguments'], V::$uri['action']);
                $args = V::$uri['arguments'];
                if (Config::DEBUG & C::DEBUG_MESSAGE) {
                    V::debug('您试图调用的控制器方法(do'
                            . ucfirst(V::$uri['action'])
                            . ')不存在，调用默认方法：'
                            . V::$uri['controller'] . '->' . $action);
                }
            } else {
                if (Config::DEBUG & C::DEBUG_MESSAGE) {
                    V::debug('您递归调用了控制器方法' . $action
                            . '，为避免可能造成的无限循环，VEE-PHP忽略了本次调用行为。');
                }
                return;
            }
        }
        if (false !== $this->onBeforeAction($name, $args)) {
            call_user_func_array(array($this, $action), $args);
            if (DEFAULT_ACTION == $name) {
                $inDefault = false;
            }
            if (false === $this->onAfterAction($name, $args)) {
                exit;
            }
        } else {
            if (DEFAULT_ACTION == $name) {
                $inDefault = false;
            }
            exit;
        }
    }

    /**
     * Action被执行前触发的事件
     * @param string $name Action名称
     * @param array $args 参数列表
     * @return boolean 如果返回false，则中止程序执行。
     */
    protected function onBeforeAction($name, $args = null) {
        return true;
    }

    /**
     * Action被执行后触发的事件
     * @param string $name Action名称
     * @param array $args 参数列表
     * @return boolean 如果返回false，则中止程序执行。
     */
    protected function onAfterAction($name, $args = null) {
        return true;
    }
}

/**
 * 语言包调用类类
 * @package vee-php\core
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class Language {
    /**
     * 短句列表
     * 
     *     key: 语种短句定义标识
     *     value: 翻译后的短语,如果有嵌入参数,必须符合sprintf函数的格式
     *     
     * @var array
     */
    private $vars;

    /**
     * 当前语言包路径
     * @var string
     */
    private $path;

    /**
     * 构造函数
     * @param string $name 语种名词
     */
    public function __construct($name) {
        $this->path = PATH_APP_LANGUAGE . $name . '/';
    }

    /**
     * 使用该语句说话
     * 
     * 支持的参数格式:
     * 
     *     1. say(短句标识, [参数1, [参数2 ...]]) 
     *     2. say(array(短句标识, [参数1, [参数2 ...]])) 
     *     短句标识可包含两部分：语言包文件标识、语句标识，中间以“:”分隔。 
     *     其中语言包文件标识部分可省略，默认使用“main”语言包文件。
     * 
     * 
     * @param string $what 说话的内容
     * @return string
     */
    public function say($what) {
        $params = (is_array($what)) ? $what : func_get_args();
        $pos = strpos($params[0], ':');
        if (false === $pos) {
            $filename = 'main';
        } else {
            $filename = substr($params[0], $pos + 1);
            $params[0] = substr($params[0], 0, $pos);
        }
        if (!isset($this->vars[$filename])) {
            $this->load($filename);
        }
        if (isset($this->vars[$filename][$params[0]])) {
            $params[0] = $this->vars[$filename][$params[0]];
            return call_user_func_array('sprintf', $params);
        } else {
            if (Config::$response['language'] == 'zh-cn') {
                throw new Exception('在当前语言包中找不到"' . $params[0]
                                    . '"的翻译.', 1006);
            } else {
                throw new Exception('The translation for "' . $params[0]
                    . '" is not found in current language package file.', 1006);
            }
        }
    }

    /**
     * 载入语言包文件
     * @param $filename
     * @return unknown_type
     */
    private function load($filename) {
        $langFile = $this->path . $filename . '.lang.php';
        if (is_file($langFile)) {
            $this->vars[$filename] = include($langFile);
        } else {
            if (Config::$response['language'] == 'zh-cn') {
                throw new Exception('找不到语言包文件. 文件名:' . $langFile, 1005);
            } else {
                throw new Exception('Language package file is not found. filename:'
                                    . $langFile, 1005);
            }
        }
    }
}


/**
 * Response抽象类，实现各种响应数据的格式化
 *
 * @package vee-php\response
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
abstract class AResponse {
    /** 选项：自定义的模板文件路径, 如果未指定则自动定位模板文件 */
    const OPTION_TEMPLATE = 1;
    /** 选项：头信息，如CVS或Excel的表头信息，对应的选项值必须是一个数组 */
    const OPTION_HEADER = 2;
    /** 选项：XML信息自动包装，如<root><data>...</data></root> */
    const OPTION_XML_AUTOWRAP = 3;

    /**
     * 值列表
     * @var array $value
     */
    protected $values = array();

    /**
     * Response选项
     * @var array
     */
    protected $options = array();

    /**
     * 构造函数
     */
    protected function __construct() {
        header('Content-type: text/html; charset=' . Config::$response['charset']);
    }

    /**
     * 析构函数
     */
    public function __destruct() {
        static $autoOutput = null;
        if (null === $autoOutput) {
            $autoOutput = Config::$response['autoOutput'];
        }
        if ($autoOutput) {
            $autoOutput = false;
            try {
                $this->output();
            } catch (Exception $e) {
                if (isset($GLOBALS['debugger'])) {
                    call_user_func(array($GLOBALS['debugger'], 'exceptionHandler'), $e);
                } else {
                    trigger_error('Exception: '. $e->getMessage() . ' (' . $e->getCode() . ')');
                }
            }
        }
//        if (Config::DEBUG & C::DEBUG_MESSAGE) {
//            V::debug('Response引擎类(' . get_class($this) . ')完成输出');
//        }
    }

    /**
     * 创建一个Response类
     * @param string $engine Response引擎名称
     * @return AResponse
     */
    static public function getInstance($engine = '') {
        $className = ucfirst($engine) . 'Response';
        if (!class_exists($className, false)) {
            $file = PATH_VEE_ROOT . 'response/' . $className . '.class.php';
            if (is_file($file)) {
                require $file;
            } else {
                require PATH_VEE_ROOT . 'response/VeeResponse.class.php';
                $className = 'VeeResponse';
            }
        }
        if (Config::DEBUG & C::DEBUG_MESSAGE) {
            V::debug('创建Response类：' . $className);
        }
        return new $className();
    }

    /**
     * 设置选项值.
     * 
     * 这些选项在不同的模板引擎意义也不一致, 可以不被使用, 但是不建议另作他用
     * 
     * @param string $name 选项名称
     * @param mixed $value 选项值
     * @return AResponse 返回Response对象本身
     */
    public function setOption($name, $value) {
        $this->options[$name] = $value;
        return $this;
    }

    /**
     * 获取选项值.
     * @param string $name 选项名称
     * @param mixed $defaultValue 默认值
     * @return mixed 返回选项值,如果该选项为设置，则返回null
     */
    public function getOption($name, $defaultValue = null) {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        } else {
            return $defaultValue;
        }
    }

    /*
     * 设置或读取变量值
     * @param string $name 变量名,如果参数类型为数组,则为变量赋值,此时$value参数无效
     * @param mixed $value 变量值,如果该参数未指定,则返回变量值,否则设置变量值
     * @return AResponse 如果参数为 null 则返回 Response 对象本身,否则返回变量值
     */
    /**
     *
     * Enter description here ...
     * @param $name
     * @param $value
     */
    public function value($name, $value = null) {
        if (is_array($name)) { //如果是数组则批量变量赋值
            $this->values = array_merge($this->values, $name);
        } else if (is_object($name)) { //如果是数组则批量变量赋值
            $this->value(get_object_vars($name));
        } else if (null === $value) { //取值
            return $this->values[$name];
        } else { //赋值
            $this->values[$name] = $value;
        }
        return $this;
    }

    /**
     * 获取模板文件路径
     * @param string $fileext 模板文件扩展名, 默认为".tpl.php"
     * @param string $uri 当前Response关联的$uri, 默认取自V::$uri
     * @return string
     */
    public function getTemplateFilename($fileext = '.tpl.php', $uri = false) {
        if (isset($this->options[AResponse::OPTION_TEMPLATE])) {
            $template = PATH_APP_VIEWS . $this->options[AResponse::OPTION_TEMPLATE];
            if (Config::DEBUG & C::DEBUG_MESSAGE) {
                V::debug('使用模板文件：' . $template);
            }
            return $template;
        } else {
            if (false === $uri) {
                $uri = V::$uri;
            }
            $templates = array_fill(0, 6, null);
            $templates[3] = PATH_APP_VIEWS . $uri['path'];
            $templates[2] = $templates[3] . '_' . ucfirst($uri['action']);
            $templates[1] = PATH_APP_CONTROLLERS . $uri['path'];
            $templates[0] = $templates[1] . '_' . ucfirst($uri['action']);
            $templates[4] = PATH_APP_CONTROLLERS . '404';
            $templates[5] = PATH_APP_VIEWS . '404';
            for ($i = 0; $i < 6; ++$i) {
                $templates[$i] .= $fileext;
                if (is_file($templates[$i])) {
                    if (Config::DEBUG & C::DEBUG_MESSAGE) {
                        V::debug('使用模板文件：' . $templates[$i]);
                    }
                    return $templates[$i];
                }
            }
            throw new Exception('找不到模板文件(' . implode(', ', $templates) . ')', 6001);
        }
    }

    /**
     * 刷新输出缓冲
     */
    public function flush() {
        ob_flush();
        flush();
    }

    /**
     * 清空输出缓冲
     */
    public function clean() {
        if (ob_get_length()) {
            ob_clean();
        }
    }

    /**
     * 获取输出内容
     * @param mixed $flag 具体含义由对应的Response类决定
     * @return string
     */
    public function fetch($flag = null) {
        ob_start();
        $this->output($flag);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    /**
     * 响应内容输出
     * @param mixed $flag 具体含义由对应的Response类决定
     * @return string 返回输出内容
     */
    abstract public function output($flag = null);
}
