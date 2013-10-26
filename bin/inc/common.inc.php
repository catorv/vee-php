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


function option($name, $longname = null) {
  $options = getopt($name, $longname ? array($longname) : array());
  
  if (!$longname) {
    $longname = $name;
  }
  
  $name = trim($name, ':');
  $longname = trim($longname, ':');
  
  if (isset($options[$name]) || isset($options[$longname])) {
    return $options[isset($options[$name]) ? $name : $longname];
  }
  
  return null;
}

function help() {
  $name = basename($_SERVER['argv'][0]);
  echo <<<EOF
usage: $name [-h] [-c name] [-m name] [-n name]

VEE-PHP - a lightweight, simple, flexible, fast PHP MVC framework

optional arguments:
  -h, --help            show this help message and exit
  -n name, --new name   create a new project
  -m name, --model name
                        create a model
  -c name, --controller name
                        create a controller

EOF;
  exit();
}

function relpath($dir, $name = '', $sep = DIRECTORY_SEPARATOR) {
  if ($name) {
    $htdocs = getcwd() . $sep . $name . $sep . 'htdocs';
  } else {
    $htdocs = getcwd();
  }
  $cwdParts = explode($sep, trim($htdocs, $sep));
  $dirParts = explode($sep, trim($dir, $sep));
  if ($dirParts) {
    $count = 0;
    while (isset($cwdParts[$count]) && isset($dirParts[$count]) 
           && $cwdParts[$count] == $dirParts[$count]) {
      $count++;
    }
    $dir = str_repeat('..' . $sep, count($cwdParts) - $count)
         . implode($sep, array_slice($dirParts, $count));
    if ($dir[0] != $sep && substr($dir, 0, 3) != '..' . $sep) {
      $dir = './' . $dir;
    }
  }
  return $dir;
}

function template($path, $name, $vars = null) {
  $tpl = file_get_contents(PATH_VEE_BIN . 'templates/' . $name);
  
  if ($vars) {
    $names = array();
    $values = array();
    foreach ($vars as $key => $value) {
      $names[]  = '{_' . $key . '_}';
      $values[] = $value;
    }
    
    $tpl = str_replace($names, $values, $tpl);
  }
  
  $names = array('(_id_)', '(_name_)', '(_username_)', '(_organization_name_)',
                 '(_year_)', '(_date_)', '(_package_name_)', '(_class_name_)',
                 '(_doc_comment_)');
  $values = array('{_id_}', '{_name_}', '{_username_}', '{_organization_name_}',
                 '{_year_}', '{_date_}', '{_package_name_}', '{_class_name_}',
                 '{_doc_comment_}');
  $tpl = str_replace($names, $values, $tpl);
  
  if (substr($path, -4) === '.php') {
    file_put_contents($path, $tpl);
  } else {
    file_put_contents($path . DIRECTORY_SEPARATOR . $name, $tpl);
  }
}

function username() {
  $filename = tempnam('', 'vee_');
  file_put_contents($filename, '<?php return get_current_user();');
  $username = include($filename);
  unlink($filename);
  
  return $username;
}

function findAppRoot() {
  $dir = getcwd();
  while (true) {
    if (is_file($dir . DIRECTORY_SEPARATOR . VEE_PHP_FILE)) {
      return $dir . DIRECTORY_SEPARATOR;
    }
    $dir = dirname($dir);
    if ($dir == DIRECTORY_SEPARATOR) return '';
  }
}

function getTemplateVars($root) {
  include_once $root . DIRECTORY_SEPARATOR . VEE_PHP_FILE;
  $vars = array(
    "id" => APP_ID,
    "name" => APP_NAME,
    "username" => USERNAME ? USERNAME : username(),
    "organization_name" => ORGANIZATION_NAME,
    "year" => date("Y"),
    "date" => date("Y-m-d H:i:s"),
  );
  
  $tpl = VEE_TPL_DOCCOMMENT;
  
  $names = array();
  $values = array();
  foreach ($vars as $key => $value) {
    $names[]  = '{_' . $key . '_}';
    $values[] = $value;
  }
  
  $tpl = str_replace($names, $values, $tpl);  
  
  $vars['doc_comment'] = $tpl;
  
  return $vars;
}

function fatal($msg) {
  if (PATH_SEPARATOR == ':') { // Mac OS/Unix/Linux
    echo("\033[31m$msg\033[0m\n");
  } else { // windows
    echo("$msg\n");
  }
  exit(1);
}

function confirm($msg) {
  if (PATH_SEPARATOR == ':') { // Mac OS/Unix/Linux
    echo("\033[33m$msg\033[0m");
  } else { // windows
    echo("$msg");
  }
  return fgets(STDIN);
}

function overwrite($filename) {
  $ok = confirm('File ' . relpath($filename) . " exists. Overwrite? [yes/No] ");
  if (strtolower(trim($ok)) != 'yes') {
    fatal("ERROR: File " . relpath($filename) . " exists.");
  }
}