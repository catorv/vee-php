<?php
// Automatically created by vee.php

require '{_dir_}/vee.cfg.php';

define('APP_ID', {_id_});
define('APP_NAME', '{_name_}');
define('PATH_APP_ROOT', dirname(dirname(__FILE__)) . '/');

if (isset($_REQUEST['DEBUG']) || isset($_COOKIE['DEBUG'])) {
    define('DEBUG_LEVEL', C::DEBUG_ALL ^ C::DEBUG_MESSAGE);
} else if (isset($_REQUEST['NODEBUG'])) {
    define('DEBUG_LEVEL', 0);
}

// define('SESSION_ENABLED', true);

require '{_dir_}/vee.inc.php';

// V::loadClass('database/Db');

V::run();
