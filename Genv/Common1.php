<?php
/**
 * API方法,Genv命名空间
 *
 * @category Genv
 * @package Common
 * @copyright Copyright (c) 2008 Genv team (http://www.Genv.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * Genv公用方法
 *
 * @category Genv
 * @package Common
 * @copyright Copyright (c) 2008 Genv team (http://www.Genv.org)
 * @license GNU General Public License 2.0
 */
class Genv_Common
{
    /** 程序版本 */
    const VERSION = '0.8/10.8.15';

    /**
     * 缓存的包含路径
     *
     * @access private
     * @var array
     */
    private static $_cachedIncludePath = false;

    /**
     * 锁定的代码块
     *
     * @access private
     * @var array
     */
    private static $_lockedBlocks = array('<p></p>' => '');
    
    /**
     * 允许的标签
     * 
     * @access private
     * @var array
     */
    private static $_allowableTags = '';
    
    /**
     * 允许的属性
     * 
     * @access private
     * @var array
     */
    private static $_allowableAttributes = array();

    /**
     * 默认编码
     *
     * @access public
     * @var string
     */
    public static $charset = 'UTF-8';

    /**
     * 异常处理类
     *
     * @access public
     * @var string
     */
    public static $exceptionHandle;

    /**
     * 锁定标签回调函数
     *
     * @access private
     * @param array $matches 匹配的值
     * @return string
     */
    public static function __lockHTML(array $matches)
    {
        $guid = '<code>' . uniqid(time()) . '</code>';
        self::$_lockedBlocks[$guid] = $matches[0];
        return $guid;
    }

    /**
     * 将url中的非法xss去掉时的数组回调过滤函数
     *
     * @access private
     * @param string $string 需要过滤的字符串
     * @return string
     */
    public static function __removeUrlXss($string)
    {
        $string = str_replace(array('%0d', '%0a'), '', strip_tags($string));
        return preg_replace(array(
            "/\(\s*(\"|')/i",           //函数开头
            "/(\"|')\s*\)/i",           //函数结尾
        ), '', $string);
    }

    /**
     * 检查是否为安全路径
     *
     * @access public
     * @param string $path 检查是否为安全路径
     * @return boolean
     */
    public static function __safePath($path)
    {
        $safePath = rtrim(__Genv_ROOT_DIR__, '/');
        return 0 === strpos($path, $safePath);
    }
    
    /**
     * html标签过滤
     * 
     * @access public
     * @param string $tag 标签
     * @param string $attrs 属性
     * @return string
     */
    public static function __tagFilter($tag, $attrs)
    {

        $suffix = '';
        $tag = strtolower($tag);
        
        if (false === strpos(self::$_allowableTags, "|{$tag}|")) {
            return '';
        }
        
        if (!empty($attrs)) {
            $result = self::__parseAtttrs($attrs);
            $attrs = '';
            
            foreach ($result as $name => $val) {
                $quote = '';
                $lname = strtolower($name);
                $lval = self::__attrTrim($val, $quote);

                if (in_array($lname, self::$_allowableAttributes[$tag])) {
                    $attrs .= ' ' . $name . (empty($val) ? '' : '=' . $val);
                }
            }
        }
        
        return "<{$tag}{$attrs}>";
    }

    /**
     * 自闭合标签过滤
     * 
     * @access public
     * @param array $matches 匹配值
     * @return string
     */
    public static function __closeTagFilter($matches)
    {
        $tag = strtolower($matches[1]);
        return false === strpos(self::$_allowableTags, "|{$tag}|") ? '' : "</{$tag}>";
    }
    
    /**
     * 解析属性
     * 
     * @access public
     * @param string $attrs 属性字符串
     * @return array
     */
    public static function __parseAtttrs($attrs)
    {
        $attrs = trim($attrs);
        $len = strlen($attrs);
        $pos = -1;
        $result = array();
        $quote = '';
        $key = '';
        $value = '';
        
        for ($i = 0; $i < $len; $i ++) {
            if ('=' != $attrs[$i] && !ctype_space($attrs[$i]) && -1 == $pos) {
                $key .= $attrs[$i];
                
                /** 最后一个 */
                if ($i == $len - 1) {
                    if ('' != ($key = trim($key))) {
                        $result[$key] = '';
                        $key = '';
                        $value = '';
                    }
                }
                
            } else if (ctype_space($attrs[$i]) && -1 == $pos) {
                $pos = -2;
            } else if ('=' == $attrs[$i] && 0 > $pos) {
                $pos = 0;
            } else if (('"' == $attrs[$i] || "'" == $attrs[$i]) && 0 == $pos) {
                $quote = $attrs[$i];
                $value .= $attrs[$i];
                $pos = 1;
            } else if ($quote != $attrs[$i] && 1 == $pos) {
                $value .= $attrs[$i];
            } else if ($quote == $attrs[$i] && 1 == $pos) {
                $pos = -1;
                $value .= $attrs[$i];
                $result[trim($key)] = $value;
                $key = '';
                $value = '';
            } else if ('=' != $attrs[$i] && !ctype_space($attrs[$i]) && -2 == $pos) {
                if ('' != ($key = trim($key))) {
                    $result[$key] = '';
                }
                
                $key = '';
                $value = '';
                $pos = -1;
                $key .= $attrs[$i];
            }
        }
        
        return $result;
    }

