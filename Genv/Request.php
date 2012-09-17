<?php
class Genv_Request extends Genv_Base{
   
    public $env;
    public $get;  
    public $post;  
    public $cookie;   
    public $server;   
    public $files;
    public $http;
    public $argv;
    protected $_is_gap = null; 
    protected $_csrf;  
	  /**
     * 内部参数
     *
     * @access private
     * @var array
     */
    private $_params = array();
	  /**
     * 服务端参数
     *
     * @access private
     * @var array
     */
    private $_server = array();

    /**
     * 客户端ip地址
     *
     * @access private
     * @var string
     */
    private $_ip = NULL;

    /**
     * 客户端字符串
     *
     * @access private
     * @var string
     */
    private $_agent = NULL;

    /**
     * 来源页
     *
     * @access private
     * @var string
     */
    private $_referer = NULL;
 /**
     * 当前过滤器
     *
     * @access private
     * @var array
     */
    private $_filter = array();

    /**
     * 支持的过滤器列表
     *
     * @access private
     * @var string
     */
    private static $_supportFilters = array(
        'int'       =>  'intval',
        'integer'   =>  'intval',
        'search'    =>  array('Genv_Common', 'filterSearchQuery'),
        'xss'       =>  array('Genv_Common', 'removeXSS'),
        'url'       =>  array('Genv_Common', 'safeUrl')
    );
    protected function _postConstruct(){
        parent::_postConstruct();
        $this->reset();
    }
  
    public function get($key = null, $alt = null){
        return $this->_getValue('get', $key, $alt);
    }    
 
    public function post($key = null, $alt = null){
        return $this->_getValue('post', $key, $alt);
    }    
    
    public function cookie($key = null, $alt = null){
        return $this->_getValue('cookie', $key, $alt);
    }    
 
    public function env($key = null, $alt = null){
        return $this->_getValue('env', $key, $alt);
    }
 
    public function server($key = null, $alt = null){
        return $this->_getValue('server', $key, $alt);
    }

    public function files($key = null, $alt = null){
        return $this->_getValue('files', $key, $alt);
    }

    public function argv($key = null, $alt = null){
        return $this->_getValue('argv', $key, $alt);
    }
  
    public function http($key = null, $alt = null){
        if ($key !== null) {
            $key = strtolower($key);
        }
        return $this->_getValue('http', $key, $alt);
    }
    

    public function postAndFiles($key = null, $alt = null){
        $post  = $this->_getValue('post',  $key, false);
        $files = $this->_getValue('files', $key, false);
        
        // no matches in post or files
        if (! $post && ! $files) {
            return $alt;
        }
        
        // match in post, not in files
        if ($post && ! $files) {
            return $post;
        }
        
        // match in files, not in post
        if (! $post && $files) {
            return $files;
        }
        
        // are either or both arrays?
        $post_array  = is_array($post);
        $files_array = is_array($files);
        
        // both are arrays, merge them
        if ($post_array && $files_array) {
            return array_merge($post, $files);
        }
        
        // post array but single files, append to post
        if ($post_array && ! $files_array) {
            array_push($post, $files);
            return $post;
        }
        
        // files array but single post, append to files
        if (! $post_array && $files_array) {
            array_push($files, $post);
            return $files;
        }
        
        // now what?
        throw $this->_exception('ERR_POST_AND_FILES', array(
            'key' => $key,
        ));
    }
    
    
    public function isSsl(){
        return $this->server('HTTPS') == 'on'
            || $this->server('SERVER_PORT') == 443;
    }
    
    /**
     * 
     * Is this a command-line request?
     * 
     * @return bool
     * 
     */
    public function isCli(){
        return PHP_SAPI == 'cli';
    }
    
    /**
     * 
     * Is the current request a cross-site forgery?
     * 
     * @return bool
     * 
     */
    public function isCsrf(){
        if (! $this->_csrf) {
            $this->_csrf = Genv::factory('Genv_Csrf');
        }        
        return $this->_csrf->isForgery();
    }
    
