<?php
/**
  +----------------------------------------------------------
 * A函数用于实例化Action
  +----------------------------------------------------------
 * @param string name Action名称
 * @param string app Model所在项目
  +----------------------------------------------------------
 * @return Action
  +----------------------------------------------------------
 */
function A($name, $app='@') {
    static $_action = array();
	static $_stack;

	if($_stack===null){	
		//初始化;
		$_stack=Genv::factory('Genv_Class_Stack');
		$_stack->add(Genv_Config::get('Genv_Action','classes'));
	}

    if (isset($_action[$app . $name]))
        return $_action[$app . $name];
    $OriClassName = $name;

	//如果不存在，则加载;
	
	if (strpos($name, '.')) {
			$array = explode('.', $name);
			$name = array_pop($array);
			$className = $name . 'Action';
			$_stack->load($name, false);		   
	} else {
			$className = $name . 'Action';
			$_stack->load($name, false);			
	}
	

    if (class_exists($className)) {
        $action = new $className();
        $_action[$app . $OriClassName] = $action;
        return $action;
    } else {
        return false;
    }
}
/**/
function B(){


}

// 设置 配置文件;
function C($key=NULL, $value=NULL){
	static $_config = array();
	//如果是数组,写入配置数组,以全字母大写的形式返回;
	if(is_array($key)){
		return $_config = array_merge($_config, array_change_key_case($key,CASE_UPPER));
	} 
	$key = strtoupper($key);	
	if(!is_null($value)) { 	return $_config[$key] = $value;}
	if(empty($key)) { return $_config;}	
	return isset($_config[$key]) ? $_config[$key] : NULL;
}



function E($message, $level = 'Error') {
		
		//参数分析
		if (empty($message)) {
			return false;
		}								
		
		//调试模式下优雅输出错误信息
		$trace 			= debug_backtrace();
		$source_file 	= $trace[0]['file'] . '(' . $trace[0]['line'] . ')';
			
		$trace_string 	= '';
		foreach ($trace as $key=>$t) {

			 
			$trace_string .= '#'. $key . ' ' . $t['file'] . '('. $t['line'] . ')' . $t['class'] . $t['type'] . $t['function'] . '(' . implode('.',  $t['args']) . ')<br/>';			
		}
			
		//加载,分析,并输出excepiton文件内容
		include_once GenvPath . 'Html/exception.php';		
		
		/*if (DOIT_DEBUG === false) {			
			//写入程序运行日志
			Log::write($message, $level);				
		}*/
		
		//终止程序
		exit();
}
	
//设置视图;

function V($k=null,$v=null){
	static $view;
	$args = func_get_args();
	if($view===null){		 
		$view=	Genv::factory('Genv_View');
	}
	if (is_array($k)){
		$args  = func_get_args();		
		foreach ($args as $arg){
			foreach ($arg as $key => $value){
				$view->assign($key, $value);
			}
		}
	}else{
		$view->assign($k, $v);
	}
	
	return $view;

}
//自动定位模板文件;
function VF($templateFile){

        C("VEXT","");
		$templateFile=strtolower($templateFile);
		 //echo $templateFile;
        if(''==$templateFile) {
            // 如果模板文件名为空 按照默认规则定位
				$templateFile = strtolower(G('APP').'.'.G('ACT').C('VEXT'));
        }elseif(strpos($templateFile,'@')===0){	
			//一般为引入本模块下其它模板为;例 @add;

			 
			 $templateFile =strtolower(G('APP')).'.'.str_replace(array('@'),'',$templateFile).C('VEXT');			 
		}elseif(strpos($templateFile,'/')===0){
			// 引入其它模块的操作模板			 
            $templateFile   =str_replace('/','.',$templateFile).C('VEXT');
        }elseif(!is_file($templateFile))    {
            // 按正常路径解析;
            $templateFile =str_replace('/','.',$templateFile).C('VEXT');
        }		 
		 
		return $templateFile;	     
	 
}

//db 库
function D($table=null,$model='Dev'){	 
    static $_model = array();
	
    if(empty($table)) {
         return Genv::factory('Genv_Model',array('table'=>'','model'=>$model));//new Genv_Model('',$model);
    }   
    if(isset($_model[$model][$table])) {
        return $_model[$model][$table];
    } 
	 
	return $_model[$model][$table]=   Genv::factory('Genv_Model',array('table'=>$table,'model'=>$model));//new Genv_Model('',$model); new Genv_Model($table,$model);     
} 

function M(){


}