    /**
     * 清除属性空格
     * 
     * @access public
     * @param string $attr 属性
     * @param string $quote 引号
     * @return string
     */
    public static function __attrTrim($attr, &$quote)
    {
        $attr = trim($attr);
        $attr_len = strlen($attr);
        $quote = '';
        
        if ($attr_len >= 2 &&
            ('"' == $attr[0] || "'" == $attr[0]) 
            && $attr[0] == $attr[$attr_len - 1]) {
            $quote = $attr[0];
            return trim(substr($attr, 1, -1));
        }
        
        return $attr;
    }

   

    /**
     * 递归去掉数组反斜线
     *
     * @access public
     * @param mixed $value
     * @return mixed
     */
    public static function stripslashesDeep($value)
    {
        return is_array($value) ? array_map(array('Genv_Common', 'stripslashesDeep'), $value) : stripslashes($value);
    }

    /**
     * 抽取多维数组的某个元素,组成一个新数组,使这个数组变成一个扁平数组
     * 使用方法:
     * <code>
     * <?php
     * $fruit = array(array('apple' => 2, 'banana' => 3), array('apple' => 10, 'banana' => 12));
     * $banana = Genv_Common::arrayFlatten($fruit, 'banana');
     * print_r($banana);
     * //outputs: array(0 => 3, 1 => 12);
     * ? >
     * </code>
     *
     * @access public
     * @param array $value 被处理的数组
     * @param string $key 需要抽取的键值
     * @return array
     */
    public static function arrayFlatten(array $value, $key)
    {
        $result = array();

        if ($value) {
            foreach ($value as $inval) {
                if (is_array($inval) && isset($inval[$key])) {
                    $result[] = $inval[$key];
                } else {
                    break;
                }
            }
        }

        return $result;
    }

   

    /**
     * 根据count数目来输出字符
     * <code>
     * echo splitByCount(20, 10, 20, 30, 40, 50);
     * </code>
     *
     * @access public
     * @return string
     */
    public static function splitByCount($count)
    {
        $sizes = func_get_args();
        array_shift($sizes);

        foreach ($sizes as $size) {
            if ($count < $size) {
                return $size;
            }
        }

        return 0;
    }

    /**
     * 自闭合html修复函数
     * 使用方法:
     * <code>
     * $input = '这是一段被截断的html文本<a href="#"';
     * echo Genv_Common::fixHtml($input);
     * //output: 这是一段被截断的html文本
     * </code>
     *
     * @access public
     * @param string $string 需要修复处理的字符串
     * @return string
     */
    public static function fixHtml($string)
    {
        //关闭自闭合标签
        $startPos = strrpos($string, "<");

        if (false == $startPos) {
            return $string;
        }

        $trimString = substr($string, $startPos);

        if (false === strpos($trimString, ">")) {
            $string = substr($string, 0, $startPos);
        }

        //非自闭合html标签列表
        preg_match_all("/<([_0-9a-zA-Z-\:]+)\s*([^>]*)>/is", $string, $startTags);
        preg_match_all("/<\/([_0-9a-zA-Z-\:]+)>/is", $string, $closeTags);

        if (!empty($startTags[1]) && is_array($startTags[1])) {
            krsort($startTags[1]);
            $closeTagsIsArray = is_array($closeTags[1]);
            foreach ($startTags[1] as $key => $tag) {
                $attrLength = strlen($startTags[2][$key]);
                if ($attrLength > 0 && "/" == trim($startTags[2][$key][$attrLength - 1])) {
                    continue;
                }
                if (!empty($closeTags[1]) && $closeTagsIsArray) {
                    if (false !== ($index = array_search($tag, $closeTags[1]))) {
                        unset($closeTags[1][$index]);
                        continue;
                    }
                }
                $string .= "</{$tag}>";
            }
        }

        return preg_replace("/\<br\s*\/\>\s*\<\/p\>/is", '</p>', $string);
    }

