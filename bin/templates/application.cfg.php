<?php
/**
 * 应用程序选项配置类（Options）
 *
 * @package {_name_}\config
 * @copyright Copyright (c) {_year_} {_organization_name_}. All rights reserved.
 * @author {_username_}
 */
class Config {
    /** 应用系统名称 */
    const NAME                  = APP_NAME;
    /** 应用系统版本 */
    const VERSION               = '1.0';
    /** 公司名 */
    const COMPANY               = '公司名称';
    /** 时区 */
    const TIMEZONE              = 'Asia/Shanghai';
    /** 本地化信息 */
    const LOCALE                = 'zh_CN.UTF-8';
    /** 系统是否开放 */
    const DISABLED              = false;
    /** 系统超时秒数 */
    const TIMEOUT               = 40;

    /**
     * 调试开关/等级, 会影响错误和异常的LOG信息量, 该配置项的值等于所有开放功能
     * 相"或"之后的值, 值为0代表关闭DEBUG信息
     */
    const DEBUG                 = DEBUG_LEVEL;
    /** 是否启用FirePHP调试器 */
    const FIREPHP_ENABLED       = true;
    /** 系统日志（调试、错误及异常信息）配置项ID，设置为 -1 则屏蔽系统日志功能 */
    const SYS_LOG_ID            = -1;
    /** 当所有调试/错误/异常处理机制全部关闭时，这个参数决定是否打开log_errors记录信息 */
    const ERROR_LOG_ENABLED     = false;

    /**
     * URI协议, 指定控制器模块(Controller Module)、控制器名称(Controller Name)
     * 以及动作名称(Action Name)的获取途径，取值范围如下： 
     *  AUTO            自动检测（默认） 
     *  PATH_INFO       使用 PATH_INFO（推荐） 
     *  REQUEST         使用 REQUEST 值 (GET/POST) 
     *  QUERY_STRING    使用 QUERY_STRING（不推荐） 
     *  REQUEST_URI     使用 REQUEST_URI（推荐） 
     */
    const URI_PROTOCOL          = 'REQUEST_URI';
    /** 当 URI_PROTOCOL = 'REQUEST' 时，传递Action信息变量的键值 */
    const URI_ACTION_KEY        = 'do';
    /** 当未指定 Response Engine 时，传递 Response Engine 信息变量的键值 */
    const URI_RESPONSE_KEY      = 're';
    /** 解析URI协议时的结束字符串，常用于在URL后加上.html实现伪静态 */
    const URI_END_STRING        = '.html';

    /** 是否缓存ORM实体对象 */
    const ORM_CACHE             = true;
    /** 用于缓存ORM实体对象的CACHE配置项ID */
    const ORM_CACHE_ID          = 0;
    /** 用于缓存ORM实体对象的CACHE项键值前缀 */
    const ORM_CACHE_PREFIX      = 'orm/entities/';

    /** The path on the server in which the cookie will be available on */
    const COOKIES_PATH          = '/';
    /** The domain that the cookie is available */
    const COOKIES_DOMAIN        = APP_DOMAIN;
    /** Cookies有效期秒数 */
    const COOKIES_EXPIRE        = 86400;
    /** Cookies变量前缀 */
    const COOKIES_PREFIX        = 'VC_';

    /** 保存语言语种的变量名称 */
    const VAR_LANG              = 'VV_LANG';
    /** 保存用户组ID(Group Id)的变量名称 */
    const VAR_GID               = 'VV_GID';
    /** 保存用户ID(User Id)的变量名称 */
    const VAR_UID               = 'VV_UID';
    /** 保存用户名称(User Name)的变量名称 */
    const VAR_UNAME             = 'VV_UNAME';
    /** 保存角色ID(Role Id)的变量名称 */
    const VAR_RID               = 'VV_RID';
    /** 保存角色名(Role Name)的变量名称 */
    const VAR_RNAME             = 'VV_RNAME';
    /** 保存用户登录时间的变量名称 */
    const VAR_LOGIN_TIME        = 'VV_LOGIN_TIME';
    /** 保存用户最后生存(最后一次与服务器的交互)时间的变量名称 */
    const VAR_LIVE_TIME         = 'VV_LIVE_TIME';