    /**
     * 
     * Is this a GET-after-POST request?
     * 
     * @return bool
     * 
     */
    public function isGap(){
        if ($this->_is_gap === null) {
            $session = Genv::factory('Genv_Session', array(
                'class' => get_class($this),
            ));
            $this->_is_gap = (bool) $session->getFlash('is_gap');
        }
        
        return $this->_is_gap;
    }
    
    /**
     * 
     * Is this a 'GET' request?
     * 
     * @return bool
     * 
     */
    public function isGet(){
        return $this->server('REQUEST_METHOD') == 'GET';
    }
    
    /**
     * 
     * Is this a 'POST' request?
     * 
     * @return bool
     * 
     */
    public function isPost(){
        return $this->server('REQUEST_METHOD') == 'POST';
    }
    
    /**
     * 
     * Is this a 'PUT' request? Supports Google's X-HTTP-Method-Override
     * solution to languages like PHP not fully honoring the HTTP PUT method.
     * 
     * @return bool
     * 
     */
    public function isPut(){
        $is_put      = $this->server('REQUEST_METHOD') == 'PUT';
        
        $is_override = $this->server('REQUEST_METHOD') == 'POST' &&
                       $this->http('X-HTTP-Method-Override') == 'PUT';
        
        return ($is_put || $is_override);
    }
    
    /**
     * 
     * Is this a 'DELETE' request? Supports Google's X-HTTP-Method-Override
     * solution to languages like PHP not fully honoring the HTTP DELETE
     * method.
     * 
     * @return bool
     * 
     */
    public function isDelete(){
        $is_delete   = $this->server('REQUEST_METHOD') == 'DELETE';
        
        $is_override = $this->server('REQUEST_METHOD') == 'POST' &&
                       $this->http('X-HTTP-Method-Override') == 'DELETE';
        
        return ($is_delete || $is_override);
    }
    
    /**
     * 
     * Is this an XmlHttpRequest?
     * 
     * Checks if the `X-Requested-With` HTTP header is `XMLHttpRequest`.
     * Generally used in addition to the [[Genv_Request::isPost() | ]],
     * [[Genv_Request::isGet() | ]], etc. methods to identify Ajax-style 
     * HTTP requests.
     * 
     * @return bool
     * 
     */
    public function isXhr(){
        return strtolower($this->http('X-Requested-With')) == 'xmlhttprequest';
    }
    
    /**
     * 
     * Reloads properties from the superglobal arrays.
     * 
     * Normalizes HTTP header keys, dispels magic quotes.
     * 
     * @return void
     * 
     */
    public function reset(){
        // load the "real" request vars
        $vars = array('env', 'get', 'post', 'cookie', 'server', 'files');
        foreach ($vars as $key) {
            $var = '_' . strtoupper($key);
            if (isset($GLOBALS[$var])) {
                $this->$key = $GLOBALS[$var];
            } else {
                $this->$key = array();
            }
        }
        
        // dispel magic quotes if they are enabled.
        // http://talks.php.net/show/php-best-practices/26
        if (get_magic_quotes_gpc()) {
            $in = array(&$this->get, &$this->post, &$this->cookie);
            while (list($k, $v) = each($in)) {
                foreach ($v as $key => $val) {
                    if (! is_array($val)) {
                        $in[$k][$key] = stripslashes($val);
                        continue;
                    }
                    $in[] =& $in[$k][$key];
                }
            }
            unset($in);
        }
        
        // load the "fake" argv request var
        $this->argv = (array) $this->server('argv');
        
        // load the "fake" http request var
        $this->http = array();
        foreach ($this->server as $key => $val) {
            
            // only retain HTTP headers
            if (substr($key, 0, 5) == 'HTTP_') {
                
                // normalize the header key to lower-case
                $nicekey = strtolower(
                    str_replace('_', '-', substr($key, 5))
                );
                
                // strip control characters from keys and values
                $nicekey = preg_replace('/[\x00-\x1F]/', '', $nicekey);
                $this->http[$nicekey] = preg_replace('/[\x00-\x1F]/', '', $val);
                
                // no control characters wanted in $this->server for these
                $this->server[$key] = $this->http[$nicekey];
                
                // disallow external setting of X-JSON headers.
                if ($nicekey == 'x-json') {
                    unset($this->http[$nicekey]);
                    unset($this->server[$key]);
                }
            }
        }
        
        // rebuild the files array to make it look more like POST
        if ($this->files) {
            $files = $this->files;
            $this->files = array();
            $this->_rebuildFiles($files, $this->files);
        }
    }
    
