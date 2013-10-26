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
require_once "common.inc.php";

function createProject($name) {
  $vars = array(
      "dir" => relpath(dirname(PATH_VEE_BIN), $name),
      "id" => 0,
      "name" => $name,
      "username" => username(),
      "year" => date("Y"),
      "date" => date("Y-m-d H:i:s"),
  );
  
  if (is_dir($name)) {
    fatal("Project $name exists.");
  }
  
  mkdir("$name/config", 0755, true);
  
  mkdir("$name/data/cache", 0777, true);
  mkdir("$name/data/upload", 0777, true);
  mkdir("$name/data/tmp", 0777, true);
  mkdir("$name/data/logs", 0777, true);
  
  mkdir("$name/htdocs", 0755, true);
  mkdir("$name/htdocs/upload", 0777, true);
  
  mkdir("$name/mvc/controllers/tools/orm", 0755, true);
  mkdir("$name/mvc/models/entities", 0755, true);
  mkdir("$name/mvc/views", 0755, true);
  
  mkdir("$name/helpers", 0755, true);
  mkdir("$name/language", 0755, true);
  
  template("$name", ".vee.php", $vars);
  
  template("$name/htdocs", "index.php", $vars);
  template("$name/htdocs", ".htaccess");
  
  template("$name/config", "application.cfg.php");
  template("$name/config", "db.cfg.php");
  
  template("$name/mvc/controllers", "Index.do.php");
  template("$name/mvc/views", "Index.tpl.php");
  template("$name/mvc/controllers/tools", "Memcache.do.php");
  template("$name/mvc/controllers/tools", "Memcache.tpl.php");
  template("$name/mvc/controllers/tools/orm", "Mysql.do.php");
  template("$name/mvc/controllers/tools/orm", "Mysql.tpl.php");
  
  echo "Project $name is created.";
}