//----------------------------------------------------------------------
/// 数据交互组件的快捷访问方法 调用函数 Genv_Dmg::call，自动处理错误，无错误时，直接返回组件结果
function DS() {
	$p = func_get_args();
	array_unshift($p, true);
	return call_user_func_array(array('Genv_Dmg','call'), $p);
}

/// 数据交互组件的快捷访问方法 调用函数 Genv_Dmg::call， 返回标准返回值结构，可自行处理错误
function DR() {
	$p = func_get_args();
	array_unshift($p, false);
	return call_user_func_array(array('Genv_Dmg','call'), $p);
}
/**
 * 删除 $dsRoute 相关的缓存
 * @param $dsRoute  	数据组件路由
 * $return 无
 */
function DD($dsRoute){
	F(COM_CACHE_KEY_PRE.$dsRoute,null);
	FG(COM_CACHE_KEY_PRE.$dsRoute,null);
	G(COM_CACHE_KEY_PRE.$dsRoute,null);
}

function RST($rst, $errno=0, $err='', $level=0, $log=''){
	return array('rst'=>$rst, 'errno'=>$errno*1, 'err'=>$err, 'level'=>$level, 'log'=>$log);
}
 //
function F($name=null,$value=''){	 
	
    static $_cache = array();
	$cache = Genv::factory('Genv_Cache');
	 
	if(empty($name)|| is_null($name) ){
	    //try{
			 
		  $cache->deleteAll();//清空缓存；	
		//}catch(Exception $e){};
		return ;
	}
	
	if(is_array($name)){
	     foreach($key as $k=>$v){
			$cache->save($k,$v);		 
		 }	
	}
	if(isset($_cache[$name])){ // 静态缓存
		
		return $_cache[$name];
	}
	if($value !==''){
		 
		if(is_null($value)){ // 删除缓存
			//
			$cache->delete($name);	
			
		}else{
			$cache->save($name,$value);			
		}		
	}else{	
		 
		$data=$cache->fetch($name);
		return $data;
	}
	//return $data;      

} 
//缓存组;
function FG($gName, $id=null, $value=null, $ttl = 0){
	    $gKey = GROUP_CACHE_KEY_PRE.' '.trim($gName);
		//如果没有id删除此缓存;
		if(!$id){
			return F($gKey,null);
		}
		$vKey = $gKey.' '.trim($id);
		$gVer = F($gKey);
		//如果有值则赋值;
	    if($value){			
			if (!$gVer){
				$gVer =APP_LOCAL_TIMESTAMP.'_'.rand(1000000,9999999);
				//echo "SET GKEY: $gKey = $gVer \n";
				F($gKey , $gVer, 0);
			}
			$gData = array('v'=>$value, 'ver'=>$gVer);
			return F($vKey, $gData, $ttl);
		}
		//如果没有值则获取;
		if(!$value){		 
			if($gVer){
				$gData = F($vKey);
				if (is_array($gData) && $gData['ver']==$gVer){
					return 	$gData['v'];
				}else{
					//echo "CACHE : [$gName, $id] expired\n";
				}
			}
			F($vKey,null);
			return false;
		}
}

// 通用快速文件缓存
function FC($name=null, $value='', $expire=-1, $p='/Data/Cache/'){
	// $value  '':读取 null:清空 data:赋值
	static $_cache = array();

	$path =Genv_Config::get('Genv','appdir').$p;
	if( is_null($name) ){
		I("@.Lib.Dir"); 		 
        Dir::del($path);
		//$cache->deleteAll();//清空缓存；	
	}
	$file = $path.$name.'.php'; 
 
	if($value !== ''){		
		if(is_null($value)){ // 删除缓存
		 
			$result = @unlink($file);
			if($_cache[$name]){
				unset($_cache[$name]);
			}
			$result = null;			
		} else{ // 缓存数据	
			$_cache[$name] = $value;
			$value = addslashes(serialize($value));				
			$content = "<?php\n!defined('IN_GENV') && die();\n//".sprintf('%012d',$expire)."\nreturn '$value';\n?>";
			$result  = file_write($file,$content);
			 
		}
		 
		return $result;
	}	
	if(isset($_cache[$name])){ // 静态缓存
		return $_cache[$name];
	}
	if(is_file($file) && false !== $content = file_get_contents($file)){
		 
		$expire = substr($content, 38,12);	
	 
		// 缓存过期,删除文件
		if($expire != -1 && time()>filemtime($file)+$expire){ 
			 @unlink($file);
			return false;
		}
		$value =unserialize(stripslashes(require $file));
		 
		$_cache[$name] = $value;
		return $value;
	} else{
		return false;
	}
}

