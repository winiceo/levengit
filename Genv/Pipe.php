<?php
/**
* ��BigPipe�Ĺܵ����������
*/
class Genv_Pipe extends Genv_Base{
  
   // protected static $_file;    
   
    protected function _postConstruct()    {
        parent::_postConstruct();
      
    }
	function Pipe(){
	}
	/**
	* �½�һ�� pagelet
	* 
	* @param mixed $r	pagelet ��·��
	* @param mixed $p	���ݸ� pagelet �Ĳ���
	* @param mixed $o	ִ��˳��,���Ϊ false������Ƚ��ȳ��Ĺ��򣬷�֮�����ݴ����ִ��
	* @param mixed $isPerch �Ƿ����ռλ��ǩ,��ռλpagelet����
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
	* ռλ��ǩ,����ȷ��ģ��Ҫ��ʾ��λ��
	*/
	function _perchLable($k,$r){
		echo sprintf("<div class='hidden' id='%s' xRoute='%s' ></div>",Genv_Pipe::_idKey($k),$r);
	}
	///������һ��ռλ��ǩ�ġ�ID
	public static function _idKey($k){
		return 'Genv_Pipe_Module_'.$k;
	}
	
	/**
	* ��ǰ�Ƿ���������PIPE
	* 
	* @param mixed $run
	*/
	public static function isRunning($run=false){
		static $isRun = false;
		if ($run) $isRun = $run;
		return $isRun ;
	}
	
	//��ǰ�����Ƿ�ʹ�� PIPE ��Ĭ��ʹ��
	public static function usePipe($state=true){
		static $isUse = true;
		if (func_num_args()){
			$isUse = $state;
		}
		return $isUse;
	}
	
	/**
	* һ�ιܵ������ĳ����ģ���������,֪ͨǰ�˴�������߼�
	* 
	* @param mixed	$rst
	* @param string JS��ͳһ���õĹܵ�����
	* @return		�޷��أ����һ�� script�������岢���
	*/
	public static function output($rst, $jsFunc='load'){
		$s = sprintf("\n<script>%s.%s(%s);</script>", V_JS_PIPE_OBJ, $jsFunc, json_encode($rst));
		//�����ֻ���������ͻ���
		/*if (APP::xcacheOpt()){
			APP::xcache($s);
		}*/
		
		echo $s;
		unset($s);
		@flush();
	}
	
	/**
	* ִ�йܵ������е������ģ��
	*/
	public static function run(){
		if (!Genv_Pipe::usePipe()){
			return false;
		}
		
		//�����ֻ���������ͻ���
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
			//���SCRIPT������
			Genv_Pipe::output($rst);
			
			//����Ƿ�����ӹܵ��������뵽��ǰ���е��ʼ,����ʹ�õݹ�
			$child_pls = Genv_Pipe::_getAndCleanPagelets();
			if ($child_pls && is_array($child_pls)) {
				//print_r($pls);print_r($child_pls);exit;
				$pls = array_merge($child_pls, $pls);
				
			}
		}
		Genv_Pipe::_end();
	}
	
	/**
	* ִ��һ���ܵ�ģ��
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
	
	
	//�ܵ���ʼ
	public static function _start(){
		Genv_Pipe::isRunning(true);
		//��ʼ����ȫ��������
		 
		$iniCfg = array(
			 
		);

		
		Genv_Pipe::output($iniCfg, 'start');
	}
	
	//�ܵ�����
	public static function _end(){
		Genv_Pipe::output(true, 'end');
		Genv_Pipe::isRunning(false);
	}
	
	/**
	* ��ȡ��ǰ��pagelet�����У������
	* @return	���û�� pagelet�����С��򷵻� false�����������򷵻� pagelets����
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
	
	//���ԣ��鿴����
	function debug($var){
		echo '<pre style="color:green; border: 1px solid green;padding: 5px;">Genv_Pipe�������٣�'."\n";
		var_dump($var);
		echo '</pre>';
	}
}