<?php
/*
*/
class Genv_App extends Genv_Base{
    
	 protected $_front;
	 protected $_controller = null; 
	 protected $_action = null;  
    

     protected $_action_default = 'index';
	 
	 protected $_session;

     protected $_view_object;

	 protected $_config = array();     

	 protected $_errors = array();
	 
	 protected $_query = array();

	 protected $_info = array();

	 protected $_request;
	 protected $_rewrite;
	 protected $_response;

   
	 public function __construct(){           
        //控制器初始化
       
		parent::_postConstruct();	

        $class = get_class($this);

		//echo $class;
 
        $this->_session = Genv::factory(
            'Genv_Session',
            array('class' => $class)
        );     
		
        $this->_response = Genv_Registry::get('response'); 
       
        if (empty($this->_controller)) {			 
            $pos = strrpos($class, '_');			
            $this->_controller =$class;			
            $this->_controller = preg_replace(
                '/([a-z])([A-Z])/',
                '$1-$2',
                $this->_controller
            );			 
            $this->_controller = strtolower($this->_controller);
			$this->_controller = str_replace("-action",'',$this->_controller);			 
        }
 
        G('APP',$this->_controller);
		
	    
       
        $this->_request = Genv_Registry::get('request');        
        
        $this->_rewrite = Genv_Registry::get('rewrite');  
        
	 }

	 public function setFront($front){
        $this->_front = $front;
     } 

	 public function setAction($action){
        $this->_action = $action;
		G('ACT',$this->_action);
     } 

	 public function fetch($spec = null){
        if (! $spec) {         
          
            $uri = Genv::factory('Genv_Uri_Action');
            $this->_info = $uri->path;
            $this->_query = $uri->query;
            $this->_format = $uri->format;
            
        } elseif ($spec instanceof Genv_Uri_Action) {
           
            $this->_info = $spec->path;
            $this->_query = $spec->query;
            $this->_format = $spec->format;
            
        } else {           
            $uri = Genv::factory('Genv_Uri_Action');
            $uri->set($spec);
            $this->_info = $uri->path;
            $this->_query = $uri->query;
            $this->_format = $uri->format;
            
        }  
       
        $shift = ! empty($this->_info[0])
              && $this->_info[0] == $this->_controller;
              
        if ($shift) {
            array_shift($this->_info);
        }
       
        // ignore .php formats
        if (strtolower($this->_format) == 'php') {
			 
            $this->_format = null;
        }    
		
 
     
        if(!$this->_action){
			if (empty($this->_info[0])) {
			   
				$this->_action = $this->_action_default;
			} else {    
				
				$this->_action = array_shift($this->_info);
			}
			G('ACT',$this->_action);
		}		
	   
		if(method_exists($this,'_initialize')) {
			 
            $this->_initialize();
        }
		
 
		$params=array();
		$method = $this->_action;
	 
		 
		if (! $method) {            
            $this->_notFound($this->_action, $params);
            
        }else{
		   
		
            if (method_exists($this, $method)) {
                if (!empty($params)) {
                    call_user_func_array(array(&$this, $method), $params);
                } else {
                     $this->$method();
                }
            }else {            
                 //执行空函数;
				 $this->_empty($this->_action, $params);
            
			}
        }

		 
		 return $this->_response;
    }
	//空函数;
	public function _empty($act=null,$params=array()){
	     
	
	}

	public function getby($key){
		 $a=$this->_info;

	 
		 $len=count($a);		 
		 if(!is_array($a)||count($a)%2!=0){
			return null;
		 }
		 $params=array();
		 for ($i = 0; $i <= count($a); ) {
			$tem=$a[$i];
			$i++;
			if($a[$i]!=null&&$a[$i]!='null'){
			$parmas[$tem]=$a[$i];
			}
			$i++;
		}
	 

		return isset($parmas[$key])?$parmas[$key]:null;
   
   }
	protected function _notFound($action, $params = null){
        $this->_errors[] = "Controller: \"{$this->_controller}\"";
        $this->_errors[] = "Action: \"$action\"";
        $this->_errors[] = "Format: \"{$this->_format}\"";
        foreach ((array) $params as $key => $val) {
            $this->_errors[] = "Param $key: $val";
        }	
       /* $this->_response->setStatusCode(404) 
		->setContent('Page not found.');
		$this->_response->display();
		 */
		$this->_response->setStatusCode(404)
                ->setHeader('X-Foo', 'Bar')
               ->setCookie('baz', 'dib')
                ->setContent('Page not found.')
                ->display();
		exit;
        // just set the view; if we call _forward('error') we'll get the
        // error view, not the not-found view.
       // $this->_view = 'notFound';
    }

	 
 