function I($class,$return=false){	 
    if (isset($GLOBALS['included_files'][$class])){
        return true;
	}else{
        $GLOBALS['included_files'][$class] = true;
    }
    $classStrut = explode(".", $class);	
	
    if ("@" == $classStrut[0]){
        $class = str_replace("@", APPPATH, $class);		 
        $file =  str_replace(".", "/", $class) . EXT;
    }else if ("X" == $classStrut[0]){
        $class = str_replace("X", X, $class);		 
        $file =  str_replace(".", "/", $class) . EXT;
		//echo $file;
    }else if ("Help" == $classStrut[0]){		 
        $class = str_replace("Help", APPPATH."/Help", $class);		 
        $file =  str_replace(".", "/", $class) . ".func.php";		 
    }else if ("D" == array_shift($classStrut)){
        $class = implode("/", $classStrut);
        $file = DT .  ucfirst($class) . EXT;
    }else{ 		
        $file =   str_replace(".", "/", $class) . EXT;
	} 		
    if (!is_readable($file)){		 
		 die($file.'没找到');
        return false;
    }
	 
   
	if($return){	 
		return require_cache($file);  
	}else{
		require_cache($file);  
		return true;
	}
    //return ;
} 


/*全局变量设定和获取*/

function G($key=null, $val = null){
	$key = strtoupper($key);
    $vkey = $key ? strtokey("{$key}", '$GLOBALS[\'MDL_\']') : '$GLOBALS[\'MDL_\']';	 
    if ($val === null) {
        /* 返回该指定环境变量 */
        $v = eval('return ' . $vkey . ';');
        return $v;
    }else{
        /* 设置指定环境变量 */
        eval($vkey . ' = $val;');
        return $val;
    }
}


	//------------------------------------------------------------------
/**
 * V($vRoute,$def_v=NULL);
 * APP:V($vRoute,$def_v=NULL);
 * 获取还原后的  $_GET ，$_POST , $_FILES $_COOKIE $_REQUEST $_SERVER $_ENV
 * 同名全局函数： V($vRoute,$def_v=NULL);
 * @param $vRoute	变量路由，规则为：“<第一个字母>[：变量索引/[变量索引]]
 * 					例:	V('G:TEST/BB'); 表示获取 $_GET['TEST']['BB']
 * 						V('p'); 		表示获取 $_POST
 * 						V('c:var_name');表示获取 $_COOKIE['var_name']
 * @param $def_v
 * @param $setVar	是否设置一个变量
 * @return unknown_type
 */

  
function Q($vRoute,$def_v=NULL,$setVar=false){
		static $v;
		if (empty($v)){$v = array();}
		$vRoute = trim($vRoute);

		//强制初始化值
		if ($setVar) {$v[$vRoute] = $def_v;return true;}

		if (!isset($v[$vRoute])){
			$vKey = array('C'=>$_COOKIE,'G'=>$_GET,		'P'=>$_POST,'R'=>$_REQUEST,
						  'F'=>$_FILES,	'S'=>$_SERVER,	'E'=>$_ENV,
						  '-'=>$GLOBALS[V_CFG_GLOBAL_NAME]
			);
			if (empty($vKey['R'])) {
				$vKey['R'] = array_merge($_COOKIE, $_GET, $_POST);
			}
			if ( !preg_match("#^([cgprfse-])(?::(.+))?\$#sim",$vRoute,$m) || !isset($vKey[strtoupper($m[1])]) ){
				trigger_error("Can't parse var from vRoute: $vRoute ", E_USER_ERROR);
				return NULL;
			}

			//----------------------------------------------------------
			$m[1] = strtoupper($m[1]);
			$tv = $vKey[$m[1]];
			
			//----------------------------------------------------------
			if ( empty($m[2]) ) {
				$v[$vRoute] =  ($m[1]=='-' || $m[1]=='F' || $m[1]=='S' || $m[1]=='E' ) ? $tv :_magic_var($tv);
			}elseif ( empty($tv) ) {
				return  $def_v;
			}else{ 
				$vr = explode('/',$m[2]);
				while( count($vr)>0 ){
					$vk = array_shift($vr);
					if (!isset($tv[$vk])){
						return $def_v;
						break;
					}  
					$tv = $tv[$vk];
				}
			}
			$v[$vRoute] = ($m[1]=='-' || $m[1]=='F' || $m[1]=='S' || $m[1]=='E'  )  ? $tv : _magic_var($tv);
		}
		return $v[$vRoute];
	}

