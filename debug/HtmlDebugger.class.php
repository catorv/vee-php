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
 * 异常、错误捕获及调试/测试处理类(For HTML)
 *
 * @package vee-php\debug
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class HtmlDebugger {
    /**
     * 错误类型(取值意义与DEBUG状态值相同）
     * @var int
     * @see V
     */
    static private $errorType = 0;

    /**
     * 错误处理函数,即格式化输出错误信息
     *
     * @param int $errno 错误的等级
     * @param string $errstr 错误信息
     * @param string $errfile 包含错误的文件
     * @param int $errline 错误发生的行号
     * @param array $errcontext 错误发生时的环境变量的值
     */
    static public function errorHandler($errno, $errstr, $errfile, $errline,
                                        $errcontext) {
        if (ini_get('display_errors') && (error_reporting() & $errno)) {
            if ($errno == E_USER_NOTICE && $errstr == C::DEBUGTYPE_DEBUG) {
                if ($errcontext['type'] == C::DEBUGTYPE_ORM) {
                    self::$errorType = C::DEBUG_ORM;
                } else {
                    self::$errorType = C::DEBUG_MESSAGE;
                }
            } else if ($errno == E_USER_NOTICE
                       && $errstr == C::DEBUGTYPE_TRACE) {
                self::$errorType = C::DEBUG_TRACE;
            } else {
                self::$errorType = C::DEBUG_ERROR;
            }
            if (Config::DEBUG & self::$errorType) {
                self::showErrorMessage($errno, $errstr, $errfile,
                                       $errline, $errcontext);
            }
        }
    }

    /**
     * 异常处理函数,即格式化输出异常信息.
     * @param Exception $exception
     */
    static public function exceptionHandler($exception) {
        if (ini_get('display_errors')) {
            if (Config::DEBUG & C::DEBUG_EXCEPTION) {
                self::showExceptionMessage($exception);
            }
        }
    }

    static private function showCss() {
        static $cssIsOutput = false;
        if (!$cssIsOutput) {
            echo '<style type="text/css">',
                 ' div.vee-debug {font-size:12px; font-family:Tahoma, Verdana; margin:2px 0;padding:1px 2px 1px 5px;border:1px solid #99c;background:#ccf;}',
                 ' div.vee-debug p {padding:2px 4px;margin:0px;text-align:left;}',
                 ' div.vee-debug p span {color:#666}',
                 ' div.vee-debug p.error-message {background:#ffa2a3}',
                 ' div.vee-debug p.debug-message {background:#f5ff3c}',
                 ' div.vee-debug p.trace-message {background:#badf70}',
                 ' div.vee-debug p.log-message {background:#fff99c}',
                 ' div.vee-debug p.exception-message {background:#f8a2ff}',
                 ' div.vee-debug ul {background:#ccf;list-style:none;padding:0px;margin:0px;}',
                 ' div.vee-debug li {background:#ffd;text-align:left;padding:2px 6px;border-bottom:1px dotted #ccf}',
                 ' div.vee-debug ul.trace li {background:#f2f2f2;}',
                 ' div.vee-debug ul.trace li span.function {color:#000099}',
                 '</style>';
            $cssIsOutput = true;
        }
    }

    static private function showTrace($traces, $i = 1, $showFrame = true) {
        if ($showFrame) {
            echo '<div class="vee-debug">';
        }
        $count = count($traces);
        if ($i < $count) {
            echo '<ul class="trace">';
            for ($n = 1; $i<$count; ++$i, ++$n) {
                $trace = & $traces[$i];
                echo '<li>#' . $n . ' <span class="function">';
                if (isset($trace['class'])) {
                    echo $trace['class'] . $trace['type'];
                }
                echo $trace['function'] . '(';
                $argumentCount = count($trace['args']);
                for ($j=0; $j<$argumentCount; $j++) {
                    echo self::varExport($trace['args'][$j], null);
                    if ($j != count($trace['args']) - 1) {
                        echo ', ';
                    }
                }
                if (isset($trace['file']) && isset($trace['line'])) {
                    echo ')</span> in <b>' . $trace['file']
                            . '</b> on line <b>' . $trace['line']
                            . '</b></li>';
                } else if (isset($traces[$i + 1])) {
                    echo ')</span> in <b>' . $traces[$i + 1]['file']
                            . '</b> on line <b>' . $traces[$i + 1]['line']
                            . '</b></li>';
                    ++$i;
                } else {
                    echo ')</span></li>';
                }
            }
            echo '</ul>';
        }
        if ($showFrame) {
            echo '</div>';
        }
    }

    static private function showErrorMessage($errno, $errstr, $errfile,
                                             $errline, $errcontext) {
        self::showCss();
        $traces = debug_backtrace();
        $i = 2;
        if (self::$errorType == C::DEBUG_ORM) {
            $count = count($traces);
            for (++$i; $i<$count; ++$i) {
                $trace = & $traces[$i];
                if (!isset($trace['file'])
                        || substr(basename($trace['file']), 0, 2) == 'Db') {
                    continue;
                } else {
                    break;
                }
            }
            if (isset($traces[$i + 1])) {
                $t = $traces[$i + 1];
            } else {
                $t = $traces[$i];
            }
            echo '<div class="vee-debug"><p class="log-message">[SQL] ',
                 htmlspecialchars($errcontext['message']), ' <span> - ',
                 (isset($t['class']) ? ($t['class'] . $t['type']) : ''),
                 $t['function'], '() in <b>', $trace['file'],
                 '</b> on line <b>',  $trace['line'], '</b></span>',
                 self::getStat(), '</p></div>';
            return;
        } else if (self::$errorType == C::DEBUG_MESSAGE) {
            if ($errcontext['type'] == 'V::autoload') {
                $trace = & $traces[$i + 3];
                if (isset($traces[$i + 4]['class'])) {
                    $errcontext['type'] = $traces[$i + 4]['class']
                                        . $traces[$i + 4]['type']
                                        . $traces[$i + 4]['function'];
                } else {
                    $errcontext['type'] = $traces[$i + 4]['function'];
                }
            } else {
                $trace = & $traces[$i + 1];
            }
            if($errcontext['type'][0] == '$') {
                echo '<div class="vee-debug"><p class="log-message"><span>[',
                     trim($errcontext['type'], '$'), ']</span> ',
                     htmlspecialchars($errcontext['message']), '<span> - <b>',
                     (isset($traces[$i + 2]['class'])
                        ? ($traces[$i + 2]['class'] . $traces[$i + 2]['type'])
                        : ''
                        ),
                     $traces[$i + 2]['function'], '()</b> in <b>',
                     $trace['file'], '</b> on line <b>',
                     $trace['line'], '</b></span>', self::getStat(),
                     '</p></div>';
            } else {
                echo '<div class="vee-debug"><p class="debug-message"><span>[调试]</span> ',
                     htmlspecialchars($errcontext['message']), '<span> - <b>',
                     (isset($traces[$i + 2]['class'])
                        ? ($traces[$i + 2]['class'] . $traces[$i + 2]['type']
                                . $traces[$i + 2]['function'])
                        : (isset($traces[$i + 2]['function'])
                            ? $traces[$i + 2]['function']
                            : $errcontext['type']
                          )
                        )
                     , '()</b> in <b>',
                     $trace['file'], '</b> on line <b>',
                     $trace['line'], '</b></span>', self::getStat(),
                     '</p></div>';
            }
            return;
        } else if (self::$errorType == C::DEBUG_TRACE) {
            $trace = & $traces[++$i];
            echo '<div class="vee-debug"><p class="trace-message"><b>',
                 $trace['class'], $trace['type'], $trace['function'],
                 '( ', count($trace['args']), ' args ) </b> in <b>',
                 $trace['file'], '</b> on line <b>',
                 $trace['line'], '</b>', self::getStat(),
                 '</p><ul>';
            ++$i;
        } else { // C::DEBUG_ERROR
            echo '<div class="vee-debug"><p class="error-message"><span>[',
                 C::$errorType[$errno], ']</span> <b>',
                 htmlspecialchars($errstr), '</b> in <b>', $errfile,
                 '</b> on line <b>', $errline, '</b>', self::getStat(),
                 '</p><ul>';
        }
        foreach ($errcontext as $name => $var) {
            if (!in_array($name, array('GLOBALS', '_REQUEST',
                                       '_ENV', 'HTTP_ENV_VARS',
                                       '_GET', 'HTTP_GET_VARS',
                                       '_POST', 'HTTP_POST_VARS',
                                       '_COOKIE', 'HTTP_COOKIE_VARS',
                                       '_SERVER', 'HTTP_SERVER_VARS',
                                       '_FILES', 'HTTP_POST_FILES',
                                       ))) {
                echo '<li>', str_replace('&lt;?php&nbsp;', '',
                                highlight_string("<?php $" . $name . ' = '
                                                     . self::varExport($var),
                                                 true)
                                        ), '</li>';
            }
        }
        echo '</ul>';
        self::showTrace($traces, $i, false);
        echo '</div>';
    }

    static private function showExceptionMessage($exception) {
        self::showCss();
        echo '<div class="vee-debug"><p class="exception-message"><span>[',
             $exception->getCode(), ']</span> <b>',
             htmlspecialchars($exception->getMessage()),
             '</b> in <b>',
             $exception->getFile(), '</b> on line <b>',
             $exception->getLine(), '</b>', self::getStat(), '</p>';
        self::showTrace($exception->getTrace(), 0, false);
        echo '</div>';
    }

    static private function getStat() {
        list($current, $total) = V::timer(C::TIMER_ALL);
        return sprintf('<span> (%0.3f/%0.3f ms,  %s/%s bytes)</span>',
                        $current,
                        $total,
                        number_format(memory_get_usage()),
                        number_format(memory_get_peak_usage())
                        );
    }

    static private function varExport(& $var, $prefix = '') {
        if (is_resource($var)) {
            $result = ucwords(get_resource_type($var))
                    . ' Resource (' . intval($var) . ')';
        } else if (is_object($var)) {
            $className = get_class($var);
            $result = $className . ' {';
            if (null !== $prefix) {
                $reflectionClass = new ReflectionClass($className);
                $properties = (array)$var;
                $result .= "\n";
                foreach ($reflectionClass->getProperties() as $property) {
                    $rawName = $name = $property->getName();
                    $result .= '  ';
                    if ($property->isStatic()) {
                        $result .= 'static ';
                    }
                    if ($property->isPublic()) {
                        $result .= 'public ';
                    } else if ($property->isPrivate()) {
                        $result .= 'private ';
                        $rawName = "\0" . $className . "\0" . $rawName;
                    } else if ($property->isProtected()) {
                        $result .= 'protected ';
                        $rawName = "\0*\0" . $rawName;
                    }
                    $result .= '$' . $name . ' = ';
                    $novalue = false;
                    if (array_key_exists($rawName, $properties)) {
                        $value = $properties[$rawName];
                        unset($properties[$rawName]);
                    } else {
                        if(method_exists($property,'setAccessible')) {
                            $property->setAccessible(true);
                            $value = $property->getValue($var);
                        } else if($property->isPublic()) {
                            $value = $property->getValue($var);
                        } else {
                            $novalue = true;
                        }
                    }
                    if ($novalue) {
                        $result .= 'null /* Need PHP 5.3 to get value */';
                    } else if (is_object($value)) {
                        $result .= self::varExport($value, null);
                    } else {
                        $result .= self::varExport($value, $prefix . '  ');
                    }
                    $declaringClass = $property->getDeclaringClass()->getName();
                    if ($declaringClass != $className) {
                        $result .= '; # Inherit from class '
                                . $declaringClass . "\n";
                    } else {
                        $result .= ";\n";
                    }
                }
                foreach ($properties as $name => $value) {
                    $result .= '  public $' . $name . ' = ';
                    if (is_object($value)) {
                        $result .= self::varExport($value, null);
                    } else {
                        $result .= self::varExport($value, $prefix . '  ');
                    }
                    $result .= ";\n";
                }
            } else {
                if (property_exists($var, 'id')) {
                    $result .= ' $id = ' . $var->id . '; ... ';
                } else {
                    $result .= ' ... ';
                }
            }
            $result .= '}';
        } else if (is_array($var)) {
            $result = "array (\n";
            foreach ($var as $name => $value) {
                $result .= '  [';
                if (is_int($name)) {
                    $result .= $name . '] => ';
                } else {
                    $result .= '\'' . $name . '\'] => ';
                }
                if (is_object($value)) {
                    $result .= self::varExport($value, null);
                } else if (is_array($value)) {
                    $result .= self::varExport($value, $prefix);
                } else {
                    $result .= self::varExport($value, $prefix . '  ');
                }
                $result .= ",\n";
            }
            $result .= ')';
        } else {
            $result = var_export($var, true);
        }
        if ('' != $prefix && false !== strpos($result, "\n")) {
            $result = str_replace("\n", "\n" . $prefix, $result);
        }
        return $result;
    }
}