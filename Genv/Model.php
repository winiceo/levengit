<?php
/*
简单模型;

*/

if(version_compare(PHP_VERSION,'6.0.0','<') ) {
    @set_magic_quotes_runtime (0);
    define('MAGIC_QUOTES_GPC',get_magic_quotes_gpc()?True:False);
}
define('MEMORY_LIMIT_ON',function_exists('memory_get_usage')?true:false);
// 记录内存初始使用
if(MEMORY_LIMIT_ON) {
     G('memory_start',memory_get_usage()) ;
}
class Genv_Model extends Genv_Base{
   
   
	 public $db = null;	 
     public $pk  = null;
	 public $fields = array();    
     public $data =   array();
     public $_table;
	 public $options  =   array();
	 public $_prefix=null; //前缀;
	 public $query=null; //前缀;

     protected function _postConstruct(){
        parent::_postConstruct();
		 
		 
        $table=$this->_config['table'];
		$model=$this->_config['model'];
		 
		$config=Genv_Config::get('Genv_Db',$model);
	    
		 //设置过滤;
		$this->query=Genv::factory('Genv_Db_Query');
		
		 
		Genv_Config::set('Genv_Db','adapter','Genv_Db_Adapter_'.$config['type']);

		
		$this->db=Genv_Registry::get("db");	
		//dump($this->db);
		$this->db->connect($model);

		$this->_prefix=$config['prefix'];

        if(!empty($table)) {
			$this->options['from']=$this->_prefix.$table;
			$this->_table=$this->_prefix.$table;            
        }
		$this->options['select']='*';		
		//dump($this->options);
		//echo $this->_table;
        if(!empty($this->_table) ){$this->tableInfo();}
		if(!ISDEBUG){			 
			$this->flush();
	    }
      
		 
     }
    /*
	清空缓存;
	*/
	 public function flush() {
		/*
		清空字段缓存;
		*/
		 $cache = Genv::factory('Genv_Cache');
		 
		 $identify   =   $this->_table.'_fields';
		 
	     $cache->delete($identify);
		
     }
     /*
	 生成表信息；
	*/
	 public function tableInfo() {
		
        // 如果不是Model类 自动记录数据表信息
        // 只在第一次执行记录		 
		 $identify   =   $this->_table.'_fields';
         $field = F($identify);	
		 
		 if(!$field){			 
            $fields =   $this->db->getFields($this->_table);			
			$field   =   array_keys($fields);
			$field['_autoinc'] = false;			
			foreach ($fields as $key=>$val){
				// 记录字段类型				
				$type[$key]    =   $val['type'];
				if($val['primary']) {
					$field['_pk'] = $key;
					
					if($val['autoinc']) $field['_autoinc']   =   true;
				}
			}	
			$field['_type'] =  $type;			
		    F($identify, $field);
         }		 
		 $this->fields=$field;		 
		 return $field;	 
     }
	 
	 public function __call($method,$args) {
		 
        if(in_array($method,array('selectfrom','where','orWhere','order','setpage','page','limit','group','bind','countPages','fetch','leftJoin','innerJoin','having'),true)) {
			 $rs=call_user_func_array(array($this->query, $method),$args);
			 if(in_array($method,array('countPages','fetch') )){
				 return $rs;
			 }else{			 
				return $this;  
			 }
			         
        }elseif(in_array(strtolower($method),array('count','sum','min','max','avg'),true)){
            // 统计查询的实现
            $field =  isset($args[0])?$args[0]:'*';
			 
            return $this->getField(strtoupper($method).'('.$field.') AS m_'.$method);

        }elseif(strtolower(substr($method,0,5))=='getby') {
            // 根据某个字段获取记录
            $field   =   $this->parseName(substr($method,5));
            $options['where'] =  $field.'=\''.$args[0].'\'';
            return $this->find($options);
        }elseif( in_array($method,array('debug','get_results' ),true)){
		
		    $rs=call_user_func_array(array($this->db, $method),$args);
		}
		
		else{
            echo $method.'不支持此方法' ;
            return;
        }
    }
	public function from($table){
	      $this->options['from']=$table;
		  //$this->_parseSql(); 
	      return $this;
	
	}
	public function select($cols){	    
		$this->options['select']=$cols;
		//$this->_parseSql(); 
		return $this;
	}

   