/**
	 * 根据用户服务器环境配置，递归还原变量
	 * @param $mixed
	 * @return 还原后的值
*/
function _magic_var($mixed) {
	if( (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) || @ini_get('magic_quotes_sybase') ) {
		if(is_array($mixed))
			return array_map('_magic_var', $mixed);
		return stripslashes($mixed);
	}else{
		return $mixed;
	}
}


//获得当前表名;
function gettable($a){
 $d= D($a);
 return $d->_prefix.$a; 

}
 
// 获取语言定义
function L($key=NULL, $value=NULL){
	static $_lang = array();
	//如果是数组,写入配置数组,以全字母大写的形式返回;
	if(is_array($key)){
		return $_lang = array_merge($_lang, $key);
	} 
	//array_change_key_case($key,CASE_UPPER)
	//$key = strtoupper($key);
	//$key = strtoupper($key);
	if(!is_null($value)) { return $_lang[$key] = $value;}
	if(empty($key)) { return $_lang;}
	return isset($_lang[$key]) ? $_lang[$key] : 0;
}



//加载应用的语言包
function LL($file){
    //语言包位置;	 
	require_once(APPPATH."/Language/".$file.".php");	 
	L($LANG);
	unset($LANG);
}

function U($url='',$params=array(),$redirect=false,$suffix=true) {
	//$params=array_merge($params,array('menuid'=>getgpc('menuid','G')));
	if(empty($params['mid'])){
	    //  $params['mid']=getgpc('mid','G');
	}
	 
	$appname=Genv_Config::get("Genv","appname");
	if($url===''){
	    $url=G('APP')."/".G('ACT');
	
	}
    if(0===strpos($url,'/')) {
        $url   =  substr($url,1);
    }
    if(!strpos($url,'://')) {// 没有指定项目名 使用当前项目名
        $url   = $appname.'://'.$url;
    }
    if(stripos($url,'@?')) { // 给路由传递参数
        $url   =  str_replace('@?','@think?',$url);
    }elseif(stripos($url,'@')) { // 没有参数的路由
        $url   =  $url.G('APP');
    }
	
	 
    // 分析URL地址
    $array   =  parse_url($url);
 
	 
	  
    $app      =  isset($array['scheme'])?   $array['scheme']  :$appname;
    $route    =  isset($array['user'])?$array['user']:'';
    if(isset($array['path'])) {
        $action  =  substr($array['path'],1);
        if(!isset($array['host'])) {
            // 没有指定模块名
            $module = G('APP');
        }else{// 指定模块
            $module = $array['host'];
        }
    }else{ // 只指定操作
        $module =  G('APP');
        $action   =  $array['host'];
    }
	//echo $module."<br>";

    if(isset($array['query'])) {
        parse_str($array['query'],$query);
        $params = array_merge($query,$params);
    }
    if(URL_DISPATCH_ON && URL_MODEL>0) {
        $depr = URL_PATHINFO_MODEL==2?URL_PATHINFO_DEPR:'/';
        $str    =   $depr;
        foreach ($params as $var=>$val)
            $str .= $var.$depr.$val.$depr;
        $str = substr($str,0,-1);
        //$group   = isset($group)?$group.$depr:'';
        if(!empty($route)) {
            $url    =   str_replace($appname,$app,__APP__).'/'.$group.$route.$str;
        }else{
            $url    =   str_replace($appname,$app,__APP__).'/'.$module.$depr.$action.$str;
        }
        if($suffix && URL_HTML_SUFFIX)
            $url .= URL_HTML_SUFFIX;

		 

		   $url=str_replace(startfile().'/','',$url);
    }else{
		 
        $params =   http_build_query($params);
		 //echo APP_NAME;
		 //echo $app;
        $url    =   str_replace($appname,$app,__APP__).'/'.$module.'/'.$action.'/?'.$params;

        //$url    = getstartfile().'?'.C('VAR_APP').'='.$module.'&'.C('VAR_ACT').'='.$action.'&'.$params;
    }
	// echo $url."<br>";
	//dump(G());
	//$url=str_replace(G('HOMEPAGE').'/','',$url).C('HTML_URL_SUFFIX');
    //$url .= C('HTML_URL_SUFFIX');

    if($redirect) {		
		 
        redirect($url);
    }else{
        return $url;
    }
}



//------------------------------------------------------------------
	/**
	 * H($fRoute);
	 * 执行 $fRoute 指定的函数 第二个以及以后的参数 将传递给此函数
	 * 例：H('test.func',1,2); 表示执行  func(1,2);
	 * @param $fRoute 函数路由，规则与模块规则一样
	 * @return 函数执行结果
	 */