    /**
     * 去掉字符串中的html标签
     * 使用方法:
     * <code>
     * $input = '<a href="http://test/test.php" title="example">hello</a>';
     * $output = Genv_Common::stripTags($input, <a href="">);
     * echo $output;
     * //display: '<a href="http://test/test.php">hello</a>'
     * </code>
     *
     * @access public
     * @param string $string 需要处理的字符串
     * @param string $allowableTags 需要忽略的html标签
     * @return string
     */
    public static function stripTags($html, $allowableTags = NULL)
    {
        if (!empty($allowableTags) && preg_match_all("/\<([a-z]+)([^>]*)\>/is", $allowableTags, $tags)) {
            self::$_allowableTags = '|' . implode('|', $tags[1]) . '|';

            if (in_array('code', $tags[1])) {
                $html = self::lockHTML($html);
            }

            $normalizeTags = '<' . implode('><', $tags[1]) . '>';
            $html = strip_tags($html, $normalizeTags);
            $attributes = array_map('trim', $tags[2]);

            $allowableAttributes = array();
            foreach ($attributes as $key => $val) {
                $allowableAttributes[$tags[1][$key]] = array_keys(self::__parseAtttrs($val));
            }
            
            self::$_allowableAttributes = $allowableAttributes;

            $len = strlen($html);
            $tag = '';
            $attrs = '';
            $pos = -1;
            $quote = '';
            $start = 0;
            
            for ($i = 0;  $i < $len; $i ++) {
                if ('<' == $html[$i] && -1 == $pos) {
                    $start = $i;
                    $pos = 0;
                } else if (0 == $pos && '/' == $html[$i] && empty($tag)) {
                    $pos = -1;
                } else if (0 == $pos && ctype_alpha($html[$i])) {
                    $tag .= $html[$i];
                } else if (0 == $pos && ctype_space($html[$i])) {
                    $pos = 1;
                } else if (1 == $pos && (!empty($quote) || '>' != $html[$i])) {
                    if (empty($quote) && ('"' == $html[$i] || "'" == $html[$i])) {
                        $quote = $html[$i];
                    } else if (!empty($quote) && $quote == $html[$i]) {
                        $quote = '';
                    }
                
                    $attrs .= $html[$i];
                } else if (-1 != $pos && empty($quote) && '>' == $html[$i]) {
                    $out = self::__tagFilter($tag, $attrs);
                    $outLen = strlen($out);
                    $nextStart = $start + $outLen;
                    
                    $tag = '';
                    $attrs = '';
                    $html = substr_replace($html, $out, $start, $i - $start + 1);
                    $len  = strlen($html);
                    $i = $nextStart - 1;
                    
                    $pos = -1;
                }
            }
            
            $html = preg_replace_callback("/<\/([_0-9a-z-]+)>/is", array('Genv_Common', '__closeTagFilter'), $html);
             $html = self::releaseHTML($html);
        } else {
            $html = strip_tags($html);
        }
        
        //去掉注释
        return preg_replace("/<\!\-\-[^>]*\-\->/s", '', $html);
    }

    /**
     * 过滤用于搜索的字符串
     *
     * @access public
     * @param string $query 搜索字符串
     * @return string
     */
    public static function filterSearchQuery($query)
    {
        return str_replace(array('%', '?', '*', '/', '{', '}'), '', $query);
    }

   

    /**
     * 宽字符串截字函数
     *
     * @access public
     * @param string $str 需要截取的字符串
     * @param integer $start 开始截取的位置
     * @param integer $length 需要截取的长度
     * @param string $trim 截取后的截断标示符
     * @return string
     */
    public static function subStr($str, $start, $length, $trim = "...")
    {
        if (function_exists('mb_get_info')) {
            $iLength = mb_strlen($str, self::$charset);
            $str = mb_substr($str, $start, $length, self::$charset);
            return ($length < $iLength - $start) ? $str . $trim : $str;
        } else {
            preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/", $str, $info);
            $str = join("", array_slice($info[0], $start, $length));
            return ($length < (sizeof($info[0]) - $start)) ? $str . $trim : $str;
        }
    }

    /**
     * 获取宽字符串长度函数
     *
     * @access public
     * @param string $str 需要获取长度的字符串
     * @return integer
     */
    public static function strLen($str)
    {
        if (function_exists('mb_get_info')) {
            return mb_strlen($str, self::$charset);
        } else {
            preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/", $str, $info);
            return sizeof($info[0]);
        }
    }

