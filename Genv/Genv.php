<?php
/*
框架核心;
*/
class Genv{
   
    protected static $_Genv = array(
        'ini_set'      => array(),
        'registry_set' => array(),
		'defines'       =>array(),
        'start'        => array(),
        'stop'         => array(),
        'system'       => null,
    );
    
    public static $system = null; 

	protected static $_config = array();   
    
    protected static $_status = false;    
    
    final private function __construct(){}
	//初始化
	public function init(){	
		define('IS_CGI',substr(PHP_SAPI, 0,3)=='cgi' ? 1 : 0 );
		define('IS_WIN',strstr(PHP_OS, 'WIN') ? 1 : 0 );
		define('IS_CLI',PHP_SAPI=='cli'? 1   :   0); 
		if(!IS_CLI) {
			// 当前文件名
			if(!defined('PHP_FILE')) {
				if(IS_CGI) {
					//CGI/FASTCGI模式下
					$_temp  = explode('.php',$_SERVER["PHP_SELF"]);
					define('PHP_FILE',  rtrim(str_replace($_SERVER["HTTP_HOST"],'',$_temp[0].'.php'),'/'));
				}else {
					define('PHP_FILE',    rtrim($_SERVER["SCRIPT_NAME"],'/'));
				}
			}
			if(!defined('WEBURL')) {		 
				// 网站URL根目录
				if( strtoupper(APPNAME) == strtoupper(basename(dirname(PHP_FILE))) ) {
					$_root = dirname(dirname(PHP_FILE));
				}else {
					$_root = dirname(PHP_FILE);
				}
				define('WEBURL',   (($_root=='/' || $_root=='\\')?'':$_root));
			}  
		}


	
	}

	//创建客户端
    public static function client($name){
		Genv::init();

		define('APPPATH',SYSPATH."/".$name);
		define('EXT',".php");		
		define('__ROOT__',WEBURL);
		define('__APPDIR__',WEBURL.'/'.$name);			 
		define('__APP__',PHP_FILE);		
		define('__SELF__',$_SERVER['PHP_SELF']);  
		define('APPPUBLIC',__APPDIR__.'/Public/');
		define('WEB_PUBLIC_URL',__ROOT__.'/Public/');
		
		
		$config = array();	
		$config['Genv']=array(
			"system"=>SYSPATH,
		    "appname"=>$name,		 
			"cachetemp"=>APPPATH."/Temp/~temp.php",
			"cacherun"=>APPPATH."/Temp/~run.php"
		);	

		$list = array(           
				'Function' ,
				'Base',
				'Class',
				'Config'
		); 
		if(ISDEBUG&&!file_exists($config['Genv']['cachetemp'])){
			$fun="php_strip_whitespace";			
			$l=$fun(APPPATH."/Conf/Webconf.php");						
			foreach((array)$list as $name){
					 $l.=$fun(GenvPath . DIRECTORY_SEPARATOR . "$name.php");		
			}			
			file_put_contents( $config['Genv']['cachetemp'], $l);			
		}
		if(ISDEBUG){			
			require_once($config['Genv']['cachetemp']);
		}else{	
			if(!is_dir(APPPATH)) Genv::build_app_dir();	

			include (APPPATH."/Conf/Webconf.php");	
			foreach((array)$list as $name){
					include GenvPath . DIRECTORY_SEPARATOR . "$name.php";		
			}
			
		}		 
		if (! class_exists('Genv_File' , false)) {				 
              require_cache(GenvPath . DIRECTORY_SEPARATOR . "File.php");
        }


 

		

        Genv::$_config=$config;
    }	 
	public static function Def(){
	
	// 标识当前请求是否是 API ， JS 请求，此值将决定如何输出错误信息 等
		define('IS_IN_API_REQUEST',	Q(V_API_REQUEST_ROUTE,	FALSE));
		define('IS_IN_JS_REQUEST',	Q(V_JS_REQUEST_ROUTE,	FALSE));
		
		// 设定时区
		if(function_exists('date_default_timezone_set')) {
			@date_default_timezone_set('Etc/GMT'.(APP_TIMEZONE_OFFSET > 0 ? '-' : '+').(abs(APP_TIMEZONE_OFFSET)));
		} else {
			putenv('Etc/GMT'.(APP_TIMEZONE_OFFSET > 0 ? '-' : '+').(abs(APP_TIMEZONE_OFFSET)));
		}
		// 解释URL,定制URL相关的常量
		$protoV = strtolower(Q('s:HTTPS','off'));
		$host	= Q('s:HTTP_X_FORWARDED_HOST',false)
					? Q('s:HTTP_X_FORWARDED_HOST') 
					: Q("s:HTTP_HOST", Q("s:SERVER_NAME", (Q("s:SERVER_PORT")=='80' ? '' : Q("s:SERVER_PORT"))));
		
		// 协议类型 http https
		define('W_BASE_PROTO',		(empty($protoV) || $protoV == 'off') ? 'http' : 'https'); 
		define('W_BASE_HTTP',		W_BASE_PROTO.'://' . $host);
		define('W_BASE_HOST',		$host);
		// 产品安装路径 如: /xweibo/  W_BASE_URL_PATH 将在安装的时候生成计算 
		define('W_BASE_URL',		defined('W_BASE_URL_PATH') ? rtrim(W_BASE_URL_PATH, '/\\').'/' : '/' );
		$fName	= basename(Q('S:SCRIPT_FILENAME'));
		define('W_BASE_FILENAME',	 $fName ? $fName : 'index.php');
	}