function H($fRoute){
		static $_fTree = array();		
		$p = func_get_args();
		array_shift($p);
		
		if(isset($_fTree[$fRoute])){
			return call_user_func_array($_fTree[$fRoute],$p);
		}
		
		I("Help.$fRoute");//加载函数;
		
		$pp = preg_match("#^([a-z_][a-z0-9_\./]*/|)([a-z0-9_]+)(?:\.([a-z_][a-z0-9_]*))?\$#sim",$fRoute,$m);
		if (!$pp) { trigger_error("fRoute : [ $fRoute  ] is  invalid ", E_USER_ERROR);  return false;}
		$_fTree[$fRoute] = empty($m[3])?$m[2]:$m[3];
		if ( !function_exists($_fTree[$fRoute]) ) {
			trigger_error("Can't find function [ {$_fTree[$fRoute]} ] in file [ $cFile ]", E_USER_ERROR);
		}		
		return call_user_func_array($_fTree[$fRoute],$p);
}

//记录日志;
function Z($t='db',$str, $level='info', $extra_params=array()){
	 $config = array(
		 'adapter' => 'Genv_Log_Adapter_File',
		  'events'  => '*',
		 'file'    => APPPATH.'/Data/Logs'.date("/Y_W.").$t.".php",
	 );
	 $log = Genv::factory('Genv_Log', $config);
	 $log->save($t,$level,$str);
}

//------------------------------------------------------------------
	/**
	 * APP::redirect($mRoute,$type=1);
	 * 重定向 并退出程序
	 * @param $mRoute
	 * @param $type 	1 : 默认 ， 内部模块跳转 ,2 : 给定模块路由，通过浏览器跳转 ,3 : 给定URL  ,4 : 给定URL，用JS跳
	 * @return 无返回值
	 */
function redirect($url,$type=1){		
		switch ($type){
			case 1:				 
				header("Location: ".$url);
				break;			
			case 2:				
				echo '<script>window.location.href="'.addslashes($url).'";</script>';
				break;
			default:
				break;
		}
		exit;
}

function redirect22($location, $exit=true, $code=302, $headerBefore=NULL, $headerAfter=NULL){
        if($headerBefore!=NULL){
            for($i=0;$i<sizeof($headerBefore);$i++){
                header($headerBefore[$i]);
            }
        }
        header("Location: $location", true, $code);
        if($headerAfter!=NULL){
            for($i=0;$i<sizeof($headerBefore);$i++){
                header($headerBefore[$i]);
            }
        }
        if($exit)
            exit;
}

function escape($str){

	$res = @unpack("H*",iconv("utf-8","UCS-2",$str));
	if (!eregi("WIN",PHP_OS)){
		preg_match_all("/(.{4})/is", $res[1],$res);
	   $ret='';
	   foreach($res[0] as $key=>$v){

		$tmpString=substr($v,2,2).substr($v,0,2);
	   $ret.="%u".$tmpString;
	   }
	   
	}else{
	$ret = preg_replace("/(.{4})/is","%u\\1",$res[1]);
	}
	return $ret;
}
  
function unescape($str) { 
         $str = rawurldecode($str); 
         preg_match_all("/%u.{4}|&#x.{4};|&#\d+;|.+/U",$str,$r); 
         $ar = $r[0]; 
         foreach($ar as $k=>$v) { 
                  if(substr($v,0,2) == "%u") 
 $ar[$k] =!eregi("WIN",PHP_OS)?iconv("UCS-2","utf8",strrev(pack("H4",substr($v,-4)))):iconv("UCS-2","gb2312",pack("H4",substr($v,-4))); 
                  elseif(substr($v,0,3) == "&#x") 
                           $ar[$k] = iconv("UCS-2","utf8",pack("H4",substr($v,3,-1))); 
                  elseif(substr($v,0,2) == "&#") { 
                           $ar[$k] = iconv("UCS-2","utf8",pack("n",substr($v,2,-1))); 
                  } 
         } 
         return join("",$ar); 
} 

/**
 * 获取文件扩展名
 * @param <type> $filename
 * @return <type>
 */
function getFileExt($filename){
    $ext = strrchr($filename,'.');
    // 根本没有扩展名
    if ( empty($ext) ){
        return null;
    }
    return $ext;
}
/*
获取文件;
*/
function startfile(){
		//$url = $_SERVER['PHP_SELF']; 
		//$filename = end(explode('/',$url)); 
		$a=$_SERVER['SCRIPT_NAME'];
		 //echo basename($a)
		return  basename($a);
}
 
/**
 * 获取基本文件名
 * @param <type> $filename
 * @return <type>
 */
