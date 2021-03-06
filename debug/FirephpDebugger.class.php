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
 * 异常、错误捕获及调试/测试处理类(For FirePHP) 
 * 
 * 本函数改自 FirePHPCore library 
 * 
 * @link http://www.firephp.org/
 *
 * @package vee-php\debug
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class FirephpDebugger {

    /** FirePHPCore library version */
    const VERSION = '0.3';
    /** Firebug LOG level: Logs a message to firebug console. */
    const LOG = 'LOG';
    /**
     * Firebug INFO level: Logs a message to firebug console and
     *                     displays an info icon before the message.
     */
    const INFO = 'INFO';
    /**
     * Firebug WARN level: Logs a message to firebug console, displays an
     *                     warning icon before the message and colors
     *                     the line turquoise.
     */
    const WARN = 'WARN';
    /**
     * Firebug ERROR level: Logs a message to firebug console, displays an error
     *                      icon before the message and colors the line yellow.
     *                      Also increments the firebug error count.
     */
    const ERROR = 'ERROR';
    /** Dumps a variable to firebug's server panel */
    const DUMP = 'DUMP';
    /** Displays a stack trace in firebug console */
    const TRACE = 'TRACE';
    /**
     * Displays an exception in firebug console
     * Increments the firebug error count.
     */
    const EXCEPTION = 'EXCEPTION';
    /** Displays an table in firebug console */
    const TABLE = 'TABLE';
    /** Starts a group in firebug console */
    const GROUP_START = 'GROUP_START';
    /** Ends a group in firebug console */
    const GROUP_END = 'GROUP_END';

    /**
     * Singleton instance of FirephpDebugger
     * @var FirephpDebugger
     */
    protected static $instance = null;

    /**
     * Flag whether we are logging from within the exception handler
     * @var boolean
     */
    protected $inExceptionHandler = false;


    /**
     * Wildfire protocol message index
     * @var int
     */
    protected $messageIndex = 1;

    /**
     * Options for the library
     * @var array
     */
    protected $options = array( 'maxObjectDepth' => 2,
                                'maxArrayDepth' => 10,
                                'includeLineNumbers' => true );

    /**
     * Filters used to exclude object members when encoding
     * @var array
     */
    protected $objectFilters = array();

    /**
     * A stack of objects used to detect recursion during object encoding
     * @var object
     */
    protected $objectStack = array();

    /**
     * Flag to enable/disable logging
     * @var boolean
     */
    protected $enabled = true;

    /**
     * 错误类型(取值意义与DEBUG状态值相同）
     * @var int
     * @see V
     */
    static private $errorType = 0;

    /**
     * FirePHP信息显示最多条数
     * @var int
     */
    static private $numbers = 3;

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
                if (0 >= self::$numbers) {
                    if (0 == self::$numbers) {
                        $firephp = self::getInstance(true);
                        self::$numbers--;
                        $firephp->error('这里隐藏了更多的错误或异常');
                    }
                    return;
                }
                self::$numbers--;
                self::$errorType = C::DEBUG_ERROR;
            }
            if (headers_sent()) {
                if (C::DEBUG_ERROR == self::$errorType) {
                    echo $errstr, ' in ', $errfile, ' on line ', $errline;
                }
                return;
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
                if (!headers_sent()) {
                    $firephp = self::getInstance(true);
                    if (!$firephp->inExceptionHandler) {
                        if (0 >= self::$numbers) {
                            if (0 == self::$numbers) {
                                $firephp = self::getInstance(true);
                                self::$numbers--;
                                $firephp->error('这里隐藏了更多的错误或异常');
                            }
                            return;
                        }
                        self::$numbers--;
                        $firephp->inExceptionHandler = true;
                        $firephp->log($exception);
                        $firephp->inExceptionHandler = false;
                        return;
                    }
                }
                echo '[' . $exception->getCode() . '] '
                         . $exception->getMessage();
            }
        }
    }

    static private function showErrorMessage($errno, $errstr, $errfile,
                                             $errline, $errcontext) {
        $firephp = self::getInstance(true);
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
            $firephp->warn($errcontext['message'] . ' - '
                                . (isset($t['class'])
                                        ? ($t['class'] . $t['type']) : ''
                                  )
                                . $t['function']
                                . '() in ' . $trace['file']
                                . ' on line ' . $trace['line']
                                . self::getStat(),
                          'SQL');
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
                $firephp->warn($errcontext['message'] . ' - '
                                . $errcontext['type'] . '() in '
                                . $trace['file'] . ' on line ' . $trace['line']
                                . self::getStat(),
                                '调试');
            } else if($errcontext['type'][0] == '$') {
                $trace = & $traces[$i + 1];
                $firephp->warn($errcontext['message'] . ' - '
                                . (isset($traces[$i + 2]['class'])
                                        ? ($traces[$i + 2]['class']
                                                . $traces[$i + 2]['type'])
                                        : ''
                                  )
                                . $traces[$i + 2]['function'] . '() in '
                                . $trace['file'] . ' on line ' . $trace['line']
                                . self::getStat(),
                           trim($errcontext['type'], '$'));
            } else {
                $trace = & $traces[$i + 1];
                $firephp->info($errcontext['message'] . ' - '
                                . (isset($traces[$i + 2]['class'])
                                    ? ($traces[$i + 2]['class']
                                            . $traces[$i + 2]['type']
                                            . $traces[$i + 2]['function'])
                                    : (isset($traces[$i + 2]['function'])
                                        ? $traces[$i + 2]['function']
                                        : $errcontext['type']
                                      )
                                  )
                                . '() in ' . $trace['file']
                                . ' on line ' . $trace['line']
                                . self::getStat(),
                                '调试');
            }
        } else if (self::$errorType == C::DEBUG_TRACE) {
            $trace = & $traces[$i + 1];
//            $firephp->group($trace['class'] . $trace['type']
//                            . $trace['function'] . '(' . count($trace['args'])
//                            . ' args) in ' . (isset($trace['file']) ? $trace['file'] : 'unknown')
//                            . ' on line ' . (isset($trace['line']) ? $trace['line'] : '0') . self::getStat(),
//                           array('Color' => '#000099'));
//            foreach ($errcontext as $name => $var) {
//                $name = ltrim($name, '_');
//                if (is_numeric($name)) {
//                    $firephp->log($var, $name + 1);
//                }
//            }
//            $firephp->trace();
//            $firephp->groupEnd();
            $prefix = $trace['class'] . $trace['type'] . $trace['function'] . '(';
            $suffix = ') in ' . (isset($trace['file']) ? $trace['file'] : 'unknown')
                                . ' on line ' . (isset($trace['line']) ? $trace['line'] : '0')
                                . self::getStat();
            if (count($errcontext) == 1) {
                if (is_object($errcontext['_0'])) {
                    $firephp->trace($prefix . '{' . get_class($errcontext['_0']) . '}' . $suffix);
                    foreach (get_object_vars($errcontext['_0']) as $name => $value) {
                        $firephp->info(is_string($value) ? var_export($value, true) : $value, '$' . $name);
                    }
                } else if (is_array($errcontext['_0'])) {
                    $firephp->trace($prefix . '[Array]' . $suffix);
                    foreach ($errcontext['_0'] as $name => $value) {
                        $firephp->info(is_string($value) ? var_export($value, true) : $value, '[' . $name . ']');
                    }
                } else if (is_resource($errcontext['_0'])) {
                    $firephp->trace($prefix . strval($errcontext['_0']) . $suffix);
                } else {
                    $firephp->trace($prefix . var_export($errcontext['_0'], true) . $suffix);
                }
            } else {
                $firephp->trace($prefix . count($trace['args']) . ' args' . $suffix);
                foreach ($errcontext as $name => $var) {
                    $name = ltrim($name, '_');
                    if (is_numeric($name)) {
                        $firephp->info($var, $name + 1);
                    }
                }
            }
        } else { // C::DEBUG_ERROR
            $firephp->group(C::$errorType[$errno] . ': ' . $errstr
                                . ' in ' . $errfile . ' on line ' . $errline
                                . self::getStat(),
                           array('Collapsed' => true, 'Color' => '#ff0000'));
            foreach ($errcontext as $name => $var) {
                if (!in_array($name, array('GLOBALS', '_REQUEST',
                                           '_ENV', 'HTTP_ENV_VARS',
                                           '_GET', 'HTTP_GET_VARS',
                                           '_POST', 'HTTP_POST_VARS',
                                           '_COOKIE', 'HTTP_COOKIE_VARS',
                                           '_SERVER', 'HTTP_SERVER_VARS',
                                           '_FILES', 'HTTP_POST_FILES',
                                           ))) {
                    $firephp->log($var, '$' . $name);
                }
            }
            $firephp->trace();
            $firephp->groupEnd();
        }
    }

    static private function getStat() {
        list($current, $total) = V::timer(C::TIMER_ALL);
        return sprintf(' (%0.3f/%0.3f ms,  %s/%s bytes)',
                        $current,
                        $total,
                        number_format(memory_get_usage()),
                        number_format(memory_get_peak_usage())
                        );
    }

    /**
     * The object constructor
     */
    public function __construct() {
    }

    /**
     * When the object gets serialized only include specific object members.
     *
     * @return array
     */
    public function __sleep() {
        return array('options', 'objectFilters', 'enabled');
    }

    /**
     * Gets singleton instance of FirePHP
     *
     * @param boolean $AutoCreate
     * @return FirephpDebugger
     */
    public static function getInstance($AutoCreate=false) {
        if($AutoCreate===true && !self::$instance) {
            self::init();
        }
        return self::$instance;
    }

    /**
     * Creates FirePHP object and stores it for singleton access
     *
     * @return FirephpDebugger
     */
    public static function init() {
        return self::$instance = new FirephpDebugger();
    }

    /**
     * Enable and disable logging to Firebug
     *
     * @param boolean $Enabled TRUE to enable, FALSE to disable
     * @return void
     */
    public function setEnabled($Enabled) {
        $this->enabled = $Enabled;
    }

    /**
     * Check if logging is enabled
     *
     * @return boolean TRUE if enabled
     */
    public function getEnabled() {
        return $this->enabled;
    }

    /**
     * Specify a filter to be used when encoding an object
     *
     * Filters are used to exclude object members.
     *
     * @param string $Class The class name of the object
     * @param array $Filter An array of members to exclude
     * @return void
     */
    public function setObjectFilter($Class, $Filter) {
        $this->objectFilters[strtolower($Class)] = $Filter;
    }

    /**
     * Set some options for the library
     *
     * Options:
     *  - maxObjectDepth: The maximum depth to traverse objects (default: 10)
     *  - maxArrayDepth: The maximum depth to traverse arrays (default: 20)
     *  - includeLineNumbers: If true will include line numbers and filenames (default: true)
     *
     * @param array $Options The options to be set
     * @return void
     */
    public function setOptions($Options) {
        $this->options = array_merge($this->options,$Options);
    }

    /**
     * Get options from the library
     *
     * @return array The currently set options
     */
    public function getOptions() {
        return $this->options;
    }

    /**
     * Set custom processor url for FirePHP
     *
     * @param string $URL
     */
    public function setProcessorUrl($URL) {
        $this->setHeader('X-FirePHP-ProcessorURL', $URL);
    }

    /**
     * Set custom renderer url for FirePHP
     *
     * @param string $URL
     */
    public function setRendererUrl($URL) {
        $this->setHeader('X-FirePHP-RendererURL', $URL);
    }

    /**
     * Start a group for following messages.
     *
     * Options:
     *   Collapsed: [true|false]
     *   Color:     [#RRGGBB|ColorName]
     *
     * @param string $Name
     * @param array $Options OPTIONAL Instructions on how to log the group
     * @return true
     * @throws Exception
     */
    public function group($Name, $Options=null) {
        if(!$Name) {
            throw $this->newException('You must specify a label for the group!');
        }

        if($Options) {
            if(!is_array($Options)) {
                throw $this->newException('Options must be defined as an array!');
            }
            if(array_key_exists('Collapsed', $Options)) {
                $Options['Collapsed'] = ($Options['Collapsed'])?'true':'false';
            }
        }

        return $this->fb(null, $Name, self::GROUP_START, $Options);
    }

    /**
     * Ends a group you have started before
     *
     * @return true
     * @throws Exception
     */
    public function groupEnd() {
        return $this->fb(null, null, self::GROUP_END);
    }

    /**
     * Log object with label to firebug console
     *
     * @see FirephpDebugger::LOG
     * @param mixes $Object
     * @param string $Label
     * @return true
     * @throws Exception
     */
    public function log($Object, $Label=null) {
        return $this->fb($Object, $Label, self::LOG);
    }

    /**
     * Log object with label to firebug console
     *
     * @see FirephpDebugger::INFO
     * @param mixes $Object
     * @param string $Label
     * @return true
     * @throws Exception
     */
    public function info($Object, $Label=null) {
        return $this->fb($Object, $Label, self::INFO);
    }

    /**
     * Log object with label to firebug console
     *
     * @see FirephpDebugger::WARN
     * @param mixes $Object
     * @param string $Label
     * @return true
     * @throws Exception
     */
    public function warn($Object, $Label=null) {
        return $this->fb($Object, $Label, self::WARN);
    }

    /**
     * Log object with label to firebug console
     *
     * @see FirephpDebugger::ERROR
     * @param mixes $Object
     * @param string $Label
     * @return true
     * @throws Exception
     */
    public function error($Object, $Label=null) {
        return $this->fb($Object, $Label, self::ERROR);
    }

    /**
     * Dumps key and variable to firebug server panel
     *
     * @see FirephpDebugger::DUMP
     * @param string $Key
     * @param mixed $Variable
     * @return true
     * @throws Exception
     */
    public function dump($Key, $Variable) {
        return $this->fb($Variable, $Key, self::DUMP);
    }

    /**
     * Log a trace in the firebug console
     *
     * @see FirephpDebugger::TRACE
     * @param string $Label
     * @return true
     * @throws Exception
     */
    public function trace($Label = '...') {
        return $this->fb($Label, self::TRACE);
    }

    /**
     * Log a table in the firebug console
     *
     * @see FirephpDebugger::TABLE
     * @param string $Label
     * @param string $Table
     * @return true
     * @throws Exception
     */
    public function table($Label, $Table) {
        return $this->fb($Table, $Label, self::TABLE);
    }

    /**
     * Check if FirePHP is installed on client
     *
     * @return boolean
     */
    public function detectClientExtension() {
        /* Check if FirePHP is installed on client */
        if(!@preg_match_all('/\sFirePHP\/([\.|\d]*)\s?/si',$this->getUserAgent(),$m)
                || !version_compare($m[1][0],'0.0.6','>=')) {
            return false;
        }
        return true;
    }

    /**
     * Log varible to Firebug
     *
     * @see http://www.firephp.org/Wiki/Reference/Fb
     * @param mixed $Object The variable to be logged
     * @return true Return TRUE if message was added to headers, FALSE otherwise
     * @throws Exception
     */
    public function fb($Object) {

        if (!$this->enabled) {
            return false;
        }

        if (headers_sent($filename, $linenum)) {
            // If we are logging from within the exception handler we cannot throw another exception
            if($this->inExceptionHandler) {
                // Simply echo the error out to the page
                echo '<div style="border: 2px solid red; font-family: Arial; font-size: 12px; background-color: lightgray; padding: 5px;"><span style="color: red; font-weight: bold;">FirePHP ERROR:</span> Headers already sent in <b>'.$filename.'</b> on line <b>'.$linenum.'</b>. Cannot send log data to FirePHP. You must have Output Buffering enabled via ob_start() or output_buffering ini directive.</div>';
            } else {
                throw $this->newException('Headers already sent in '.$filename.' on line '.$linenum.'. Cannot send log data to FirePHP. You must have Output Buffering enabled via ob_start() or output_buffering ini directive.');
            }
        }

        $Type = null;
        $Label = null;
        $Options = array();

        if (func_num_args()==1) {
            /* nothing */
        } else if(func_num_args()==2) {
            switch(func_get_arg(1)) {
                case self::LOG:
                case self::INFO:
                case self::WARN:
                case self::ERROR:
                case self::DUMP:
                case self::TRACE:
                case self::EXCEPTION:
                case self::TABLE:
                case self::GROUP_START:
                case self::GROUP_END:
                    $Type = func_get_arg(1);
                    break;
                default:
                    $Label = func_get_arg(1);
                    break;
            }
        } else if (func_num_args()==3) {
            $Type = func_get_arg(2);
            $Label = func_get_arg(1);
        } else if (func_num_args()==4) {
            $Type = func_get_arg(2);
            $Label = func_get_arg(1);
            $Options = func_get_arg(3);
        } else {
            throw $this->newException('Wrong number of arguments to fb() function!');
        }


        if (!$this->detectClientExtension()) {
            return false;
        }

        $meta = array();
        $skipFinalObjectEncode = false;

        if ($Object instanceof Exception) {

            $meta['file'] = $this->_escapeTraceFile($Object->getFile());
            $meta['line'] = $Object->getLine();

            $trace = $Object->getTrace();
            if ($Object instanceof ErrorException
                    && isset($trace[0]['function'])
                    && $trace[0]['function']=='errorHandler'
                    && isset($trace[0]['class'])
                    && $trace[0]['class']==__CLASS__) {

                $severity = false;
                switch ($Object->getSeverity()) {
                    case E_WARNING: $severity = 'E_WARNING'; break;
                    case E_NOTICE: $severity = 'E_NOTICE'; break;
                    case E_USER_ERROR: $severity = 'E_USER_ERROR'; break;
                    case E_USER_WARNING: $severity = 'E_USER_WARNING'; break;
                    case E_USER_NOTICE: $severity = 'E_USER_NOTICE'; break;
                    case E_STRICT: $severity = 'E_STRICT'; break;
                    case E_RECOVERABLE_ERROR: $severity = 'E_RECOVERABLE_ERROR'; break;
                    case E_DEPRECATED: $severity = 'E_DEPRECATED'; break;
                    case E_USER_DEPRECATED: $severity = 'E_USER_DEPRECATED'; break;
                }

                $Object = array('Class'=>get_class($Object),
                        'Message'=>$severity.': '.$Object->getMessage(),
                        'File'=>$this->_escapeTraceFile($Object->getFile()),
                        'Line'=>$Object->getLine(),
                        'Type'=>'trigger',
                        'Trace'=>$this->_escapeTrace(array_splice($trace,2)));
                $skipFinalObjectEncode = true;
            } else {
                $Object = array('Class'=>get_class($Object),
                        'Message'=>$Object->getMessage().' ('.$Object->getCode().') in '.$Object->getFile().' on line '.$Object->getLine(),
                        'File'=>$this->_escapeTraceFile($Object->getFile()),
                        'Line'=>$Object->getLine(),
                        'Type'=>'throw',
                        'Trace'=>$this->_escapeTrace($trace));
                $skipFinalObjectEncode = true;
            }
            $Type = self::EXCEPTION;

        } else if ($Type==self::TRACE) {

            $trace = debug_backtrace();
            if (!$trace) return false;
            for ( $i=0 ; $i<sizeof($trace) ; $i++ ) {
                if (isset($trace[$i]['class'])
                        && $trace[$i]['class'] == __CLASS__) {
                    /* Skip - class FirephpDebugger */
                } else if ($trace[$i]['function'] == 'trigger_error'
                           && basename($trace[$i]['file']) == 'V.class.php') {
                    /* Skip - trigger_error() in V.class.php */
                } else {
                    $meta['file'] = isset($trace[$i]['file'])?$this->_escapeTraceFile($trace[$i]['file']):$this->_escapeTraceFile($trace[$i+1]['file']);
                    $meta['line'] = isset($trace[$i]['line'])?$trace[$i]['line']:$trace[$i+1]['line'];
                    $Object = array('Class'=>isset($trace[$i]['class'])?$trace[$i]['class']:'',
                          'Type'=>isset($trace[$i]['type'])?$trace[$i]['type']:'',
                          'Function'=>isset($trace[$i]['function'])?$trace[$i]['function']:'',
                          'Message'=>isset($trace[0]['args'][0])?$trace[0]['args'][0]:'...',
                          'File'=>$meta['file'],
                          'Line'=>$meta['line'],
                          'Args'=>isset($trace[$i]['args'])?$this->encodeObject($trace[$i]['args']):'',
                          'Trace'=>$this->_escapeTrace(array_splice($trace,$i+1)));

                    $skipFinalObjectEncode = true;
                    break;
                }
            }

        } else if ($Type==self::TABLE) {

            if (isset($Object[0]) && is_string($Object[0])) {
                $Object[1] = $this->encodeTable($Object[1]);
            } else {
                $Object = $this->encodeTable($Object);
            }

            $skipFinalObjectEncode = true;

        } else if ($Type==self::GROUP_START) {

            if (!$Label) {
                throw $this->newException('You must specify a label for the group!');
            }

        } else {
            if ($Type===null) {
                $Type = self::LOG;
            }
        }

        if ($this->options['includeLineNumbers']) {
            if (!isset($meta['file']) || !isset($meta['line'])) {
                $trace = debug_backtrace();
                for ( $i=0 ; $trace && $i<sizeof($trace) ; $i++ ) {

                    if (isset($trace[$i]['class'])
                            && $trace[$i]['class'] == __CLASS__) {
                        /* Skip - class FirephpDebugger */
                    } else if ($trace[$i]['function'] == 'trigger_error'
                            && basename($trace[$i]['file']) == 'V.class.php') {
                        /* Skip - trigger_error() */
                    } else {
                        $meta['file'] = isset($trace[$i]['file'])?$this->_escapeTraceFile($trace[$i]['file']):$this->_escapeTraceFile($trace[$i+1]['file']);
                        $meta['line'] = isset($trace[$i]['line'])?$trace[$i]['line']:$trace[$i+1]['line'];
                        break;
                    }
                }

            }
        } else {
            unset($meta['file']);
            unset($meta['line']);
        }

        $this->setHeader('X-Wf-Protocol-1','http://meta.wildfirehq.org/Protocol/JsonStream/0.2');
        $this->setHeader('X-Wf-1-Plugin-1','http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/'.self::VERSION);

        $structure_index = 1;
        if ($Type==self::DUMP) {
            $structure_index = 2;
            $this->setHeader('X-Wf-1-Structure-2','http://meta.firephp.org/Wildfire/Structure/FirePHP/Dump/0.1');
        } else {
            $this->setHeader('X-Wf-1-Structure-1','http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1');
        }

        if ($Type==self::DUMP) {
            $msg = '{"'.$Label.'":'.$this->jsonEncode($Object, $skipFinalObjectEncode).'}';
        } else {
            $msg_meta = $Options;
            $msg_meta['Type'] = $Type;
            if ($Label!==null) {
                $msg_meta['Label'] = $Label;
            }
            if (isset($meta['file']) && !isset($msg_meta['File'])) {
                $msg_meta['File'] = $meta['file'];
            }
            if (isset($meta['line']) && !isset($msg_meta['Line'])) {
                $msg_meta['Line'] = $meta['line'];
            }
            $msg = '['.$this->jsonEncode($msg_meta).','.$this->jsonEncode($Object).']';
//            $msg = '['.$this->jsonEncode($msg_meta).','.$this->jsonEncode($Object, $skipFinalObjectEncode).']';
        }

        $parts = explode("\n",chunk_split($msg, 5000, "\n"));

        for ( $i=0 ; $i<count($parts) ; $i++) {

            $part = $parts[$i];
            if ($part) {

                if (count($parts)>2) {
                    // Message needs to be split into multiple parts
                    $this->setHeader('X-Wf-1-'.$structure_index.'-'.'1-'.$this->messageIndex,
                    (($i==0)?strlen($msg):'')
                    . '|' . $part . '|'
                    . (($i<count($parts)-2)?'\\':''));
                } else {
                    $this->setHeader('X-Wf-1-'.$structure_index.'-'.'1-'.$this->messageIndex,
                    strlen($part) . '|' . $part . '|');
                }

                $this->messageIndex++;

                if ($this->messageIndex > 99999) {
                    throw $this->newException('Maximum number (99,999) of messages reached!');
                }
            }
        }

        $this->setHeader('X-Wf-1-Index',$this->messageIndex-1);

        return true;
    }

    /**
     * Standardizes path for windows systems.
     *
     * @param string $Path
     * @return string
     */
    protected function _standardizePath($Path) {
        return preg_replace('/\\\\+/','/',$Path);
    }

    /**
     * Escape trace path for windows systems
     *
     * @param array $Trace
     * @return array
     */
    protected function _escapeTrace($Trace) {
        if(!$Trace) return $Trace;
        $result = array();
        $count = count($Trace);
        for( $i=0 ; $i<$count ; $i++ ) {
            $result[] = & $Trace[$i];
            if(isset($Trace[$i]['file'])) {
                $Trace[$i]['file'] = $this->_escapeTraceFile($Trace[$i]['file']);
            } else if (isset($Trace[$i + 1]) && isset($Trace[$i + 1]['file']) && isset($Trace[$i + 1]['line'])) {
                $Trace[$i]['file'] = $this->_escapeTraceFile($Trace[$i + 1]['file']);
                $Trace[$i]['line'] = $Trace[$i + 1]['line'];
                ++$i;
            } else {
                $Trace[$i]['file'] = '';
                $Trace[$i]['line'] = '';
            }
            if(isset($Trace[$i]['args'])) {
                $Trace[$i]['args'] = $this->encodeObject($Trace[$i]['args']);
            }
//            if(isset($Trace[$i]['object'])) {
//                $Trace[$i]['object'] = 'object';
//                $Trace[$i]['object'] = $this->encodeObject($Trace[$i]['object']);
//            }
        }
        return $result;
    }

    /**
     * Escape file information of trace for windows systems
     *
     * @param string $File
     * @return string
     */
    protected function _escapeTraceFile($File) {
        /* Check if we have a windows filepath */
        if(strpos($File,'\\')) {
            /* First strip down to single \ */

            $file = preg_replace('/\\\\+/','\\',$File);

            return $file;
        }
        return $File;
    }

    /**
     * Send header
     *
     * @param string $Name
     * @param string_type $Value
     */
    protected function setHeader($Name, $Value) {
        return header($Name.': '.$Value);
    }

    /**
     * Get user agent
     *
     * @return string|false
     */
    protected function getUserAgent() {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) return false;
        return $_SERVER['HTTP_USER_AGENT'];
    }

    /**
     * Returns a new exception
     *
     * @param string $Message
     * @return Exception
     */
    protected function newException($Message) {
        return new Exception($Message);
    }

    /**
     * Encode an object into a JSON string
     *
     * Uses PHP's jeson_encode() if available
     *
     * @param object $Object The object to be encoded
     * @return string The JSON string
     */
    public function jsonEncode($Object, $skipObjectEncode=false) {
        if (!$skipObjectEncode) {
            $Object = $this->encodeObject($Object);
        }

        return json_encode($Object);
    }

    /**
     * Encodes a table by encoding each row and column with encodeObject()
     *
     * @param array $Table The table to be encoded
     * @return array
     */
    protected function encodeTable($Table) {

        if (!$Table) return $Table;

        $new_table = array();
        foreach ($Table as $row) {

            if (is_array($row)) {
                $new_row = array();

                foreach ($row as $item) {
                    $new_row[] = $this->encodeObject($item);
                }

                $new_table[] = $new_row;
            }
        }

        return $new_table;
    }

    /**
     * Encodes an object including members with
     * protected and private visibility
     *
     * @param Object $Object The object to be encoded
     * @param int $Depth The current traversal depth
     * @return array All members of the object
     */
    protected function encodeObject($Object, $ObjectDepth = 1, $ArrayDepth = 1) {
        $return = array();

        if (is_resource($Object)) {
            return '** ' . ucwords(get_resource_type($Object))
                    . ' Resource (' . intval($Object) . ') **';
        } else if (is_object($Object)) {

            if ($ObjectDepth > $this->options['maxObjectDepth']) {
                return '** Max Object Depth ('.$this->options['maxObjectDepth'].') **';
            }

            foreach ($this->objectStack as $refVal) {
                if ($refVal === $Object) {
                    return '** Recursion ('.get_class($Object).') **';
                }
            }
            array_push($this->objectStack, $Object);

            $return['__className'] = $class = get_class($Object);
            $class_lower = strtolower($class);

            $reflectionClass = new ReflectionClass($class);
            $properties = array();
            foreach( $reflectionClass->getProperties() as $property) {
                $properties[$property->getName()] = $property;
            }

            $members = (array)$Object;

            foreach( $properties as $raw_name => $property ) {

                $name = $raw_name;
                if($property->isStatic()) {
                    $name = 'static:'.$name;
                }
                if($property->isPublic()) {
                    $name = 'public:'.$name;
                } else
                if($property->isPrivate()) {
                    $name = 'private:'.$name;
                    $raw_name = "\0".$class."\0".$raw_name;
                } else
                if($property->isProtected()) {
                    $name = 'protected:'.$name;
                    $raw_name = "\0".'*'."\0".$raw_name;
                }

                if(!(isset($this->objectFilters[$class_lower])
                    && is_array($this->objectFilters[$class_lower])
                    && in_array($raw_name,$this->objectFilters[$class_lower]))) {

                    if(array_key_exists($raw_name,$members)
                            && !$property->isStatic()) {

                        $return[$name] = $this->encodeObject($members[$raw_name], $ObjectDepth + 1, 1);

                    } else {
                        if(method_exists($property,'setAccessible')) {
                            $property->setAccessible(true);
                            $return[$name] = $this->encodeObject($property->getValue($Object), $ObjectDepth + 1, 1);
                        } else
                        if($property->isPublic()) {
                            $return[$name] = $this->encodeObject($property->getValue($Object), $ObjectDepth + 1, 1);
                        } else {
                            $return[$name] = '** Need PHP 5.3 to get value **';
                        }
                    }
                } else {
                    $return[$name] = '** Excluded by Filter **';
                }
            }

            // Include all members that are not defined in the class
            // but exist in the object
            foreach( $members as $raw_name => $value ) {

                $name = $raw_name;

                if ($name{0} == "\0") {
                    $parts = explode("\0", $name);
                    $name = $parts[2];
                }

                if(!isset($properties[$name])) {
                    $name = 'undeclared:'.$name;

                    if(!(isset($this->objectFilters[$class_lower])
                    && is_array($this->objectFilters[$class_lower])
                    && in_array($raw_name,$this->objectFilters[$class_lower]))) {

                        $return[$name] = $this->encodeObject($value, $ObjectDepth + 1, 1);
                    } else {
                        $return[$name] = '** Excluded by Filter **';
                    }
                }
            }

            array_pop($this->objectStack);

        } else if (is_array($Object)) {

            if ($ArrayDepth > $this->options['maxArrayDepth']) {
                return '** Max Array Depth ('.$this->options['maxArrayDepth'].') **';
            }

            foreach ($Object as $key => $val) {

                // Encoding the $GLOBALS PHP array causes an infinite loop
                // if the recursion is not reset here as it contains
                // a reference to itself. This is the only way I have come up
                // with to stop infinite recursion in this case.
                if($key=='GLOBALS' && is_array($val)
                        && array_key_exists('GLOBALS',$val)) {
                    $val['GLOBALS'] = '** Recursion (GLOBALS) **';
                }

                $return[$key] = $this->encodeObject($val, 1, $ArrayDepth + 1);
            }
        } else {
            if (self::is_utf8($Object)) {
                return $Object;
            } else {
                return utf8_encode($Object);
            }
        }
        return $return;
    }

    /**
     * Returns true if $string is valid UTF-8 and false otherwise.
     *
     * @param mixed $str String to be tested
     * @return boolean
     */
    protected static function is_utf8($str) {
        $c=0; $b=0;
        $bits=0;
        $len=strlen($str);
        for ($i=0; $i<$len; $i++){
            $c=ord($str[$i]);
            if($c > 128){
                if(($c >= 254)) return false;
                elseif($c >= 252) $bits=6;
                elseif($c >= 248) $bits=5;
                elseif($c >= 240) $bits=4;
                elseif($c >= 224) $bits=3;
                elseif($c >= 192) $bits=2;
                else return false;
                if(($i+$bits) > $len) return false;
                while($bits > 1){
                    $i++;
                    $b=ord($str[$i]);
                    if($b < 128 || $b > 191) return false;
                    $bits--;
                }
            }
        }
        return true;
    }
}
