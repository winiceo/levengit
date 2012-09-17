<?php

//autho zheli;
class Genv_View extends Genv_Base{
  
    protected $_Genv_View = array(
        'template_path' => array(),
        'helper_class'  => array(),
        'escape'        => array(),
    );   
  	 
	var $tpldir;
	var $objdir;

	var $tplfile;
	var $objfile;
	var $langfile;

	var $vars=array();
	var $force = 0;

	var $var_regexp = "\@?\\\$[a-zA-Z_]\w*(?:\[[\w\.\"\'\[\]\$]+\])*";
	var $vtag_regexp = "\<\?=(\@?\\\$[a-zA-Z_]\w*(?:\[[\w\.\"\'\[\]\$]+\])*)\?\>";
	var $const_regexp = "\{([\w]+)\}";

	var $languages = array();
	var $sid;

	protected $_response;
    protected $_format = null; 
	protected $_format_type = array(
        null        => 'text/html',
        'atom'      => 'application/atom+xml',
        'css'       => 'text/css',
        'htm'       => 'text/html',
        'html'      => 'text/html',
        'js'        => 'text/javascript',
        'json'      => 'application/json',
        'pdf'       => 'application/pdf',
        'ps'        => 'application/postscript',
        'rdf'       => 'application/rdf+xml',
        'rss'       => 'application/rss+xml',
        'rss2'      => 'application/rss+xml',
        'rtf'       => 'application/rtf',
        'text'      => 'text/plain',
        'txt'       => 'text/plain',
        'xhtml'     => 'application/xhtml+xml',
        'xml'       => 'application/xml',
     );
    protected $_charset = 'utf-8';
   
	protected function _postConstruct(){
        parent::_postConstruct();
		$this->_response = Genv_Registry::get('response');
		ob_start();	
	    if (version_compare(PHP_VERSION, '5') == -1) {
			register_shutdown_function(array(&$this, '__destruct'));
		}

		if(function_exists('_preView')){
			$this->assign(_preView());
		}
		
 	}

	protected function _setContentType(){
 	 
        if ($this->_response->getHeader('Content-Type')) {
            return;
        }    
      
        $format = $this->_format;        
        
        if (! empty($this->_format_type[$format])) {           
            
            $val = $this->_format_type[$format];            
           
            if ($this->_charset) {
                $val .= '; charset=' . $this->_charset;
            }            
          
            $this->_response->setHeader('Content-Type', $val);
        }
    }


	public function get($key){	
		 
		 if(empty($key)) { return $this->vars;}	
		 return isset($this->vars[$key]) ? $this->vars[$key] : NULL;
		 
	}
  
	public function assign($spec, $var = null){  	   
        if (is_array($spec)) {
            foreach ($spec as $key => $val) {               
				$this->vars[$key]=$val;
            }
            return true;
        }       
        if (is_object($spec) && $spec instanceof Genv_View) {
            foreach (get_object_vars($spec) as $key => $val) {
                if ($key[0] != "_") {                   
					$this->vars[$key]=$val;
                }
            }
            return true;
        }
        
       
        if (is_object($spec)) {
            foreach (get_object_vars($spec) as $key => $val) {
                $this->$key = $val;
				$this->vars[$key]=$val;
            }
            return true;
        }
         
        if (is_string($spec)) {         
			
			$this->vars[$spec]=$var;			
            return true;
        }       
     
        return false;
    }

    public function fetch($tplfile){    
        ob_start();
		
	    extract($this->vars, EXTR_SKIP);		 
		include $this->gettpl($tplfile); 
		$this->_response->content =ob_get_contents();
		ob_end_clean();
		return $this->_response->content;
       
    }
	//执行某一个模板;
	function eval_php($template){
		$content = file_get_contents($this->gettpl($template));
		extract($this->vars, EXTR_SKIP);
        ob_start();		 
		eval("?>$content<?php ");		 
		$output = ob_get_contents();
		ob_end_clean();
		return $output;		 
    }

	function display($file=null) {
		$file=VF($file); 
		extract($this->vars, EXTR_SKIP);
		$this->_setContentType();		
		$this->fetch($file);		 
		echo  $this->_response->content;


	}
	 