function getFilename($filename){
    return str_replace(getFileExt($filename),'', $filename);
}

function getFileBasename($filename){
    $filename = str_replace('\\', '/', $filename);
    $filename = strrchr($filename, '/');
    $filename = str_replace(getFileExt($filename),'', $filename);
    return str_replace('/', '', $filename);
}



/**
 *    将default.abc类的字符串转为$default['abc']
 *    @param     string $str
 *    @return    string
 */
function strtokey($str, $owner = ''){
    if (!$str){
        return '';
    }
	
    if ($owner){
        return $owner . '[\'' . str_replace('.', '\'][\'', $str) . '\']';
    }else{
        $parts = explode('.', $str);
        $owner = '$' . $parts[0];
        unset($parts[0]);		
        return strtokey(implode('.', $parts), $owner);
    }
}

function file_write($f, $c=''){
	$dir = dirname($f);
	if(!is_dir($dir)){
		dirs_mk($dir);
	}
	return @file_put_contents($f, $c);
}

//
function file_read($f){
	return @file_get_contents($f);
}
/*
建立目录;可多级;
*/
function dirs_mk($l1, $l2 = 0777){
	if(!is_dir($l1)){
		dirs_mk(dirname($l1), $l2);		
		return @mkdir($l1, $l2);
	}
	return true;
}
// 批量创建目录
function mkdirs($dirs,$mode=0777) {
    foreach ($dirs as $dir){
        if(!is_dir($dir))  mkdir($dir,$mode);
    }
}
/* function dump1($str){
	 echo Genv::dump($str);
 
 }
*/
function dump($var, $echo=true,$label=null, $strict=true){
    $label = ($label===null) ? '' : rtrim($label) . ' ';
    if(!$strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, true);
            $output = "<pre>".$label.htmlspecialchars($output,ENT_QUOTES)."</pre>";
        } else {
            $output = $label . " : " . print_r($var, true);
        }
    }else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if(!extension_loaded('xdebug')) {
            $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
            $output = '<pre>'
                    . $label
                    . htmlspecialchars($output, ENT_QUOTES)
                    . '</pre>';
        }
    }
    if ($echo) {
        echo($output);
        return null;
    }else {
        return $output;
    }
}



function getgpc($k) {
	$var=array_merge($_GET,$_POST);
	/*switch($t) {
		case 'P': $var = &$_POST; break;
		case 'G': $var = &$_GET; break;
		case 'C': $var = &$_COOKIE; break;
		case 'R': $var = &$_REQUEST; break;
	}*/
	return isset($var[$k]) ? (is_array($var[$k]) ? $var[$k] : trim($var[$k])) : NULL;
}
function getgpc2($k, $t='R') {
	switch($t) {
		case 'P': $var = &$_POST; break;
		case 'G': $var = &$_GET; break;
		case 'C': $var = &$_COOKIE; break;
		case 'R': $var = &$_REQUEST; break;
	}
	return isset($var[$k]) ? (is_array($var[$k]) ? $var[$k] : trim($var[$k])) : NULL;
}
 

/**
 * 获得当前格林威治时间的时间戳
 *
 * @return  integer
 */
function gmtime(){
    return (time() - date('Z'));
}

/**
 * 将GMT时间戳格式化为用户自定义时区日期
 *
 * @param  string       $format
 * @param  integer      $time       该参数必须是一个GMT的时间戳
 *
 * @return  string
 */
 
 
 /**
 * 获得服务器的时区
 *
 * @return  integer
 */
function server_timezone(){

    if (function_exists('date_default_timezone_get')){
		
        return date_default_timezone_get();
    }else{
        return date('Z') / 3600;
    }

}

G('timezone',0);
/**
 *  生成一个用户自定义时区日期的GMT时间戳
 *
 * @access  public
 * @param   int     $hour
 * @param   int     $minute
 * @param   int     $second
 * @param   int     $month
 * @param   int     $day
 * @param   int     $year
 *
 * @return void
 */
function local_mktime($hour = NULL , $minute= NULL, $second = NULL,  $month = NULL,  $day = NULL,  $year = NULL){
     $timezone = G('timezone');
    /**
    * $time = mktime($hour, $minute, $second, $month, $day, $year) - date('Z') + (date('Z') - $timezone * 3600)
    * 先用mktime生成时间戳，再减去date('Z')转换为GMT时间，然后修正为用户自定义时间。以下是化简后结果
    **/
    $time = mktime($hour, $minute, $second, $month, $day, $year) - $timezone * 3600;

    return $time;
}


