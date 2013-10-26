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
 * 路径处理辅助类
 * @package vee-php\helpers
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class PathHelper {
    static public function relpath($dir, $rel, $sep = DIRECTORY_SEPARATOR) {
        $relParts = explode($sep, trim($rel, $sep));
        $dirParts = explode($sep, trim($dir, $sep));
        if ($dirParts) {
            $count = 0;
            while (isset($relParts[$count]) && isset($dirParts[$count]) 
                   && $relParts[$count] == $dirParts[$count]) {
              $count++;
            }
            return str_repeat('..' . $sep, count($relParts) - $count)
                 . implode($sep, array_slice($dirParts, $count));
        }
        return $dir;
    }
}