	function gettpl($file) {
		 
		$stack = Genv::factory('Genv_Path_Stack');
        $stack->add(tpldir);  
		C("VEXT",".html");
		$filehtml = $stack->findReal($file.C("VEXT"));
		
		$this->tplfile = $filehtml;
		$this->objfile =tplcachedir.'/'.$file.'.php';
	   // dump($this->objfile);
		if($filehtml === FALSE) {
			//$this->tplfile = tpldir.'/no.html';
		}
		if(!ISDEBUG || !file_exists($this->objfile) || @filemtime($this->objfile) < filemtime($this->tplfile)) {
			/*if(empty($this->language)) {
				@include $this->langfile;
				if(is_array($languages)) {
					$this->languages += $languages;
				}
			}*/
			$template = file_get_contents($this->tplfile);
			$this->complie($template);
		}
		return $this->objfile;
	}
   /*loop最多支持3层*/
	function complie($template) {
		
		 $template = preg_replace('/\{literal}(.*?){\/literal}/eis',"\$this->parseLiteral('\\1')",$template);
	 
		$template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);
		$template = preg_replace("/\{lang\s+(\w+?)\}/ise", "\$this->lang('\\1')", $template);

		$template = preg_replace("/\{($this->var_regexp)\}/", "<?=\\1?>", $template);

		
		$template = preg_replace("/\{($this->const_regexp)\}/", "<?=\\1?>", $template);
		 
		$template = preg_replace("/(?<!\<\?\=|\\\\)$this->var_regexp/", "<?=\\0?>", $template);
 
		$template = preg_replace("/\<\?=(\@?\\\$[a-zA-Z_]\w*)((\[[\\$\[\]\w]+\])+)\?\>/ies", "\$this->arrayindex('\\1', '\\2')", $template);

		$template = preg_replace("/\{\{eval (.*?)\}\}/ies", "\$this->stripvtag('<? \\1?>')", $template);
		$template = preg_replace("/\{eval (.*?)\}/ies", "\$this->stripvtag('<? \\1?>')", $template);
		$template = preg_replace("/\{for (.*?)\}/ies", "\$this->stripvtag('<? for(\\1) {?>')", $template);

		$template = preg_replace("/\{elseif\s+(.+?)\}/ies", "\$this->stripvtag('<? } elseif(\\1) { ?>')", $template);

		for($i=0; $i<4; $i++) {
			$template = preg_replace("/\{loop\s+$this->vtag_regexp\s+$this->vtag_regexp\s+$this->vtag_regexp\}(.+?)\{\/loop\}/ies", "\$this->loopsection('\\1', '\\2', '\\3', '\\4')", $template);
			$template = preg_replace("/\{loop\s+$this->vtag_regexp\s+$this->vtag_regexp\}(.+?)\{\/loop\}/ies", "\$this->loopsection('\\1', '', '\\2', '\\3')", $template);
		}
		$template = preg_replace("/\{if\s+(.+?)\}/ies", "\$this->stripvtag('<? if(\\1) { ?>')", $template);

		$template = preg_replace("/\{template\s+(\w+?)\}/is", "<? include \$this->gettpl('\\1');?>", $template);
		$template = preg_replace("/\{template\s+(.+?)\}/ise", "\$this->stripvtag('<? include \$this->gettpl(\\1); ?>')", $template);


		$template = preg_replace("/\{else\}/is", "<? } else { ?>", $template);
		$template = preg_replace("/\{\/if\}/is", "<? } ?>", $template);
		$template = preg_replace("/\{\/for\}/is", "<? } ?>", $template);

		$template = preg_replace("/$this->const_regexp/", "<?=\\1?>", $template);
        
		$template = "<? if(!defined('IN_GENV')) exit('Access Denied');?>\r\n$template";
		$template = preg_replace("/(\\\$[a-zA-Z_]\w+\[)([a-zA-Z_]\w+)\]/i", "\\1'\\2']", $template);

		 
		$template = preg_replace('/<!--###literal(\d)###-->/eis',"\$this->restoreLiteral('\\1')",$template);

       // $template=$this->restoreLiteral($template);

	    
		//$template=preg_replace("/{([^\}\{\n]*)}/e", "\$this->get_val('\\1');", $template);
		 