/**
 * 将GMT时间戳格式化为用户自定义时区日期
 *
 * @param  string       $format
 * @param  integer      $time       该参数必须是一个GMT的时间戳
 *
 * @return  string
 */

function local_date($format, $time = NULL){

    $timezone = G('timezone');
    if ($time === NULL){
        $time = gmtime();
    }elseif ($time <= 0){
        return '';
    }
    $time += ($timezone * 3600);
    return date($format, $time);
}


/**
 * 转换字符串形式的时间表达式为GMT时间戳
 *
 * @param   string  $str
 *
 * @return  integer
 */
function gmstr2time($str){

    $time = strtotime($str);
    if ($time > 0){
        $time -= date('Z');
    }

    return $time;
}

/**
 *  将一个用户自定义时区的日期转为GMT时间戳
 *
 * @access  public
 * @param   string      $str
 *
 * @return  integer
 */
function local_strtotime($str){

     $timezone = G('timezone');
	 
    /**
    * $time = mktime($hour, $minute, $second, $month, $day, $year) - date('Z') + (date('Z') - $timezone * 3600)
    * 先用mktime生成时间戳，再减去date('Z')转换为GMT时间，然后修正为用户自定义时间。以下是化简后结果
    **/
    $time = strtotime($str) - $timezone * 3600;

    return $time;

}

/**
 * 获得用户所在时区指定的时间戳
 *
 * @param   $timestamp  integer     该时间戳必须是一个服务器本地的时间戳
 *
 * @return  array
 */
function local_gettime($timestamp = NULL){
    $tmp = local_getdate($timestamp);
    return $tmp[0];
}

/**
 * 获得用户所在时区指定的日期和时间信息
 *
 * @param   $timestamp  integer     该时间戳必须是一个服务器本地的时间戳
 *
 * @return  array
 */
function local_getdate($timestamp = NULL){
    $timezone = G('timezone');

    /* 如果时间戳为空，则获得服务器的当前时间 */
    if ($timestamp === NULL)
    {
        $timestamp = time();
    }

    $gmt        = $timestamp - date('Z');       // 得到该时间的格林威治时间
    $local_time = $gmt + ($timezone * 3600);    // 转换为用户所在时区的时间戳

    return getdate($local_time);
}



// 优化的require_once
function require_cache($filename) {
    static $_importFiles = array();
    $filename = realpath($filename);
    if (!isset($_importFiles[$filename])) {
        if (file_exists_case($filename)) {
            require $filename;
            $_importFiles[$filename] = true;
        } else {
            $_importFiles[$filename] = false;
        }
    }
    return $_importFiles[$filename];
}

// 区分大小写的文件存在判断
function file_exists_case($filename) {
    if (is_file($filename)) {
        if (IS_WIN ) {
            if (basename(realpath($filename)) != basename($filename))
                return false;
        }
        return true;
    }
    return false;
}

//截取字符串长度;
function str_left($string, $length, $dot = '...') {
	$strlen = strlen($string);
	if($strlen <= $length) return $string;
	$string = str_replace(array(' ','&nbsp;', '&amp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;'), array('∵',' ', '&', '"', "'", '“', '”', '—', '<', '>', '·', '…'), $string);
	$strcut = '';
	$CHARSET='utf-8';
	if(strtolower($CHARSET) == 'utf-8') {
		$length = intval($length-strlen($dot)-$length/3);
		$n = $tn = $noc = 0;
		while($n < strlen($string)) {
			$t = ord($string[$n]);
			if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
				$tn = 1; $n++; $noc++;
			} elseif(194 <= $t && $t <= 223) {
				$tn = 2; $n += 2; $noc += 2;
			} elseif(224 <= $t && $t <= 239) {
				$tn = 3; $n += 3; $noc += 2;
			} elseif(240 <= $t && $t <= 247) {
				$tn = 4; $n += 4; $noc += 2;
			} elseif(248 <= $t && $t <= 251) {
				$tn = 5; $n += 5; $noc += 2;
			} elseif($t == 252 || $t == 253) {
				$tn = 6; $n += 6; $noc += 2;
			} else {
				$n++;
			}
			if($noc >= $length) {
				break;
			}
		}
		if($noc > $length) {
			$n -= $tn;
		}
		$strcut = substr($string, 0, $n);
		$strcut = str_replace(array('∵', '&', '"', "'", '“', '”', '—', '<', '>', '·', '…'), array(' ', '&amp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;'), $strcut);
	} else {
		$dotlen = strlen($dot);
		$maxi = $length - $dotlen - 1;
		$current_str = '';
		$search_arr = array('&',' ', '"', "'", '“', '”', '—', '<', '>', '·', '…','∵');
		$replace_arr = array('&amp;','&nbsp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;',' ');
		$search_flip = array_flip($search_arr);
		for ($i = 0; $i < $maxi; $i++) {
			$current_str = ord($string[$i]) > 127 ? $string[$i].$string[++$i] : $string[$i];
			if (in_array($current_str, $search_arr)) {
				$key = $search_flip[$current_str];
				$current_str = str_replace($search_arr[$key], $replace_arr[$key], $current_str);
			}
			$strcut .= $current_str;
		}
	}
	return $strcut.$dot;
}


