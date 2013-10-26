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

function createModel($name) {
  $root = findAppRoot();
  if ($root) {
    $vars = getTemplateVars($root);
    
    $modeldir = $root. 'mvc/models/';
    
    if (($pos = strrpos($name, DIRECTORY_SEPARATOR)) === false) {
      $classname = ucfirst($name);
      $filename = $modeldir . $classname . '.class.php';
      $packagename = 'model';
      $path = $packagename;
    } else {
      $path = substr($name, 0, $pos);
      if (!is_dir($modeldir . $path)) {
        mkdir($modeldir . $path, 0755, true);
      }
      $classname = ucfirst(substr($name, $pos + 1));
      $filename = $modeldir . $path . DIRECTORY_SEPARATOR 
                . $classname . '.class.php';
      $packagename = 'model\\' . strtr($path, DIRECTORY_SEPARATOR, '\\');
      $path = 'model/' . $path;
    }
    
    if (is_file($filename)) overwrite($filename);
    
    $vars['package_name'] = $packagename;
    $vars['class_name'] = $classname;
    
    $names = array('{_package_name_}', '{_class_name_}');
    $values = array($packagename, $classname);
    $vars['doc_comment'] = str_replace($names, $values, $vars['doc_comment']);
    
    template($filename, "Model.class.php", $vars);
    
    echo "File " . relpath($filename) . " is created";
  } else {
    fatal("ERROR: Not a VEE-PHP project (or any of the parent directories).");
  }
}