	public function find($options=array()) {	 
      
		if(is_numeric($options) || is_string($options)) {
				  $where  =  $this->getpk().'=\''.$options.'\'';				 
				  $this->query->where($where);
				 
        } 
		 
		$this->query->limit(1);
		$this->_parseSql(); 
		
 
		 
        $rs = $this->db->get_row($this->query);
		$this->clear();
        if(false === $rs) {
            return false;
        }
        if(empty($rs)) {// 查询结果为空
            return null;
        }       
        return $rs;

     } 
	 
	 public function findall($options=array()) {
		 
            if(is_numeric($options) || is_string($options)) {
				  $where  =  $this->getpk().' in ('.$options.')';				 
				  $this->query->where($where);
				 
            } 
			$this->_parseSql(); 
			 
			$rs = $this->db->get_results($this->query); 
			$this->clear();
			if(false === $rs) {
				return false;
			}
			if(empty($rs)) {// 查询结果为空
				return null;
			}		   
			return $rs;			 
     }
	 public function clear(){
		  $this->query->clear();	 
	 }
 
	 protected function _facade($data) {
		 
		 
        if(!empty($this->fields)) {
            foreach ($data as $key=>$val){
                if(!in_array($key,$this->fields,true)){
                    unset($data[$key]);
                }elseif(C('DB_FIELDTYPE_CHECK') && is_scalar($val)) {
                    // 字段类型检查
                    $fieldType = strtolower($this->fields['_type'][$key]);
                    if(false !== strpos($fieldType,'int')) {
                        $data[$key]   =  intval($val);
                    }elseif(false !== strpos($fieldType,'float') || false !== strpos($fieldType,'double')){
                        $data[$key]   =  floatval($val);
                    }
                }
            }
        }      
        return $data;
     }
	  

	public function add($data,$format=null) {
		$data = $this->_facade($data);			 	 
		if(!isset($this->options['from'])){            
            $this->options['from'] =$this->_table;
        }	
        $this->options['from'] =ereg_replace("^table.",$this->_prefix,$this->options['from'] );
		  
		return $this->db->insert($this->options['from'],$data,$format);			  
	}

	public function insert($data,$format=null) {
		return $this->add($data,$format);			  
	}

	public function replace($data,$options=null) {
		$data = $this->_facade($data);			 	 
		if(!isset($this->options['from'])){            
            $this->options['from'] =$this->_table;
        }	
        $this->options['from'] =ereg_replace("^table.",$this->_prefix,$this->options['from'] );
		  
		return $this->db->replace($this->options['from'],$data,$format);	 
 
	}
     //直接执行语句
	 public function query($sql)    {
        if(!empty($sql)) {
            $rs=$this->db->query($sql); 
			return $rs;
        }else{
            return false;
        }
    }

	public function save($data,$format=null,$where=null) {

		if(empty($data)) {
            // 没有传递数据，获取当前数据对象的值
            if(!empty($this->data)) {
                $data    =   $this->data;
            }else{
                $this->error = L('_DATA_TYPE_INVALID_');
                return false;
            }
        }
		//如果有主键则以主键为主;			 
		 if(isset($data[$this->getpk()])) {
				$pk   =  $this->getpk();
				$this->query->where($pk.'=\''.$data[$pk].'\'');
				$pkValue = $data[$pk];
				unset($data[$pk]);
		 }
		 
        // 数据处理
        $data = $this->_facade($data);
       
		 
		$data = $this->_facade($data);	 	
		$options= $this->options;		 
		if(!isset($options['from'])){            
            $options['from'] =$this->_table;
        }	
        $options['from'] =ereg_replace("^table.",$this->_prefix,$options['from'] );
		$where.=implode(' ', $this->query->_parts['where']);
		if($where==null){
			$where='1=1';		 
		}
		$this->clear();
		return $this->db->update($options['from'],$data,$where,$format); 
    }
	public function update($data,$format=null,$where=null) {
		$this->save($data,$format,$where);
	}

	
	//批量添加
	function addall( $datas,$format=null) {
		
			$dt=array();
			$rs=array();
			foreach($datas as $key=>$data){			 
				$rs[]=$this->add($data,$format);
			}         
			return $rs;
	}
 
