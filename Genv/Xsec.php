<?php
 

class Genv_Xsec{
 
	
	/**
	 * ����һ��Verify hash�������ⲿУ��
	 * @param string $add
	 * @param bool $useSinaUid
	 * @return string
	 */
	public static function makeVerifyHash($add='', $useSinaUid = true){
		if($useSinaUid){
			$add .= ('#'. Genv_User::uid());
		}
		$useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		
		//trigger_error("{$add}#{$useragent}#". WB_AKEY. '#'. WB_SKEY. '#'. WB_VERSION, E_USER_WARNING);
		return substr(md5("{$add}#{$useragent}#". WB_AKEY. '#'. WB_SKEY. '#'. WB_VERSION), 8, 10);
		
	}
	
	/**
	 * ���������Դ�Ƿ���ȷ
	 * @return int �ɹ�����0�����򷵻ظ���
	 */
	public static function checkReferer(){
		if(!isset($_SERVER['HTTP_REFERER'])){
			return -1;    //NO REFERER
		}
		$referer = @parse_url($_SERVER['HTTP_REFERER']);
		if(!isset($referer['host'])){
			return -2;    //REFERER PARSE FAILURE
		}
		$host = W_BASE_HOST;
		if(false !== strpos($host, ':')){
			$host = preg_replace('/:[0-9]+/', '', $host);
		}
		if($referer['host'] != $host){
			return -3;    //REFERER CHECK FAILURE
		}
		return 0;
	}
	
	/**
	 * ��submit���м��
	 * @param string $reqmethod ��ѡֵ��POST��GET
	 * @param array $param ��ϼ�����顣��ѡ�����У�
	 * array(
	 *     'check_verifyhash' => true,    //�Ƿ��verifyhash����У��
	 *     'add' => '',    //verifyhash add
	 *     'useSinaUid' => true,    //verifyhash useSinaUid
	 *     'check_referer' => true,    //�Ƿ��������Դ���м��
	 * );
	 * @return int �ɹ�����0�����򷵻ظ�����
	 * -1:���󷽷����ԣ�-2:������Դ���ԣ�-3:verifyhashУ��ʧ��
	 */
	public static function checkSubmit($reqmethod = 'POST', $param = array()){
		static $default_param = array(
			'check_verifyhash' => true,
			'add' => '',
			'useSinaUid' => true,
			'check_referer' => true,
		);
		$param = array_merge($default_param, (array)$param);
		
		if($_SERVER['REQUEST_METHOD'] != $reqmethod){
			return -1;
		}
		
		if(true == $param['check_referer'] && 0 != XSec::checkReferer()){
			return -2;
		}
		
		if('POST' == $reqmethod){
			$input = 'p:verifyhash';
		}else{
			$input = 'g:verifyhash';
		}
		if(true == $param['check_verifyhash'] && V($input, null) != XSec::makeVerifyHash($param['add'], $param['useSinaUid'])){
			return -3;
		}
		
		return 0;
		
	}
	
}