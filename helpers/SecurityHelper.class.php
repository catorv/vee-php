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
 * 安全辅助器
 * @package vee-php\helpers
 * @author 魏永增 Cator Vee <catorwei@gmail.com>
 */
class SecurityHelper {
    /**
     * 清除XSS攻击脚本（代码参考CodeIgniter架构）
     * @param $str
     * @param $isImage
     * @return fixed 如果 $isImage = true，则返回true|false，否则返回转换后的字符串
     */
    static public function xssClean($str, $isImage = false) {
        if (is_array($str)) {
            foreach ($str as & $val) {
                $val = self::xssClean($val, $isImage);
            }
            return $str;
        }

        /* Remove Invisible Characters */
        $str = preg_replace(array(
                '/%0[0-8bcef]/',        // url encoded 00-08, 11, 12, 14, 15
                '/%1[0-9a-f]/',         // url encoded 16-31
                '/[\x00-\x08]/',        // 00-08
                '/\x0b/', '/\x0c/',     // 11, 12
                '/[\x0e-\x1f]/'         // 14-31
                ), '', $str);

        /* Protect GET variables in URLs */
        $str = preg_replace('/\&([a-z\_0-9]+)\=([a-z\_0-9]+)/i',
                            'CATOR-LOVE-U-FOREVER-489237623\1=\2', $str);

        /*
         * Validate standard character entities
         *
         * Add a semicolon if missing.  We do this to enable
         * the conversion of entities to ASCII later.
         *
         */
        $str = preg_replace('/(&\#?[0-9a-z]{2,})([\x00-\x20])*;?/i',
                            '\1;\2', $str);

        /*
         * Validate UTF16 two byte encoding (x00)
         *
         * Just as above, adds a semicolon if missing.
         *
         */
        $str = preg_replace('/(&\#x?)([0-9A-F]+);?/i', '\1\2;', $str);

        /* Un-Protect GET variables in URLs */
        $str = str_replace('CATOR-LOVE-U-FOREVER-489237623', '&', $str);

        /*
         * URL Decode
         *
         * Just in case stuff like this is submitted:
         *
         * <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
         *
         * Note: Use rawurldecode() so it does not remove plus signs
         *
         */
        $str = rawurldecode($str);

        /*
         * Convert character entities to ASCII
         *
         * This permits our tests below to work reliably.
         * We only convert entities that are within tags since
         * these are the ones that will pose security problems.
         *
         */
        $str = preg_replace_callback("/[a-z]+=([\'\"]).*?\\1/si",
                                     array(__CLASS__, '_convertAttribute'),
                                     $str);

        $str = preg_replace_callback("/<\w+.*?(?=>|<|$)/si",
                                     array(__CLASS__, '_htmlEntityDecode'),
                                     $str);

        /* Remove Invisible Characters Again! */
        $str = preg_replace(array(
                '/%0[0-8bcef]/',        // url encoded 00-08, 11, 12, 14, 15
                '/%1[0-9a-f]/',         // url encoded 16-31
                '/[\x00-\x08]/',        // 00-08
                '/\x0b/', '/\x0c/',     // 11, 12
                '/[\x0e-\x1f]/'         // 14-31
                ), '', $str);

        /*
         * Convert all tabs to spaces
         *
         * This prevents strings like this: ja   vascript
         * NOTE: we deal with spaces between characters later.
         * NOTE: preg_replace was found to be amazingly slow here on large blocks of data,
         * so we use str_replace.
         *
         */

        if (strpos($str, "\t") !== false) {
            $str = str_replace("\t", ' ', $str);
        }

        /*
         * Capture converted string for later comparison
         */
        $convertedString = $str;

        /*
        * Makes PHP tags safe
        *
        *  Note: XML tags are inadvertently replaced too:
        *
        *   <?xml
        *
        * But it doesn't seem to pose a problem.
        *
        */
        if ($isImage === TRUE) {
            // Images have a tendency to have the PHP short opening and closing tags every so often
            // so we skip those and only do the long opening tags.
            $str = str_replace(array('<?php', '<?PHP'),
                               array('&lt;?php', '&lt;?PHP'),
                               $str);
        } else {
            $str = str_replace(array('<?php', '<?PHP', '<?', '?'.'>'),
                               array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'),
                               $str);
        }

        /*
         * Compact any exploded words
         *
         * This corrects words like:  j a v a s c r i p t
         * These words are compacted back to their correct state.
         *
         */
        $words = array('javascript', 'expression', 'vbscript', 'script',
                       'applet', 'alert', 'document', 'write', 'cookie',
                       'window');
        foreach ($words as & $word) {
            $temp = '';
            for ($i = 0, $wordlen = strlen($word); $i < $wordlen; $i++) {
                $temp .= substr($word, $i, 1)."\s*";
            }

            // We only want to do this when it is followed by a non-word character
            // That way valid stuff like "dealer to" does not become "dealerto"
            $str = preg_replace_callback('#('.substr($temp, 0, -3).')(\W)#is',
                                      array(__CLASS__, '_compactExplodedWords'),
                                      $str);
        }

        /*
         * Remove disallowed Javascript in links or img tags
         * We used to do some version comparisons and use of stripos for PHP5,
         * but it is dog slow compared to these simplified non-capturing
         * preg_match(), especially if the pattern exists in the string
         */
        do {
            $original = $str;

            if (preg_match("/<a/i", $str)) {
                $str = preg_replace_callback("#<a\s+([^>]*?)(>|$)#si",
                                             array(__CLASS__, '_jsLinkRemoval'),
                                             $str);
            }

            if (preg_match("/<img/i", $str)) {
                $str = preg_replace_callback("#<img\s+([^>]*?)(\s?/?>|$)#si",
                                             array(__CLASS__, '_jsImgRemoval'),
                                             $str);
            }

            if (preg_match("/script/i", $str) OR preg_match("/xss/i", $str)) {
                $str = preg_replace("#<(/*)(script|xss)(.*?)\>#si",
                                    '[removed]',
                                    $str);
            }
        } while($original != $str);

        unset($original);

        /*
         * Remove JavaScript Event Handlers
         *
         * Note: This code is a little blunt.  It removes
         * the event handler and anything up to the closing >,
         * but it's unlikely to be a problem.
         *
         */
        $eventHandlers = array('[^a-z_\-]on\w*', 'xmlns');

        if ($isImage === true) {
            /*
             * Adobe Photoshop puts XML metadata into JFIF images, including namespacing,
             * so we have to allow this for images. -Paul
             */
            unset($eventHandlers[array_search('xmlns', $eventHandlers)]);
        }

        $str = preg_replace("#<([^><]+?)(" . implode('|', $eventHandlers)
                                           . ")(\s*=\s*[^><]*)([><]*)#i",
                            '<\1\4',
                            $str);

        /*
         * Sanitize naughty HTML elements
         *
         * If a tag containing any of the words in the list
         * below is found, the tag gets converted to entities.
         *
         * So this: <blink>
         * Becomes: &lt;blink&gt;
         *
         */
        $naughty = 'alert|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|isindex|layer|link|meta|object|plaintext|style|script|textarea|title|video|xml|xss';
        $str = preg_replace_callback('#<(/*\s*)(' . $naughty
                                                  . ')([^><]*)([><]*)#is',
                                     array(__CLASS__, '_sanitizeNaughtyHtml'),
                                     $str);

        /*
         * Sanitize naughty scripting elements
         *
         * Similar to above, only instead of looking for
         * tags it looks for PHP and JavaScript commands
         * that are disallowed.  Rather than removing the
         * code, it simply converts the parenthesis to entities
         * rendering the code un-executable.
         *
         * For example:  eval('some code')
         * Becomes:      eval&#40;'some code'&#41;
         *
         */
        $str = preg_replace('#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2&#40;\\3&#41;", $str);

        /*
         *  Images are Handled in a Special Way
         *  - Essentially, we want to know that after all of the character conversion is done whether
         *  any unwanted, likely XSS, code was found.  If not, we return TRUE, as the image is clean.
         *  However, if the string post-conversion does not matched the string post-removal of XSS,
         *  then it fails, as there was unwanted XSS code found and removed/changed during processing.
         */

        if ($isImage === true) {
            return ($str == $convertedString); // true | false
        }

        return $str;
    }

    /**
     * Attribute Conversion
     *
     * Used as a callback for XSS Clean
     *
     * @param    array
     * @return   string
     */
    static private function _convertAttribute($match) {
        return str_replace(array('>', '<', '\\'),
                           array('&gt;', '&lt;', '\\\\'),
                           $match[0]);
    }

    // --------------------------------------------------------------------

    /**
     * HTML Entity Decode Callback
     *
     * Used as a callback for XSS Clean
     *
     * @param    array $match
     * @return   string
     */
    static private function _htmlEntityDecode($match) {
        $str = & $match[0];
        if (stristr($str, '&') === false) {
            return $str;
        }

        // The reason we are not using html_entity_decode() by itself is because
        // while it is not technically correct to leave out the semicolon
        // at the end of an entity most browsers will still interpret the entity
        // correctly.  html_entity_decode() does not convert entities without
        // semicolons, so we are left with our own little solution here. Bummer.

        if (function_exists('html_entity_decode')
                && (strtolower($charset) != 'utf-8'
                    OR version_compare(phpversion(), '5.0.0', '>='))) {
            $str = html_entity_decode($str,
                                      ENT_COMPAT,
                                      Config::$response['charset']);
            $str = preg_replace('~&#x(0*[0-9a-f]{2,5})~ei',
                                'chr(hexdec("\\1"))',
                                $str);
            return preg_replace('~&#([0-9]{2,4})~e', 'chr(\\1)', $str);
        }

        // Numeric Entities
        $str = preg_replace('~&#x(0*[0-9a-f]{2,5});{0,1}~ei',
                            'chr(hexdec("\\1"))',
                            $str);
        $str = preg_replace('~&#([0-9]{2,4});{0,1}~e', 'chr(\\1)', $str);

        // Literal Entities - Slightly slow so we do another check
        if (stristr($str, '&') === FALSE) {
            $str = strtr($str, array_flip(get_html_translation_table(HTML_ENTITIES)));
        }

        return $str;
    }

    /**
     * Compact Exploded Words
     *
     * Callback function for xss_clean() to remove whitespace from
     * things like j a v a s c r i p t
     *
     * @param    array $matches
     * @return   string
     */
    static private function _compactExplodedWords($matches) {
        return preg_replace('/\s+/s', '', $matches[1]) . $matches[2];
    }

    /**
     * JS Link Removal
     *
     * Callback function for xss_clean() to sanitize links
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on link-heavy strings
     *
     * @param    array $match
     * @return   string
     */
    static private function _jsLinkRemoval($match) {
        $attributes = self::_filterAttributes(str_replace(array('<', '>'),
                                                          '',
                                                          $match[1]));
        return str_replace($match[1], preg_replace("#href=.*?(alert\(|alert&\#40;|javascript\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si", "", $attributes), $match[0]);
    }

    /**
     * JS Image Removal
     *
     * Callback function for xss_clean() to sanitize image tags
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on image tag heavy strings
     *
     * @param    array $match
     * @return   string
     */
    static private function _jsImgRemoval($match) {
        $attributes = $this->_filterAttributes(str_replace(array('<', '>'), '', $match[1]));
        return str_replace($match[1], preg_replace("#src=.*?(alert\(|alert&\#40;|javascript\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si", "", $attributes), $match[0]);
    }

    /**
     * Filter Attributes
     *
     * Filters tag attributes for consistency and safety
     *
     * @param    string $str
     * @return   string
     */
    static private function _filterAttributes($str) {
        $out = '';

        if (preg_match_all('#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches)) {
            foreach ($matches[0] as $match) {
                $out .= preg_replace("#/\*.*?\*/#s", '', $match);
            }
        }

        return $out;
    }

    /**
     * Sanitize Naughty HTML
     *
     * Callback function for xss_clean() to remove naughty HTML elements
     *
     * @param    array $matches
     * @return   string
     */
    static private function _sanitizeNaughtyHtml($matches) {
        // encode opening brace
        $str = '&lt;' . $matches[1] . $matches[2] . $matches[3];

        // encode captured opening or closing brace to prevent recursive vectors
        $str .= str_replace(array('>', '<'), array('&gt;', '&lt;'), $matches[4]);

        return $str;
    }
}