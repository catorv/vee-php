#!/usr/bin/env php
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
require_once 'inc/common.inc.php';
require_once 'inc/project.inc.php';
require_once 'inc/model.inc.php';
require_once 'inc/controller.inc.php';

date_default_timezone_set('Asia/Shanghai');
setlocale(LC_ALL, 'zh_CN.UTF-8');


define('PATH_VEE_BIN', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('VEE_PHP_FILE', '.vee.php');

if ($name = option('n:', 'new:')) {
  createProject($name);
} else if ($name = option('m:', 'model:')) {
  createModel($name);
} else if ($name = option('c:', 'controller:')) {
  createController($name);
} else {
  help();
}

echo PHP_EOL;
