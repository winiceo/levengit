<?php
/// 数据交互组件管理类

class Genv_Dmg extends Genv_Base{
 
	public static function Dmg(){
	}
	//------------------------------------------------------------------
	/**
	 * 调用一个数据交互方法、动作　Genv_Dmg::call($dsRoute, $opt); 同名快捷函数为 DS($dsRoute, $opt);
	 * @param $eHandler	是否自动处理错误信息，默认为 TRUE
	 *							设置为 TRUE  时，将自动处理错误信息，并且退出程序，返回值为：真实数据结果
	 *							设置为 FALSE 时，将忽略错误，直接返回标准结果格式，返回值为：用 RST 封装过的标准结果结构
	 * @param $dsRoute	数据交互组件的路由
	 * @param $opt		数据交互的缓存与过滤策略，默认为空,不做任何缓存与过虑，
	 *					其规则如下：
	 *					[缓存组/]缓存时间[|过滤函数]  
	 *					  
	 *					缓存组,过滤函数 都是可选的，缓存时间为空表示不缓存，为0表示永久缓存，其它数值表示缓存的秒数
	 *					缓存组的可能值为:  空 、 i 、 g0,g1... 、 p0,p1... 、 s0,s1.... 、 u 
	 *					如： 	$opt = 123 			表示当前数据的调用会被缓存　123 秒
	 *					  	$opt = 'g1/223'		表示对当数据件交互产生缓存组，每个缓存周期是 223 秒
	 *											g1 表示根据数据调用组件的第一个参数作为标识,同理 g2,g3,g4
	 *											g0 表示根据数据调用组件的所有参数作为标识
	 *						$opt = 'g2/0|format_func1|format_func2'
	 *						表示，当前调用将会用第2个参数建立缓存组，建立永久缓存，第一次取数据时，
	 *						会依次用函数库的 	format_func1,format_func2 对组件数据进行格式化或者过滤处理			
	 *					
	 *						$opt = '|format_func';			
	 *						表示，当前调用不缓存，但需要使用 format_func 进行格式化或者过滤处理
	 *
	 *						$opt = '0|format_func'	以上例不同，此调用将做永久缓存			
	 * 
	 * @return 			见 $eHandler	 说明
	 */
	public static function call($eHandler, $dsRoute, $opt=false){
		
		$useCache	= false;
		$formatFunc = array();
		$gCache		= false;
		
		$gCacheId	= '';
		// 缓存类别有三种： g  默认的应用程序级缓存; s 会话级缓存; p 页面周期缓存; i 无论缓存是否存在都不使用，通常用于调试
		$cacheType	= 'g';
		$useStatic	= false;
		$gCacheName = COM_CACHE_KEY_PRE.$dsRoute;
		// 静态缓存数据
		static $rstData = array();
		// 静态对象
		static $objArr	= array();
		// 第三个参数开始，将传递给 数据组件
		$arg	= func_get_args();
		array_shift($arg);
		array_shift($arg);
		array_shift($arg);
		//--------------------------------------------------------------					
		if ($opt || $opt===0 || $opt==='0'){
			if (is_numeric($opt)) {
				$ttl = $opt * 1;
				$useCache = true;
			}else{
				$optArr		= explode('|', $opt);
				//缓存选项
				$cacheArr	= explode('/', $optArr[0]);
				
				//不缓存，或者设定了缓存时间 ，但没有缓存分组要求
				if (is_numeric($cacheArr[0]) || $cacheArr[0]==='' ){
					if ($cacheArr[0] === ''){
						$useCache = false;
					}else{
						$ttl = $cacheArr[0] * 1;
						$useCache = true;
					}
				}else{
					// 使用缓存组
					$flag = trim(strtolower($cacheArr[0]));
					switch ($flag[0]) {
						
					    // 自定义周期的用户程序级缓存,用户组缓存
						case 'g':
							$cacheType = 'g';
							$argi		= isset($flag[1])		? $flag[1]*1		: 0;
							$ttl		= isset($cacheArr[1])	? $cacheArr[1]*1	: 0;
							$useCache	= true;
							$gCache		= true;
							$gCacheId	= ($argi>0) ? $arg[$argi-1] : Genv_Dmg::_creCacheID($arg);
							break;
						
						// 页面周期的缓存
						case 'p':
							$useCache	= true;
							$cacheType	= 'p';
							$argi		= isset($flag[1]) ? $flag[1]*1 : 0;
							$gCacheId	= ($argi>0) ? $arg[$argi-1] : Genv_Dmg::_creCacheID($arg);
							break;
						// 会话周期的缓存
						case 's':
							$useCache	= true;
							$cacheType	= 's';
							break;
						// 用户组缓存
						case 'u':
							$useCache	= true;
							$gCache		= true;
							$cacheType	= 'u';
							$ttl		= isset($cacheArr[1])	? $cacheArr[1]*1	: 0;
							$argi		= isset($flag[1])		? $flag[1]*1		: 0;
							$gCacheId	= ($argi>0) ? $arg[$argi-1] : Genv_Dmg::_creCacheID($arg);
							$gCacheId	= 'uid_'.Genv_User::uid().' '.$gCacheId;
							break;
						
						// 忽略缓存
						case 'i':
							$cacheType	= 'i';
							break;
						default:
							trigger_error("dsMgr cache OPT : [ $opt ] is  invalid ", E_USER_ERROR);  exit;
							
					}
				}
				
				//管道过滤队列
				array_shift($optArr);
				$formatFunc = $optArr;
			}
		}
		//--------------------------------------------------------------
		// 如果有使用格式化管道，则自动根据管道分组
		if (!empty($formatFunc)) {
			$gCache		= true;
			$gCacheId 	= implode(' ', $formatFunc).' '.$gCacheId;
		}
		// 让 cache id 始终不为空
		$gCacheId.=' -';
		//--------------------------------------------------------------
		//var_dump(array($useCache,$gCacheName,$gCacheId, $cacheType));
		//echo "CACHE TRY TO FIND [$useCache] [$gCacheName], [$gCacheId]\n";
		// 需要使用缓存，如果缓存存在，则直接给出结果
		if ($useCache && $cacheType!='i'){
			$rst = false;
			switch ($cacheType) {
				case 'g':
					$rst = $gCache	? FG($gCacheName, $gCacheId)
									: F($gCacheName);
					break;
				// 页面周期的缓存
				case 'p':
					$rst = Genv_Com::getData($gCacheId,$gCacheName);
					//$rst = G($gCacheName.".".$gCacheId);
					break;
				// 用户缓存
				case 'u':
					$rst = FG($gCacheName, $gCacheId);
					break;
				// 会话周期的缓存
				case 's':
					$cacheType = 's';
					break;
			}
			//if (is_array($rst)) echo "CACHE HIT: [$useCache] [$gCacheName], [$gCacheId] \n";//print_r($rst);
			if (is_array($rst) && isset($rst['rst']) && $rst['rst'] !== null){return $eHandler ? $rst['rst'] : $rst;}
		}
		//--------------------------------------------------------------
		$rData = Genv_Com::_parseRoute($dsRoute);
		$stKey = $rData[1].$rData[2];
 
		if (!isset($objArr[$stKey])){
			$objArr[$stKey] = Genv_Com::_cls($dsRoute,'com',true);
		}
		// 第三个参数开始将传递给数据调用组件
		$comRst = call_user_func_array(array(&$objArr[$stKey], $rData[3]), $arg);
		//--------------------------------------------------------------
		// 错误处理 
		if (!empty($comRst['errno'])){
			if ($comRst['errno'] == '1040008') {
				
				return;
			} elseif ($comRst['errno'] == '1040016' && !IS_IN_JS_REQUEST) {
				Genv_App::tips(array('tpl' => 'error', 'msg' => L('common__apiError__limitTip')));
			}
			return  $eHandler ? Genv_Dmg::errorDump($comRst) : $comRst;
		}
		//--------------------------------------------------------------
		//通过管道式策略，格式化，过滤处理数据
		if (!empty($formatFunc)) {
			$comRst['rst'] = Genv_Dmg::_formatData($formatFunc, $comRst['rst']);
		}
		//--------------------------------------------------------------
		// 需要使用缓存时，建立缓存
		if ($useCache  && $cacheType!='i' ){
			switch ($cacheType) {
				case 'g':
				case 'u':
					$ttl = $ttl*1 ;
					if ($gCache){
						FG($gCacheName, $gCacheId, $comRst, $ttl);
					}else{
						F($gCacheName, $comRst, $ttl);
					}
					break;
				case 'p':
					Genv_Com::setData($gCacheId, $comRst, $gCacheName); 
					//G( $gCacheName.".".$gCacheId, $comRst);
					break;
				case 's':
					//会话级缓存未实现
					break;
			}
		}
		//--------------------------------------------------------------
		return  $eHandler ? $comRst['rst'] : $comRst;
	}
	//------------------------------------------------------------------
	/// 清除数据组件的缓存,同样适用于缓存组
	function delete($dsRoute){
		DD($dsRoute);
	}
	//------------------------------------------------------------------
	/// 根据一个变量生成一个HASH值
	function _creCacheID($arg){
		return md5(serialize($arg));
	}
	//------------------------------------------------------------------
	/// 给定管道函数列表,格式化、过滤数据
	function _formatData($funcArr,$data){
		foreach ($funcArr as $func){
			$data = H($func, $data);
		}
		return $data;
	}
	
	/// 获取一个数据调用的实例
	function & get($dsRoute){
		return Genv_Com::_cls($dsRoute,'com',true);
	}
	//------------------------------------------------------------------
	/// 错误控制，输出错误信息
	function errorDump($rst){
		dump($rst);
		exit;
		APP::xcacheOpt(false);
		if(isset($rst['log']) && $rst['log']){
			APP::LOG($rst['log']);
		}
		//var_dump($rst);
		$msg = $rst['err'];
		if (IS_IN_API_REQUEST || IS_IN_JS_REQUEST){
			if (!IS_DEBUG){
				unset($rst['log']);//unset($rst['']);
			}
			header('Content-type: application/json;');
			echo json_encode($rst);
		}else{
			F('error',$msg, false);
		}
		exit;		
	}
}