    /**
     * 生成缩略名
     *
     * @access public
     * @param string $str 需要生成缩略名的字符串
     * @param string $default 默认的缩略名
     * @param integer $maxLength 缩略名最大长度
     * @return string
     */
    public static function slugName($str, $default = NULL, $maxLength = 200)
    {
        $str = str_replace(array("'", ":", "\\", "/", '"'), "", $str);
        $str = str_replace(array("+", ",", ' ', '，', ' ', ".", "?", "=", "&", "!", "<", ">", "(", ")", "[", "]", "{", "}"), "-", $str);
        $str = trim($str, '-');
        $str = empty($str) ? $default : $str;

        return function_exists('mb_get_info') ? mb_strimwidth($str, 0, 128, '', self::$charset) : substr($str, 0, $maxLength);
    }

    /**
     * 去掉html中的分段
     *
     * @access public
     * @param string $html 输入串
     * @return string
     */
    public static function removeParagraph($html)
    {
        /** 锁定标签 */
        $html = self::lockHTML($html);
        $html = str_replace(array("\r", "\n"), '', $html);
    
        $html = trim(preg_replace(
        array("/\s*<p>(.*?)<\/p>\s*/is", "/\s*<br\s*\/>\s*/is",
        "/\s*<(div|blockquote|pre|code|script|table|fieldset|ol|ul|dl|h[1-6])([^>]*)>/is",
        "/<\/(div|blockquote|pre|code|script|table|fieldset|ol|ul|dl|h[1-6])>\s*/is", "/\s*<\!--more-->\s*/is"),
        array("\n\\1\n", "\n", "\n\n<\\1\\2>", "</\\1>\n\n", "\n\n<!--more-->\n\n"),
        $html));
        
        return trim(self::releaseHTML($html));
    }
    
    /**
     * 锁定标签
     * 
     * @access public
     * @param string $html 输入串
     * @return string
     */
    public static function lockHTML($html)
    {
        return preg_replace_callback("/<(code|pre|script)[^>]*>.*?<\/\\1>/is", array('Genv_Common', '__lockHTML'), $html);
    }
    
    /**
     * 释放标签
     * 
     * @access public
     * @param string $html 输入串
     * @return string
     */
    public static function releaseHTML($html)
    {
        $html = trim(str_replace(array_keys(self::$_lockedBlocks), array_values(self::$_lockedBlocks), $html));
        self::$_lockedBlocks = array('<p></p>' => '');
        return $html;
    }
    
    /**
     * 文本分段函数
     *
     * @param string $string 需要分段的字符串
     * @return string
     */
    public static function cutParagraph($string){
        /*static $loaded;
        if (!$loaded) {
            require_once 'Genv/Common/Paragraph.php';
            $loaded = true;
        }*/
        
        return Genv_Common_Paragraph::process($string);
    }

    /**
     * 生成随机字符串
     *
     * @access public
     * @param integer $length 字符串长度
     * @param string $specialChars 是否有特殊字符
     * @return string
     */
    public static function randString($length, $specialChars = false)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        if ($specialChars) {
            $chars .= '!@#$%^&*()';
        }

        $result = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[rand(0, $max)];
        }
        return $result;
    }

    /**
     * 对字符串进行hash加密
     *
     * @access public
     * @param string $string 需要hash的字符串
     * @param string $salt 扰码
     * @return string
     */
    public static function hash($string, $salt = NULL)
    {
        /** 生成随机字符串 */
        $salt = empty($salt) ? self::randString(9) : $salt;
        $length = strlen($string);
        $hash = '';
        $last = ord($string[$length - 1]);
        $pos = 0;

        /** 判断扰码长度 */
        if (strlen($salt) != 9) {
            /** 如果不是9直接返回 */
            return;
        }

        while ($pos < $length) {
            $asc = ord($string[$pos]);
            $last = ($last * ord($salt[($last % $asc) % 9]) + $asc) % 95 + 32;
            $hash .= chr($last);
            $pos ++;
        }

        return '$T$' . $salt . md5($hash);
    }

    /**
     * 判断hash值是否相等
     *
     * @access public
     * @param string $from 源字符串
     * @param string $to 目标字符串
     * @return boolean
     */
    public static function hashValidate($from, $to)
    {
        if ('$T$' == substr($to, 0, 3)) {
            $salt = substr($to, 3, 9);
            return self::hash($from, $salt) == $to;
        } else {
            return md5($from) == $to;
        }
    }

    /**
     * 将路径转化为链接
     *
     * @access public
     * @param string $path 路径
     * @param string $prefix 前缀
     * @return string
     */
    public static function url($path, $prefix)
    {
        $path = (0 === strpos($path, './')) ? substr($path, 2) : $path;
        return rtrim($prefix, '/') . '/' . str_replace('//', '/', ltrim($path, '/'));
    }

}