    /**
     * 
     * Recursive method to rebuild $_FILES structure to be more like $_POST.
     * 
     * @param array $src The source $_FILES array, perhaps from a sub-
     * element of that array/
     * 
     * @param array &$tgt Where we will store the restructured data when we
     * find it.
     * 
     * @return void
     * 
     */
    protected function _rebuildFiles($src, &$tgt)
    {
        // an array with these keys is a "target" for us (pre-sorted)
        $tgtkeys = array('error', 'name', 'size', 'tmp_name', 'type');
        
        // the keys of the source array (sorted so that comparisons work
        // regardless of original order)
        $srckeys = array_keys((array) $src);
        sort($srckeys);
        
        // is the source array a target?
        if ($srckeys == $tgtkeys) {
            // get error, name, size, etc
            foreach ($srckeys as $key) {
                if (is_array($src[$key])) {
                    // multiple file field names for each error, name, size, etc.
                    foreach ((array) $src[$key] as $field => $value) {
                        $tgt[$field][$key] = $value;
                    }
                } else {
                    // the key itself is error, name, size, etc., and the
                    // target is already the file field name
                    $tgt[$key] = $src[$key];
                }
            }
        } else {
            // not a target, create sub-elements and rebuild them too
            foreach ($src as $key => $val) {
                $tgt[$key] = array();
                $this->_rebuildFiles($val, $tgt[$key], $key);
            }
        }
    }
    
    /**
     * 
     * Common method to get a request value and return it.
     * 
     * @param string $var The request variable to fetch from: get, post,
     * etc.
     * 
     * @param string $key The array key, if any, to get the value of.
     * 
     * @param string $alt The alternative default value to return if the
     * requested key does not exist.
     * 
     * @return mixed The requested value, or the alternative default
     * value.
     * 
     */
    protected function _getValue($var, $key, $alt){
        // get the whole property, or just one key?
        if ($key === null) {
            // no key selected, return the whole array
            return $this->$var;
        } elseif (array_key_exists($key, $this->$var)) {
            // found the requested key.
            // need the funny {} becuase $var[$key] will try to find a
            // property named for that element value, not for $var.
            return $this->{$var}[$key];
        } else {
            // requested key does not exist
            return $alt;
        }
    }











 /**
     * 应用过滤器
     *
     * @access private
     * @param mixed $value
     * @return void
     */
    private function _applyFilter($value)
    {
        if ($this->_filter) {
            foreach ($this->_filter as $filter) {
                $value = is_array($value) ? array_map($filter, $value) :
                call_user_func($filter, $value);
            }
        }

        $this->_filter = array();
        return $value;
    }

    /**
     * 设置过滤器
     *
     * @access public
     * @param mixed $filter 过滤器名称
     * @return Genv_Widget_Request
     */
    public function filter()
    {
        $filters = func_get_args();

        foreach ($filters as $filter) {
            $this->_filter[] = is_string($filter) && isset(self::$_supportFilters[$filter])
            ? self::$_supportFilters[$filter] : $filter;
        }

        return $this;
    }