    public static function start($config = array()){
	
        if (Genv::$_status) {return;} 		

		$config=array_merge(Genv::$_config, $config);		 
      
	    if(ISDEBUG&&file_exists($config['Genv']['cacherun'])){			 
			 require_cache($config['Genv']['cacherun']) ;
		}

        spl_autoload_register(array('Genv_Class', 'autoload'));
		 
        if (ini_get('register_globals')) {
			
            Genv::cleanGlobals();
        } 

        Genv_Config::load($config);
		
      //  $c=Genv_Config::get("Genv");
		 
        $arch_config = Genv_Config::get('Genv');

        if (! $arch_config) {
            Genv_Config::set('Genv', null, Genv::$_Genv);
        } else {
            Genv_Config::set('Genv', null, array_merge(
                Genv::$_Genv,
                (array) $arch_config
            ));
        }       
      
        Genv::$system = Genv_Config::get('Genv', 'system');
          
        $settings = Genv_Config::get('Genv', 'ini_set');		

        foreach ($settings as $key => $val) {
            ini_set($key, $val);
        }

		$defines = Genv_Config::get('Genv', 'defines', array());

        foreach ($defines as $key => $val) {			 
            define($key, $val);
        }
        Genv::Def();
        // user-defined registry entries
        $register = Genv_Config::get('Genv', 'registry_set', array());
	 
        foreach ($register as $name => $list) {			 
            $list = array_pad((array) $list, 2, null);			
            list($spec, $config) = $list;
            Genv_Registry::set($name, $spec, $config);
        } 

        $name_class = array(
            'inflect'  => 'Genv_Inflect',
            'locale'   => 'Genv_Locale',
            'rewrite'  => 'Genv_Uri_Rewrite',
            'request'  => 'Genv_Request',
            'response' => 'Genv_Http_Response',
        );       
        // ... but only if not already registered by the user.
        foreach ($name_class as $name => $class) {			
            if (! Genv_Registry::exists($name)) {
                Genv_Registry::set($name, $class);
            }
        } 
       
       $hooks = Genv_Config::get('Genv', 'start', array());
	  
       Genv::callbacks($hooks);      
        
       Genv::$_status = true;
	   
    }
   
    public static function stop(){  
		 
		$cachefile=Genv_Config::get("Genv",'cacherun');
		 
		if(ISDEBUG&&!Genv_File::exists($cachefile)){			

			$file=Genv_Config::get("Temp",'file'); 
			$fun="php_strip_whitespace";			
			$l='';
			foreach((array)$file as $k=>$v){
				$l.=$fun(Genv_File::exists($v));		
			}			
			file_write($cachefile, $l);	
		}
		
		$hooks = Genv_Config::get('Genv', 'stop', array());
        Genv::callbacks($hooks); 
        spl_autoload_unregister(array('Genv_Class', 'autoload'));       
        Genv::$_status = false;

    }
	
   
    public static function callbacks($callbacks){
        foreach ((array) $callbacks as $params) {
           /*
            // include a file as in previous versions of Genv
            if (is_string($params)) {
				 echo 333;

                Genv_File::load($params);
                continue;
            }
		 
            exit;*/
            // $spec is an object instance, class name, or registry key
            settype($params, 'array');
            $spec = array_shift($params);
			  
            if (! is_object($spec)) {
                // not an object, so treat as a class name ...
                $spec = (string) $spec;
                // ... unless it's a registry key.
                if (Genv_Registry::exists($spec)) {
                    $spec = Genv_Registry::get($spec);
                }
            }
            
            // the method to call on $spec
            $func = array_shift($params);
          // dump($params);
            // make the call
            if ($spec) {
                call_user_func_array(array($spec, $func), $params);
            } else {
                call_user_func_array($func, $params);
            }
        }
    }   
	

	public static function factory($class, $config = null){		 
        Genv_Class::autoload($class); 
       // dump($class);
        $obj = new $class($config); 
		 
        if ($obj instanceof Genv_Factory) {           
            return $obj->factory();
        }
		
        return $obj;
    }
    
  
    public static function dependency($class, $spec){
        // is it an object already?
        if (is_object($spec)) {
            return $spec;
        }
        
        // check for registry objects
        if (is_string($spec)) {
            return Genv_Registry::get($spec);
        }
     //   dump($class);
        // not an object, not in registry.
        // try to create an object with $spec as the config
        return Genv::factory($class, $spec);
    }
  