	//------------------------------------------------------------------
	//------------------------------------------------------------------
	

	/**
	 * APP::ajaxRst($rst,$errno=0,$err='');
	 * 通用的 AJAX 或者  API 输出入口
	 * 生成后的JSON串结构示例为：
	 * 成功结果： {"rst":[1,0],"errno":0}
	 * 失败结果 ：{"rst":false,"errno":1001,"err":"access deny"}
	 * @param $rst
	 * @param $errno 	错误代码，默认为 0 ，表示正常执行请求， 或者 >0 的 5位数字 ，1 开头的系统保留
	 * @param $err		错误信息，默认为空
	 * @param $return	是否直接返回数据，不输出
	 * @return unknown_type
	 */
	function ajaxRst($rst,$errno=0,$err='', $return = false){
		$r = array('rst'=>$rst,'errno'=>$errno*1,'err'=>$err);
		if ($return) {
			return json_encode($r);
		}else {
			header('Content-type: application/json;charset=utf-8');
			exit(json_encode($r));
		}
	}
	//------------------------------------------------------------------
	
	///todo
	function JSONP($rst, $errno=0,$err='', $callback='callback', $script=''){
		echo "<script language='javascript'>{$callback}(".json_encode(array('rst'=>$rst,'errno'=>$errno*1,'err'=>$err)).");".$script."</script>";
	}	
	//------------------------------------------------------------------
	function deny($info=''){
		header("HTTP/1.1 403 Forbidden");
		exit('Access deny'.$info);
	}
	//------------------------------------------------------------------
	/**
	 * APP::tips($params,$display = true);
	 * 显示一个消息，并定时跳转
	 * @param $params Array
	 * 		['msg'] 显示消息,
	 * 		['location'] 跳转地址,
	 * 		['timeout'] = 3 跳转时长 ,0 则不跳转 此时 location 无效
	 * 		['tpl'] = '' 使用的模板名,
	 * 		如果$params不是数组,则直接当作 $params['msg'] 处理
	 * @param $display boolean 是否即时输出
	 */
	function tips($params,$display = true) {
		static $msg=array();
		 
		if (!is_array($params)) {
			$params = array('msg' => $params);
		}

		if (isset($params['msg']) && is_array($params['msg'])) {
			foreach($params['msg'] as $v) {
				$msg[] = $v;
			}
		} elseif(isset($params['msg'])) {
			$msg[] = $params['msg'];
		}
		
		if ($display) {
			$params['msg'] = $msg;
			$defParam = array('timeout'=>0, 'location'=>'', 'lang'=>'', 'baseskin'=>true, 'caching'=>'','tpl'=>'');
			$params = array_merge($defParam, $params);

			$time	= $params['timeout']*1;
			$url	= $params['location'];

			if($time) {
				header("refresh:{$time};url=".$url);
			}

			if ($params['tpl']) {
				if (in_array($params['tpl'], array('e403', 'e404'))) {
					H('err404', implode('<br />', $params['msg']));
				} else if (in_array($params['tpl'], array('error', 'error_busy', 'error_force', 'error_rest'))) {
					H('error', implode('<br />', $params['msg']));
				} else {	
					//dump();
					$params['msg']= implode('<br />', $params['msg']);
                    if(empty($url)){
					 $params['location']=$this->_getReferer();
					
					}
					 
					V($params)->display($params['tpl']);
				}
				exit;
			} else {
				
				if($time) {
					echo "<meta http-equiv='Refresh' content='{$time};URL={$url}'>\n";
				}
				echo implode('<br />', $params['msg']);
			}
			exit;
		}
	}
	/**
		 * 先从g:callback获取referer，无则从$_SERVER['HTTP_REFERER']获取
		 * 主要针对IE7及以下在IFRAME获取$_SERVER['HTTP_REFERER']错误的的问题
		 * @todo 来源检查
		 * @param bool $failure_def 如果为空，是否返回mgr/admin.index？默认为是
		 * @return string
		 */
	   function _getReferer($failure_def = true){
			$ref = strval(Q('g:callback'));
			
			if(empty($ref)){
				$ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
			}
			
			if(empty($ref) && true == $failure_def){
				//$ref = W_BASE_HTTP. URL('mgr/admin.index', null, 'admin.php');
			}
			
			return $ref;
		}