	 public function delete($options=array()){
         if(is_numeric($options)  || is_string($options)) {
            // 根据主键删除记录
            $pk   =  $this->getpk();
            if(strpos($options,',')) {
                $where  =  $pk.' IN ('.$options.')';
            }else{
                $where  =  $pk.'=\''.$options.'\'';
                $pkValue = $options;
            }
			 
            $this->query->where($where);
            
        }elseif(is_array($options)&&count($options)>=1){
		
		    $pk   =  $this->getpk();		 
             $where  =  $pk.' IN ('.implode(',',$options).')';
			 $this->query->where($where);
		}
		$this->_parseSql(); 
		 
		$where=implode(' ', $this->query->_parts['where']);
	 
		$table=$this->options['from'];
		$rs=$this->db->delete($table,$where);
		$this->clear();
		return $rs;
		
    }

	 


	 public function create($data='',$type='') {
        // 如果没有传值默认取POST数据
        if(empty($data)) {			 
            $data    =   $_POST;
        }elseif(is_object($data)){
            $data   =   get_object_vars($data);
        }elseif(!is_array($data)){
            $this->error = L('_DATA_TYPE_INVALID_');
            return false;
        }      
        // 验证完成生成数据对象
        $vo   =  array();
         foreach ($this->fields as $key=>$name){
            if(substr($key,0,1)=='_') continue;
            $val = isset($data[$name])?$data[$name]:null;
			 
            //保证赋值有效
            if(!is_null($val)){
                $vo[$name] = (MAGIC_QUOTES_GPC && is_string($val))?   stripslashes($val)  :  $val;
            }
        }
       
	    
        // 赋值当前数据对象
        $this->data =   $vo;
        // 返回创建的数据以供其他调用
        return $vo;
     }

	public function getpk() {
		//dump($this->options);
		 if($this->options&&$this->options['from'] ){	
			// $options['from'] =ereg_replace("^table.",$this->_prefix,$options['from'] );
			$this->_table=  ereg_replace("^table.",$this->_prefix,$this->options['from'] ); 

			
			$this->tableInfo();
			
		 }	
		 
		 
         return  $this->fields['_pk'];
    }

 
    public function getDbFields(){
        return $this->fields;
    }



	

  
 


    /**
     +----------------------------------------------------------
     * 获取一条记录的某个字段值
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $field  字段名
     * @param mixed $condition  查询条件
     * @param string $spea  字段数据间隔符号
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function getField($field,$condition='',$sepa=' ') {
        if(empty($condition) && isset($this->options['where']))
            $condition   =  $this->options['where'];
        $options['where'] =  $condition;
        $options['select']    =  $field;
        //$options =  $this->parseOptions($options);
		  //dump($options);
		//  exit;
        if(strpos($field,',')) { // 多字段
            $resultSet = $this->findall($options);
			//dump($options);
            if(!empty($resultSet)) {
                $field  =   explode(',',$field);
                $key =  array_shift($field);
                $cols   =   array();
                foreach ($resultSet as $result){
                    $name   = $result[$key];
                    $cols[$name] =  '';
                    foreach ($field as $val)
                        $cols[$name] .=  $result[$val].$sepa;
                    $cols[$name]  = substr($cols[$name],0,-strlen($sepa));
                }
                return $cols;
            }
        }else{   // 查找一条记录
            $options['limit'] = 1;
            $result = $this->findall($options);	
			//dump(reset($result[0]));
            if(!empty($result)) {
                return reset($result[0]);
            }
        }
        return null;
    }

	
	private function _parseSql() {
        $options=$this->options;

		//dump($options);
		if(!isset($options['select'])){            
            $options['select'] ="*";
        }
		if(!isset($options['from'])){            
            $options['from'] =$this->_table;
        }	
        $options['from'] =ereg_replace("^table.",$this->_prefix,$options['from'] );
		// dump($options);
	//	$this->options=array_merge($this->options,$options);
		$this->query->selectfrom($options['from'], $options['select']);
	}
 
    /*
	取得所有的sql 语句;
	*/
    public function showsql() {
       dump($this->db->queries);
	   return true;
    }
	 public function getsql() {
       dump($this->db->last_query);
	   return true;
    }

	public function sqlinfo(){
	
	$t['total_query_time']=$this->db->total_query_time;
	$t['num_queries']=$this->db->num_queries;
	dump($t);
	
	}

	public function logsql(){
	   $rs=$this->db->queries;
	   $sql='';
	   foreach($rs as $k=>$v){
	    $sql.=$v[0]."\t\n\t\n\t\n";
	   
	   }
	   return $sql;
	
	}

	

}
  
?>