//视图赋值;
function _preView(){

		$client=Genv_Config::get('Genv', 'appdir');


		//dump(G());
		if(!defined('__URL__')) define('__URL__',PHP_FILE.'/'.G('APP'));        
		if(!defined('__ACTION__'))  define('__ACTION__',__URL__.'/'.G('ACT'));	
	 
		$a=array("_public"=>APPPUBLIC,
			'_url'=>__URL__,
			'_action'=>__ACTION__,
			'_self'=>__SELF__,
			'_app'=>__APP__,
			'random_num'=>rand(),
			'mid'=>getgpc('mid'),
			'WEB_PUBLIC_URL'=>WEB_PUBLIC_URL,
			'API'=>U(''),
			"pageinfo"=>_pageinfo(),
			);
		  
	     return $a;		
}





if (!function_exists('json_decode')){
	function json_decode($s, $ass = false){
		//$assoc = ($ass) ? 16 : 32;
		 $json =  Genv::factory('Genv_Json');

		return $json->decode($s);
	}
}

if (!function_exists('json_encode')){
	function json_encode($s){
		  $json =  Genv::factory('Genv_Json');

		return $json->encode($s);
	}
}

if (!function_exists('hash_hmac')) {
	function hash_hmac($algo, $data, $key, $raw = false) {
		if (empty($algo)) {
			return false;
		}
		switch ($algo) {
			case 'md5':
				return mhash(MHASH_MD5, $data, $key);
				break;
			case 'sha1':
				return mhash(MHASH_SHA1, $data, $key);
				break;
		}
	}
}

if (!function_exists('array_combine')) {
	function array_combine( $keys, $values ) {
	   if( !is_array($keys) || !is_array($values) || empty($keys) || empty($values) || count($keys) != count($values)) {
		 trigger_error( "array_combine() expects parameters 1 and 2 to be non-empty arrays with an equal number of elements", E_USER_WARNING);
		 return false;
	   }
	   $keys = array_values($keys);
	   $values = array_values($values);
	   $result = array();
	   foreach( $keys as $index => $key ) {
		 $result[$key] = $values[$index];
	   }
	   return $result;
	}
}

 



if (!function_exists('http_build_query')) {
	function http_build_query($data, $prefix='', $sep='', $key='') {
	    $ret = array();
	    foreach ((array)$data as $k => $v) {
	        if (is_int($k) && $prefix != null) {
	            $k = urlencode($prefix . $k);
	        }
	        if ((!empty($key)) || ($key === 0))  $k = $key.'['.urlencode($k).']';
	        if (is_array($v) || is_object($v)) {
	            array_push($ret, http_build_query($v, '', $sep, $k));
	        } else {
	            array_push($ret, $k.'='.urlencode($v));
	        }
	    }
	    //if (empty($sep)) $sep = ini_get('arg_separator.output');
	    $sep = '&';
		return implode($sep, $ret);
	}
}

 
if (!function_exists('mb_substr')) {
	function mb_substr($str, $start = 0, $length = 0, $encode = 'utf-8') {
	    $encode_len = strtolower($encode) == 'utf-8' ? 3 : 2;
	    
	    for($byteStart = $i = 0; $i < $start; ++$i) {
	        $byteStart += ord($str{$byteStart}) < 128 ? 1 : $encode_len;
	        if($str{$byteStart} == '') return '';
	    }
	    
	    for($i = 0, $byteLen = $byteStart; $i < $length; ++$i)
	        $byteLen += ord($str{$byteLen}) < 128 ? 1 : $encode_len;
	        
	    return substr($str, $byteStart, $byteLen - $byteStart);
	}
}

if (!function_exists('mb_strlen')) {
	function mb_strlen($text, $encode = 'utf-8') {
		if (strtolower($encode) == 'utf-8') {
			return preg_match_all('%(?:[\x09\x0A\x0D\x20-\x7E]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})%xs',$text,$out);
	   } else {
			return strlen($text);
	   }
	}
}
?>