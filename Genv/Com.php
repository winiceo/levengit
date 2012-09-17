<?php
//数据缓存组件;

define('R_DEF_MOD_FUNC',		"default_action");
define('V_GLOBAL_NAME',		"__GG");

class Genv_Com{

	//------------------------------------------------------------------
	/**
	 * APP::setData($k,$v=false,$category='STATIC_STORE');
	 * 保存一个静态全局数据
	 */
	function setData($k,$v=false,$category='STATIC_STORE'){
		if (!isset($GLOBALS[V_GLOBAL_NAME][$category]) || !is_array($GLOBALS[V_GLOBAL_NAME][$category])){
			$GLOBALS[V_GLOBAL_NAME][$category] = array();
		}
		if (is_array($k)){
			$GLOBALS[V_GLOBAL_NAME][$category] = array_merge($GLOBALS[V_GLOBAL_NAME][$category], $k);
		}else{
			$GLOBALS[V_GLOBAL_NAME][$category][$k] = $v;
		}
	}
	//------------------------------------------------------------------
	/// 重置一个静态数据分组
	function resetData($category='STATIC_STORE'){
		$GLOBALS[V_GLOBAL_NAME][$category] = array();
	}
	/**
	 * APP::getData($k=false, $category='STATIC_STORE');
	 * 获取一个静态存储数据
	 */
	function getData($k=false, $category='STATIC_STORE', $defV=NULL){
		if (!isset($GLOBALS[V_GLOBAL_NAME][$category]) || !is_array($GLOBALS[V_GLOBAL_NAME][$category])){
			return $defV;
		}
		$gV = $GLOBALS[V_GLOBAL_NAME][$category];
		return $k ? (isset($gV[$k]) ? $gV[$k] : $defV) : $gV;
	}
	/// 获取一个类名,在此定义类的后缀
	function _className($className, $type){
		$tCfg = array(
			'cls'=>	'',
			'mod'=>	'_mod',
			'com'=>	'',
			'pls'=> '_pls'
		);
		return isset($tCfg[$type]) ? $className.$tCfg[$type] : $className;
	}
	function &_cls($iRoute,$type,$is_single){
		static $clsArr=array();
		$iRoute = trim($iRoute);
		$type 	= trim($type);
		
		$clsKey = $type.":".$iRoute;
		if ( $is_single && isset($clsArr[$clsKey]) &&  is_object($clsArr[$clsKey]) ){
			return $clsArr[$clsKey];
		}else{

			$r = Genv_Com::_parseRoute($iRoute); 
            if($type=='com'){			
				$cFile =APPPATH."/Com/".$r[1].$r[2].".com.php";			
			}else{
			return;
			}			 
			require_once($cFile);
			
			$class	= Genv_Com::_className($r[2],$type) ;
			$func	= $r[3];

			if(!class_exists ($class)){
				trigger_error("class [ $class ]  is not exists in file [ $cFile ] ", E_USER_ERROR);
			}
			$p = func_get_args();
			array_shift($p);
			array_shift($p);
			array_shift($p);
			if(!empty($p)){
				$prm = array();
				foreach($p as $i=>$v){
					$prm[] = "\$p[".$i."]";
				}
				eval("\$retClass = new ".$class." (".implode(",",$prm).");");
				if ( $is_single ) { $clsArr[$clsKey] = $retClass; }
				return $retClass;
			}else{
				if ( $is_single ) {
					$clsArr[$clsKey] = new $class;
					return $clsArr[$clsKey];
				}else{
					$c = new $class;
					return $c;
				}
			}
		}
	}
	function _parseRoute($route){
		/*
		static $staticRoute=array();
		if (isset($staticRoute[$route])){
			return $staticRoute[$route];
		}*/
		$route = trim($route);
		$p = preg_match("#^([a-z_][a-z0-9_\./]*/|)([a-z0-9_]+)(?:\.([a-z_][a-z0-9_]*))?\$#sim",$route,$m);
		if (!$p) { trigger_error("route : [ $route  ] is  invalid ", E_USER_ERROR);  return false;}
		if (empty($m[3])) $m[3] = R_DEF_MOD_FUNC;
		return $m;
	}
}