		$fp = fopen($this->objfile, 'w');
		fwrite($fp, $template);
		fclose($fp);
	}
	 function get_val($val)
    {

		 dump($val);
        if (strrpos($val, '[') !== false)
        {
            $val = preg_replace("/\[([^\[\]]*)\]/eis", "'.'.str_replace('$','\$','\\1')", $val);
        }

        if (strrpos($val, '|') !== false)
        {
            $moddb = explode('|', $val);
            $val = array_shift($moddb);
        }

        if (empty($val))
        {
            return '';
        }

        if (strpos($val, '.$') !== false)
        {
            $all = explode('.$', $val);

            foreach ($all AS $key => $val)
            {
                $all[$key] = $key == 0 ? $this->make_var($val) : '['. $this->make_var($val) . ']';
            }
            $p = implode('', $all);
        }
        else
        {
            $p = $this->make_var($val);
        }
 

        return $p;
    }
	 /**
     * 处理去掉$的字符串
     *
     * @access  public
     * @param   string     $val
     *
     * @return  bool
     */
    function make_var($val)
    {
        if (strrpos($val, '.') === false)
        {
            if (isset($this->_vars[$val]) && isset($this->_patchstack[$val]))
            {
                $val = $this->_patchstack[$val];
            }
            $p = '$this->_var[\'' . $val . '\']';
        }
        else
        {
            $t = explode('.', $val);
            $_var_name = array_shift($t);
            if (isset($this->_vars[$_var_name]) && isset($this->_patchstack[$_var_name]))
            {
                $_var_name = $this->_patchstack[$_var_name];
            }
            
                $p = '$this->_var[\'' . $_var_name . '\']';
            
            foreach ($t AS $val)
            {
                $p.= '[\'' . $val . '\']';
            }
        }

        return $p;
    }
	function push_vars($key, $val)
    {
        if (!empty($key))
        {
            array_push($this->_temp_key, "\$this->_vars['$key']='" .$this->_vars[$key] . "';");
        }
        if (!empty($val))
        {
            array_push($this->_temp_val, "\$this->_vars['$val']='" .$this->_vars[$val] ."';");
        }
    }

    /**
     * 弹出临时数组的最后一个
     *
     * @return  void
     */
    function pop_vars()
    {
        $key = array_pop($this->_temp_key);
        $val = array_pop($this->_temp_val);

        if (!empty($key))
        {
            eval($key);
        }
    }

 /**
     +----------------------------------------------------------
     * 替换页面中的literal标签
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $content  模板内容
     +----------------------------------------------------------
     * @return string|false
     +----------------------------------------------------------
     */
    function parseLiteral($content) {

		 
        if(trim($content)=='') {
            return '';
        }
        $content = stripslashes($content);
        static $_literal = array();
        $i  =   count($_literal);
        $_literal[$i] = $content;
        $parseStr   =   "<!--###literal{$i}###-->";
        $_SESSION["literal{$i}"]    =   $content;
        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * 还原被替换的literal标签
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $tag  literal标签序号
     +----------------------------------------------------------
     * @return string|false
     +----------------------------------------------------------
     */
    function restoreLiteral($tag) {
        // 还原literal标签
        $parseStr   =   $_SESSION['literal'.$tag];
        // 销毁literal记录
        unset($_SESSION['literal'.$tag]);
        return $parseStr;
    }

	function arrayindex($name, $items) {
		$items = preg_replace("/\[([a-zA-Z_]\w*)\]/is", "['\\1']", $items);
		return "<?=$name$items?>";
	}

	function stripvtag($s) {
		return preg_replace("/$this->vtag_regexp/is", "\\1", str_replace("\\\"", '"', $s));
	}

	function loopsection($arr, $k, $v, $statement) {
		$arr = $this->stripvtag($arr);
		$k = $this->stripvtag($k);
		$v = $this->stripvtag($v);
		$statement = str_replace("\\\"", '"', $statement);
		return $k ? "<? foreach((array)$arr as $k => $v) {?>$statement<?}?>" : "<? foreach((array)$arr as $v) {?>$statement<? } ?>";
	}

	function lang($k) {
		return !empty($this->languages[$k]) ? $this->languages[$k] : "{ $k }";
	}

	function _transsid($url, $tag = '', $wml = 0) {
		$sid = $this->sid;
		$tag = stripslashes($tag);
		if(!$tag || (!preg_match("/^(http:\/\/|mailto:|#|javascript)/i", $url) && !strpos($url, 'sid='))) {
			if($pos = strpos($url, '#')) {
				$urlret = substr($url, $pos);
				$url = substr($url, 0, $pos);
			} else {
				$urlret = '';
			}
			$url .= (strpos($url, '?') ? ($wml ? '&amp;' : '&') : '?').'sid='.$sid.$urlret;
		}
		return $tag.$url;
	}

	function __destruct() {
		return ;
		if($_COOKIE['sid']) {
			return;
		}
		$sid = rawurlencode($this->sid);
		$searcharray = array(
			"/\<a(\s*[^\>]+\s*)href\=([\"|\']?)([^\"\'\s]+)/ies",
			"/(\<form.+?\>)/is"
		);
		$replacearray = array(
			"\$this->_transsid('\\3','<a\\1href=\\2')",
			"\\1\n<input type=\"hidden\" name=\"sid\" value=\"".rawurldecode(rawurldecode(rawurldecode($sid)))."\" />"
		);
		$content = preg_replace($searcharray, $replacearray, ob_get_contents());
		ob_end_clean();
		echo $content;
	}
}
?>