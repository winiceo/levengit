<?php
/**
* 类BigPipe的管道输出控制类
*/
class Genv_Pipe extends Genv_Base{
  
   // protected static $_file;    
   
    protected function _postConstruct()    {
        parent::_postConstruct();
      
    }
	function Pipe(){
	}
	/**
	* 新建一个 pagelet
	* 
	* @param mixed $r	pagelet 的路由
	* @param mixed $p	传递给 pagelet 的参数
	* @param mixed $o	执行顺序,如果为 false则符合先进先出的规则，返之，数据大的先执行
	* @param mixed $isPerch 是否输出占位标签,非占位pagelet可用
	*/
	function pagelet($r, $p=array(), $o=false,$isPerch=true){
		static $ost= 1000000,$custom_st = 1000001,$ki=10000;
		$ki++;
		$k =  $o==false ? $ost-- : $custom_st+$o;		
		 //echo $k,"-",$ost,"\n";
		$GLOBALS[PIPE_NAME]['PAGELETS'][$k] = array($r, $p, $ki,$isPerch);
		if ($isPerch){
			Genv_Pipe::_perchLable($ki,$r);
		}
	}
	
	/**
	* 占位标签,用于确定模块要显示的位置
	*/
	function _perchLable($k,$r){
		echo sprintf("<div class='hidden' id='%s' xRoute='%s' ></div>",Genv_Pipe::_idKey($k),$r);
	}
	///　生成一个占位标签的　ID
	public static function _idKey($k){
		return 'Genv_Pipe_Module_'.$k;
	}
	
	/**
	* 当前是否正在运行PIPE
	* 
	* @param mixed $run
	*/
	public static function isRunning($run=false){
		static $isRun = false;
		if ($run) $isRun = $run;
		return $isRun ;
	}
	
	//当前请求是否使用 PIPE ，默认使用
	public static function usePipe($state=true){
		static $isUse = true;
		if (func_num_args()){
			$isUse = $state;
		}
		return $isUse;
	}
	
	/**
	* 一次管道输出，某个子模块运算完成,通知前端处理相关逻辑
	* 
	* @param mixed	$rst
	* @param string JS端统一调用的管道方法
	* @return		无返回，输出一段 script　到缓冲并输出
	*/
	public static function output($rst, $jsFunc='load'){
		$s = sprintf("\n<script>%s.%s(%s);</script>", V_JS_PIPE_OBJ, $jsFunc, json_encode($rst));
		//将布局缓冲输出到客户端
		/*if (APP::xcacheOpt()){
			APP::xcache($s);
		}*/
		
		echo $s;
		unset($s);
		@flush();
	}
	
	/**
	* 执行管道队列中的相关子模块
	*/
	public static function run(){
		if (!Genv_Pipe::usePipe()){
			return false;
		}
		
		//将布局缓冲输出到客户端
		/*if (APP::xcacheOpt()){
			APP::xcache(ob_get_flush());
		}else{
			@ob_end_flush();
		}*/
		@ob_end_flush();
		Genv_Pipe::_start();
		
		$pls = Genv_Pipe::_getAndCleanPagelets(); 
		if (!$pls || !is_array($pls)){
			Genv_Pipe::_end();
			return false;
		}
		 
		 
		while(count($pls)>0){
			$pl		= array_shift($pls);
			 
			
			$rst	= Genv_Pipe::runOnePagelet($pl);
			// dump($rst);
			//输出SCRIPT到缓冲
			Genv_Pipe::output($rst);
			
			//检查是否存在子管道，并插入到当前队列的最开始,避免使用递归
			$child_pls = Genv_Pipe::_getAndCleanPagelets();
			if ($child_pls && is_array($child_pls)) {
				//print_r($pls);print_r($child_pls);exit;
				$pls = array_merge($child_pls, $pls);
				
			}
		}
		Genv_Pipe::_end();
	}
	
	/**
	* 执行一个管道模块
	* @param mixed $pl
	*/
	function runOnePagelet($pl){
		//sleep(2);
		 ob_start();
		list($r, $p, $k,$isPerch) = $pl;
		 //print_r($rArr);
		///$data = $plObj->$rArr[3]($p);
		//V()->display($r);
		$ap=explode(".",$r);
		//$p = func_get_args();
		// dump($p);
	    $data=call_user_func_array(array(A($ap['0']),$ap[1]), $p);
       // $b=A($ap['0']);->$ap[1]();
	   
		 
		$pl_content = ob_get_clean();
		// dump($pl_content);
		return array(
				'html'=>$pl_content,
				'pagelet'=>$r,
				'perch'=>$isPerch,
				'id'=>Genv_Pipe::_idKey($k),
				'data'=>$data
				);
	}
	
	
	//管道开始
	public static function _start(){
		Genv_Pipe::isRunning(true);
		//初始化的全局配置量
		 
		$iniCfg = array(
			 
		);

		
		Genv_Pipe::output($iniCfg, 'start');
	}
	
	//管道结束
	public static function _end(){
		Genv_Pipe::output(true, 'end');
		Genv_Pipe::isRunning(false);
	}
	
	/**
	* 记取当前　pagelet　队列，并清空
	* @return	如果没有 pagelet　队列　则返回 false　，　如有则返回 pagelets数据
	*/
	public static function _getAndCleanPagelets(){
		$ret = $GLOBALS[PIPE_NAME]['PAGELETS'];
		$GLOBALS[PIPE_NAME]['PAGELETS'] = array();
		if (!empty($ret) && is_array($ret)) {
			krsort($ret);
			return $ret;	
		}else{
			return false;
		}
		return empty($ret) ? false : $ret;		
	}
	
	//调试，查看变量
	function debug($var){
		echo '<pre style="color:green; border: 1px solid green;padding: 5px;">Genv_Pipe变量跟踪：'."\n";
		var_dump($var);
		echo '</pre>';
	}
}