    public static function exception($spec, $code, $text = '',$info = array()) {
        // is the spec an object?
        if (is_object($spec)) {
            // yes, find its class
            $class = get_class($spec);
        } else {
            // no, assume the spec is a class name
            $class = (string) $spec;
        }

		 
        
        // drop 'ERR_' and 'EXCEPTION_' prefixes from the code
        // to get a suffix for the exception class
        $suffix = $code;
        if (strpos($suffix, 'ERR_') === 0) {
            $suffix = substr($suffix, 4);
        } elseif (strpos($suffix, 'EXCEPTION_') === 0) {
            $suffix = substr($suffix, 10);
        }
        
        // convert "STUDLY_CAP_SUFFIX" to "Studly Cap Suffix" ...
        $suffix = ucwords(strtolower(str_replace('_', ' ', $suffix)));
        
        // ... then convert to "StudlyCapSuffix"
        $suffix = str_replace(' ', '', $suffix);
        
        // build config array from params
        $config = array(
            'class' => $class,
            'code'  => $code,
            'text'  => $text,
            'info'  => (array) $info,
        );
        
        // get all parent classes, including the class itself
        $stack = array_reverse(Genv_Class::parents($class, true));
        
        // add the vendor namespace to the stack as a fallback, even though
        // it's not strictly part of the hierarchy, for generic vendor-wide
        // exceptions.
        $vendor = Genv_Class::vendor($class);
        if ($vendor != 'Genv') {
            $stack[] = $vendor;
        }
        
        // add Genv as the final fallback
        $stack[] = 'Genv';
        
		 
        return Genv::factory('Genv_Exception', $config);
    }    
   
    public static function dump($var, $label = null){
        $obj = Genv::factory('Genv_Debug_Var');
        $obj->display($var, $label);
    }    
   
    public static function cleanGlobals(){

        $list = array(
            'GLOBALS',
            '_POST',
            '_GET',
            '_COOKIE',
            '_REQUEST',
            '_SERVER',
            '_ENV',
            '_FILES',
        );
        
        // Create a list of all of the keys from the super-global values.
        // Use array_keys() here to preserve key integrity.
        $keys = array_merge(
            array_keys($_ENV),
            array_keys($_GET),
            array_keys($_POST),
            array_keys($_COOKIE),
            array_keys($_SERVER),
            array_keys($_FILES),
            // $_SESSION is null if you have not started the session yet.
            // This insures that a check is performed regardless.
            isset($_SESSION) && is_array($_SESSION) ? array_keys($_SESSION) : array()
        );
      // dump($keys);
        // Unset the globals.
        foreach ($keys as $key) {
            if (isset($GLOBALS[$key]) && ! in_array($key, $list)) {
                unset($GLOBALS[$key]);
            }
        }
    }

	public static function get_caller() {
		$trace  = array_reverse( debug_backtrace() );
		$caller = array();

		foreach ( $trace as $call ) {
			if ( isset( $call['class'] ) && __CLASS__ == $call['class'] )
				continue; // Filter out wpdb calls.
			$caller[] = isset( $call['class'] ) ? "{$call['class']}->{$call['function']}" : $call['function'];
		}

		return join( '<br> ', $caller );
	}

	// 创建项目目录结构
	function build_app_dir() {
			// 没有创建项目目录的话自动创建
			if(!is_dir(APPPATH)) mkdir(APPPATH,0777);
			if(is_writeable(APPPATH)) {
				$dirs  = array(
					'App',
					'Cache',
					'Conf',
					'Data','Data/Cache','Data/Logs','Data/View',
					'Help',
					'Language',
					'Lib',
					'Public',
					'Temp',
					'View',
					'Com'
					);
				foreach($dirs as $k=>$v){
				 
					 if(!is_dir(APPPATH."/".$v))  mkdir(APPPATH."/".$v,0777);
				}
			
				// 目录安全写入
				if(!defined('BUILD_DIR_SECURE')) define('BUILD_DIR_SECURE',false);
				if(BUILD_DIR_SECURE) {
					if(!defined('DIR_SECURE_FILENAME')) define('DIR_SECURE_FILENAME','index.html');
					if(!defined('DIR_SECURE_CONTENT')) define('DIR_SECURE_CONTENT',' ');
					// 自动写入目录安全文件
					$content = DIR_SECURE_CONTENT;
					$a = explode(',', DIR_SECURE_FILENAME);
					foreach ($a as $filename){
						foreach ($dirs as $dir)
							file_put_contents($dir.$filename,$content);
					}
				}
				
				// 写入配置文件
				if(!is_file(APPPATH.'/Config/Webconf.php'))
					file_put_contents(APPPATH.'/Conf/Webconf.php',"<?php\n\n?>");
				// 写入测试Action
				/*if(!is_file(LIB_PATH.'Action/IndexAction.class.php'))
					build_action();
				*/
			}else{
				header("Content-Type:text/html; charset=utf-8");
				exit('<div style=\'font-weight:bold;float:left;width:345px;text-align:center;border:1px solid silver;background:#E8EFFF;padding:8px;color:red;font-size:14px;font-family:Tahoma\'>项目目录不可写，目录无法自动生成！<BR>请使用项目生成器或者手动生成项目目录~</div>');
			}
		}
	 
}