	function success($rst,$ajax=false){
		    //如果ajax提交，直接返回;
			if($ajax || $this->_request->isXhr()){	 
				Genv_App::ajaxRst($rst);			 
			} 			
			Genv_App :: tips(array('msg'=> $rst['msg'], 'tpl' => 'success'));
			
	}
	function error($msg,$ajax=false){
		    //如果ajax提交，直接返回;
			if($ajax || $this->_request->isXhr()){	 
				Genv_App::ajaxRst($msg);			 
			} 			
			Genv_App :: tips(array('msg'=> $msg, 'tpl' => 'error'));
			
	}

	/**
		 * 操作成功后跳转
		 * @param $msg String 要显示的消息
		 * @param $url String|Array 显示消息3秒后跳转的地址
		 * 如果该参数为数据则为路由方式,其中下标为0表示action,1表示module,2表示controller；
		 * 如果该参数被特别设置为'GET_REFERER'（全大写），则表示使用g:callback获取，没有时才使用$_SERVER['HTTP_REFERER']
		 * @param $data mixed json数据，如果设置该值，则以json方式输出
		 */
		function _succ($msg, $url = null, $data=null) {
			if ($data !== null) {
				Genv_App::ajaxRst($data);
			}
			if (is_array($url)) {
				if (empty($url[0])) {
					Genv_App :: tips(array('msg'=> $msg, 'tpl' => 'error', 'baseskin'=>false));
				}
				//$module = isset($url[1]) ? $url[1]: $this->_getModule();;
				//V($this->userInfo);

				//$controller = isset($url[2]) ? $url[2] : $this->_getController();
				//$url = URL( $controller . '/' . $module . '.' . $url[0]);
			}elseif('GET_REFERER' == $url){
				$url = $this->_getReferer();
			}
			
			if (empty($url)) {
				Genv_App :: tips(array('msg'=> $msg, 'tpl' => 'error','baseskin'=>false));
			}
			
			// 成功后直接调整，不出现成功提示页面, 2011-05-20
			//APP :: tips(array('msg'=> $msg, 'tpl' => 'mgr/success', 'timeout'=>3, 'location' => $url, 'baseskin'=>false));

			 
			redirect($url);
		}

		/**
		 * 操作成功后跳转
		 * @param $msg String 要显示的消息
		 * @param $url String|Array 显示消息3秒后跳转的地址,如果该参数为数据则为路由方式,其中下标为0表示action,1表示module,2表示controller,
		 * @param $errno int 如果设置该参数，则返回json结果
		 */
		function _error($msg, $url = null, $errno=null) {
			if ($errno !== null) {
				Genv_App::ajaxRst(false, $errno, $msg);
			}
			if (is_array($url)) {
				if (empty($url[0])) {
					Genv_App :: tips(array('msg'=> $msg, 'tpl' => 'error', 'baseskin'=>false));
				}
				//$module = isset($url[1]) ? $url[1]: $this->_getModule();
				//$controller = isset($url[2]) ? $url[2] : $this->_getController();
				//$url = URL( $controller . '/' . $module . '.' . $url[0]);
			}elseif('GET_REFERER' == $url){
				$url = $this->_getReferer();
			}

			$param = array(
						'msg'=> $msg,
						'tpl' => 'mgr.error',
						'baseskin'=>false
					);

			if ($url) {
				$param += array(
					'timeout'=>3,
					'location' => $url
				);
			} 
			Genv_App :: tips($param);
		}

	
	



}
?>