    /**
     * Response对象使用到的配置参数 
     *      charset          字符集 
     *      title            标题 
     *      cached           响应数据是否允许访问者本地缓存 (保留设置,暂无效果) 
     *      engine           默认Response引擎 (vee|json|xml|excel) 
     *      language         默认语言包 
     *      autoOutput       自动输出响应数据 
     *      pageSize         分页的默认大小 
     * @var array
     */
    static public $response = array(
            'charset'           => 'UTF-8',
            'title'             => '${name}',
            'cached'            => false,
            'engine'            => 'vee',
            'language'          => 'AUTO',
            'autoOutput'        => true,
            'pageSize'          => 20,
        );

    /**
     * 路由配置。每个路由配置项的格式为：array('form' => 'to')
     * @var array
     */
    static public $route = array(
//            '/^news-(\d+).*/'        => 'demo-test/hello/$1',
        );

    /**
     * Cache 配置参数，根据 Cache engine 不同，配置项定义如下： 
     * 当使用 Memcached 做 cache 存储时， engine = memcache （推荐） 
     *      host            Memcached服务器地址 
     *      port            端口号 
     *      timeout         超时时间(秒) 
     *      prefix          键名前缀 
     *      persistent      是否使用持续化连接 
     * 当使用文件做 cache 存储时， engine = file 
     *      root            文件Cache的根路径。可选，默认值为 PATH_APP_CACHE 
     * @var array
     */
    static public $cache = array(
//            array( // Memcache Cache
//                'engine'        => 'memcache',
//                'host'          => '192.168.158.14',
//                'port'          => 11211,
//                'timeout'       => 10,
//                'prefix'        => 'vee_',
//                'persistent'    => true,
//            ),
            array( // File Cache
                'engine'        => 'file',
//                'root'          => PATH_APP_CACHE,
            ),
        );

    /**
     * Log 配置参数，根据 Log engine 不同，配置项定义如下： 
     * 当使用文件做 Log 存储时， engine = file 
     *      root            文件Log的根路径。可选，默认值为 PATH_APP_LOG 
     * 当使用 Memcached 做 Log 存储时， engine = memcache 
     *      host            Memcached服务器地址 
     *      port            端口号 
     *      timeout         超时时间(秒) 
     *      prefix          键名前缀 
     *      persistent      是否使用持续化连接 
     * 当使用数据库做 Log 存储时， engine = db （推荐） 
     *      db              数据库配置索引,参见Config::$db数组,默认值为0 
     * @var array
     */
    static public $log = array(
//            array( // Memcache LOG
//                'engine'        => 'memcache',
//                'host'          => '192.168.158.14',
//                'port'          => 11211,
//                'timeout'       => 10,
//                'prefix'        => 'vlog_',
//                'persistent'    => true,
//            ),
//            array( // DB LOG
//                'engine'        => 'db',
//                'db'            => 0,
//            ),
            array( // File LOG
                'engine'        => 'file',
            ),
        );
    
    /**
     * 数据库配置
     * @var array
     */
    static public $db = array();

    /**
     * 系统类列表,用于自动装载类（autoload）。
     * 格式：array( className => 'path/to/filename.class.php' );
     * @var array
     */
    static public $autoload = array();

    /**
     * 外部插件库配置项
     * @var array
     */
    static public $externals = array(
        /* Smarty 配置 */
        'smarty' => array(
            'left_delimiter'    => '<?=',
            'right_delimiter'   => '?>',
//            'force_compile'     => true, //强制每次都编译模板，在开发调试时特有用
        ),
    );
}

V::loadConfig('db');