    /**
     * 获取实际传递参数(magic)
     *
     * @access public
     * @param string $key 指定参数
     * @return void
     */
    public function __get($key)
    {
        return $this->getv($key);
    }
/**
     * 获取实际传递参数
     *
     * @access public
     * @param string $key 指定参数
     * @param mixed $default 默认参数 (default: NULL)
     * @return void
     */
    public function getv($key, $default = NULL)
    {
        $value = $default;

        switch (true) {
            case isset($this->_params[$key]):
                $value = $this->_params[$key];
                break;
            case isset($_GET[$key]):
                $value = $_GET[$key];
                break;
            case isset($_POST[$key]):
                $value = $_POST[$key];
                break;
            case isset($_COOKIE[$key]):
                $value = $_COOKIE[$key];
                break;
            default:
                $value = $default;
                break;
        }

        $value = is_array($value) || strlen($value) > 0 ? $value : $default;
        return $this->_filter ? $this->_applyFilter($value) : $value;
    }

    /**
     * 从参数列表指定的值中获取http传递参数
     *
     * @access public
     * @param mixed $parameter 指定的参数
     * @return array
     */
    public function from($params)
    {
        $result = array();
        $args = is_array($params) ? $params : func_get_args();

        foreach ($args as $arg) {
            $result[$arg] = $this->getv($arg);
        }

        return $result;
    }



	/*以下为扩展函数*/
	 /**
     * 设置服务端参数
     *
     * @access public
     * @param string $name 参数名称
     * @param mixed $value 参数值
     * @return void
     */
    public function setServer($name, $value = NULL)
    {
        if (NULL == $value) {
            if (isset($_SERVER[$name])) {
                $value = $_SERVER[$name];
            } else if (isset($_ENV[$name])) {
                $value = $_ENV[$name];
            }
        }

        $this->_server[$name] = $value;
    }

    /**
     * 获取环境变量
     *
     * @access public
     * @param string $name 获取环境变量名
     * @return string
     */
    public function getServer($name)
    {
        if (!isset($this->_server[$name])) {
            $this->setServer($name);
        }

        return $this->_server[$name];
    }

    /**
     * 设置ip地址
     *
     * @access public
     * @param unknown $ip
     * @return unknown
     */
    public function setIp($ip = NULL)
    {
        switch (true) {
            case NULL !== $this->getServer('HTTP_X_FORWARDED_FOR'):
                $this->_ip = $this->getServer('HTTP_X_FORWARDED_FOR');
                return;
            case NULL !== $this->getServer('HTTP_CLIENT_IP'):
                $this->_ip = $this->getServer('HTTP_CLIENT_IP');
                return;
            case NULL !== $this->getServer('REMOTE_ADDR'):
                $this->_ip = $this->getServer('REMOTE_ADDR');
                return;
            default:
                break;
        }

        $this->_ip = 'unknown';
    }

    /**
     * 获取ip地址
     *
     * @access public
     * @return string
     */
    public function getIp()
    {
        if (NULL === $this->_ip) {
            $this->setIp();
        }

        return $this->_ip;
    }

    /**
     * 设置客户端
     *
     * @access public
     * @param string $agent 客户端字符串
     * @return void
     */
    public function setAgent($agent = NULL)
    {
        $this->_agent = (NULL === $agent) ? $this->getServer('HTTP_USER_AGENT') : $agent;
    }

    /**
     * 获取客户端
     *
     * @access public
     * @return void
     */
    public function getAgent()
    {
        if (NULL === $this->_agent) {
            $this->setAgent();
        }

        return $this->_agent;
    }

    /**
     * 设置来源页
     *
     * @access public
     * @param string $referer 客户端字符串
     * @return void
     */
    public function setReferer($referer = NULL)
    {
        $this->_referer = (NULL === $referer) ? $this->getServer('HTTP_REFERER') : $referer;
    }

    /**
     * 获取客户端
     *
     * @access public
     * @return void
     */
    public function getReferer(){
        if (NULL === $this->_referer) {
            $this->setReferer();
        }
        return $this->_referer;
    }
}
?>