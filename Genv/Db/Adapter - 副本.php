<?php

/**
 * Genv数据库适配器
 * 定义通用的数据库适配接口
 *
 * @package Db
 */
abstract class Genv_Db_Adapter extends Genv_Base {    
   
    protected $_Genv_Sql_Adapter = array(
        'host'      => null,
        'port'      => null,
        'sock'      => null,
        'user'      => null,
        'pass'      => null,
        'name'      => null,
        'profiling' => false,
        'cache'     => array('adapter' => 'Genv_Cache_Adapter_Var'),
    );  
	public $dbpool=array();// db缓存池;
	public $dlink=null;//当前连接
	protected $_ident_quote_prefix = null;
	protected $_ident_quote_suffix = null;  
	protected function _postConstruct(){
		 
		
        parent::_postConstruct();        
        $this->_pool = array();
        $this->_connectedPool = array();       
        $this->_setup();
    }

	protected function _setup(){
       
    }
	public function print_error(){
	
	}

	 public function quoteInto($text, $data){
        // how many question marks are there?
        $count = substr_count($text, '?');
        if (! $count) {
            // no replacements needed
            return $text;
        }
        
        // only one replacement?
        if ($count == 1) {
            $data = $this->quote($data);
            $text = str_replace('?', $data, $text);
            return $text;
        }
        
        // more than one replacement; force values to be an array, then make 
        // sure we have enough values to replace all the placeholders.
        settype($data, 'array');
        if (count($data) < $count) {
            // more placeholders than values
            throw $this->_exception('ERR_NOT_ENOUGH_VALUES', array(
                'text'  => $text,
                'data'  => $data,
            ));
        }
        
        // replace each placeholder with a quoted value
        $offset = 0;
        foreach ($data as $val) {
            // find the next placeholder
            $pos = strpos($text, '?', $offset);
            if ($pos === false) {
                // no more placeholders, exit the data loop
                break;
            }
            
            // replace this question mark with a quoted value
            $val  = $this->quote($val);
            $text = substr_replace($text, $val, $pos, 1);
            
            // update the offset to move us past the quoted value
            $offset = $pos + strlen($val);
        }
        
        return $text;
    }
	public function quoteName($spec)
    {
        if (is_array($spec)) {
            foreach ($spec as $key => $val) {
                $spec[$key] = $this->quoteName($val);
            }
            return $spec;
        }
        
        // no extraneous spaces
        $spec = trim($spec);
        
        // `original` AS `alias` ... note the 'rr' in strripos
        $pos = strripos($spec, ' AS ');
        if ($pos) {
            // recurse to allow for "table.col"
            $orig  = $this->quoteName(substr($spec, 0, $pos));
            // use as-is
            $alias = $this->_quoteName(substr($spec, $pos + 4));
            return "$orig AS $alias";
        }
        
        // `original` `alias`
        $pos = strrpos($spec, ' ');
        if ($pos) {
            // recurse to allow for "table.col"
            $orig = $this->quoteName(substr($spec, 0, $pos));
            // use as-is
            $alias = $this->_quoteName(substr($spec, $pos + 1));
            return "$orig $alias";
        }
        
        // `table`.`column`
        $pos = strrpos($spec, '.');
        if ($pos) {
            // use both as-is
            $table = $this->_quoteName(substr($spec, 0, $pos));
            $col   = $this->_quoteName(substr($spec, $pos + 1));
            return "$table.$col";
        }
        
        // `name`
        return $this->_quoteName($spec);
    }
    
 
    protected function _quoteName($name)
    {
        $name = trim($name);
        if ($name == '*') {
            return $name;
        } else {
            return $this->_ident_quote_prefix
                 . $name
                 . $this->_ident_quote_suffix;
        }
    }
    
    public function quoteNamesIn($spec)
    {
        if (is_array($spec)) {
            foreach ($spec as $key => $val) {
                $spec[$key] = $this->quoteNamesIn($val);
            }
            return $spec;
        }
        
        // single and double quotes
        $apos = "'";
        $quot = '"';
        
        // look for ', ", \', or \" in the string.
        // match closing quotes against the same number of opening quotes.
        $list = preg_split(
            "/(($apos+|$quot+|\\$apos+|\\$quot+).*?\\2)/",
            $spec,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );
        
        // concat the pieces back together, quoting names as we go.
        $spec = null;
        $last = count($list) - 1;
        foreach ($list as $key => $val) {
            
            // skip elements 2, 5, 8, 11, etc. as artifacts of the back-
            // referenced split; these are the trailing/ending quote
            // portions, and already included in the previous element.
            // this is the same as every third element from zero.
            if (($key+1) % 3 == 0) {
                continue;
            }
            
            // is there an apos or quot anywhere in the part?
            $is_string = strpos($val, $apos) !== false ||
                         strpos($val, $quot) !== false;
            
            if ($is_string) {
                // string literal
                $spec .= $val;
            } else {
                // sql language.
                // look for an AS alias if this is the last element.
                if ($key == $last) {
                    // note the 'rr' in strripos
                    $pos = strripos($val, ' AS ');
                    if ($pos) {
                        // quote the alias name directly
                        $alias = $this->_quoteName(substr($val, $pos + 4));
                        $val = substr($val, 0, $pos) . " AS $alias";
                    }
                }
                
                // now quote names in the language.
                $spec .= $this->_quoteNamesIn($val);
            }
        }
        
        // done!
        return $spec;
    }
    
  
    protected function _quoteNamesIn($text)
    {
        $word = "[a-z_][a-z0-9_]+";
        
        $find = "/(\\b)($word)\\.($word)(\\b)/i";
        
        $repl = '$1'
              . $this->_ident_quote_prefix
              . '$2'
              . $this->_ident_quote_suffix
              . '.'
              . $this->_ident_quote_prefix
              . '$3'
              . $this->_ident_quote_suffix
              . '$4'
              ;
              
        $text = preg_replace($find, $repl, $text);
        
        return $text;
    }

    public function fetchSql($spec,$data=array()){
        // build the statement from its component parts if needed
        if (is_array($spec)) {
			// dump($spec);
			// echo $this->_select($spec,$data);
            return $this->_select($spec,$data);
        } else {
			 
            return $spec;
        }
    }
	  public function fetchValue($spec, $data = array())
    {
        if (is_array($spec)) {
            // automatically limit to the first row only,
            // but leave the offset alone.
            $spec['limit']['count'] = 1;
        }
        $sql= $this->fetchSql($spec,$data);
	  
		$result = $this->get_row($sql);

		 
		 
		 
		return $result['genvid'];
       // return $result->fetchColumn(0);
    }
    
  
    protected function _select($parts,$data){
        // buid the statment
        if (empty($parts['compound'])) {
            $stmt = $this->_selectSingle($parts);
        } else {
            $stmt = $this->_selectCompound($parts);
        }
         
        // modify per adapter
        $this->_modSelect($stmt, $parts);
        foreach($data as $key=>$v){
		 
		 $stmt=str_replace(":$key",$this->quoteValue($v), $stmt);
		}
		//dump($stmt);
		
		 if($data){
		 
			$stmt=vsprintf($stmt, array_map(array($this, 'quoteValue'), $data));
		 }
        return $stmt;
    }
    
   
    protected function _selectSingle($parts)
    {
        $default = array(
            'distinct' => null,
            'cols'     => array(),
            'from'     => array(),
            'join'     => array(),
            'where'    => array(),
            'group'    => array(),
            'having'   => array(),
            'order'    => array(),
        );
        
        $parts = array_merge($default, $parts);
        
        // is this a SELECT or SELECT DISTINCT?
        if ($parts['distinct']) {
            $stmt = "SELECT DISTINCT\n    ";
        } else {
            $stmt = "SELECT\n    ";
        }
        
        // add columns
        $stmt .= implode(",\n    ", $parts['cols']) . "\n";
        
        // from these tables
        $stmt .= $this->_selectSingleFrom($parts['from']);
        
        // joined to these tables
        if ($parts['join']) {
            $list = array();
            foreach ($parts['join'] as $join) {
                $tmp = '';
                // add the type (LEFT, INNER, etc)
                if (! empty($join['type'])) {
                    $tmp .= $join['type'] . ' ';
                }
                // add the table name and condition
                $tmp .= 'JOIN ' . $join['name'];
                $tmp .= ' ON ' . $join['cond'];
                // add to the list
                $list[] = $tmp;
            }
            // add the list of all joins
            $stmt .= implode("\n", $list) . "\n";
        }
        
        // with these where conditions
        if ($parts['where']) {
            $stmt .= "WHERE\n    ";
            $stmt .= implode("\n    ", $parts['where']) . "\n";
        }
        
        // grouped by these columns
        if ($parts['group']) {
            $stmt .= "GROUP BY\n    ";
            $stmt .= implode(",\n    ", $parts['group']) . "\n";
        }
        
        // having these conditions
        if ($parts['having']) {
            $stmt .= "HAVING\n    ";
            $stmt .= implode("\n    ", $parts['having']) . "\n";
        }
        
        // ordered by these columns
        if ($parts['order']) {
            $stmt .= "ORDER BY\n    ";
            $stmt .= implode(",\n    ", $parts['order']) . "\n";
        }
        
        // done!
        return $stmt;
    }
    
     protected function _selectSingleFrom($from){
        return "FROM\n    "
             . implode(",\n    ", $from)
             . "\n";
    }
    
  
    protected function _selectCompound($parts)
    {
        // the select statement to build up
        $stmt = '';
        
        // default parts of each 'compound' element
        $default = array(
            'type' => null, // 'UNION', 'UNION ALL', etc.
            'spec' => null, // array or string for the SELECT statement
        );
        
        // combine the compound elements
        foreach ((array) $parts['compound'] as $compound) {
            
            // make sure we have the default elements
            $compound = array_merge($default, $compound);
            
            // is it an array of select parts?
            if (is_array($compound['spec'])) {
                // yes, build a select string from them
                $select = $this->_select($compound['spec']);
            } else {
                // no, assume it's already a select string
                $select = $compound['spec'];
            }
            
            // do we need to add the compound type?
            // note that the first compound type will be ignored.
            if ($stmt) {
                $stmt .= strtoupper($compound['type']) . "\n";
            }
            
            // now add the select itself
            $stmt .= "(" . $select . ")\n";
        }
        
        // add any overall order
        if (! empty($parts['order'])) {
            $stmt .= "ORDER BY\n    ";
            $stmt .= implode(",\n    ", $parts['order']) . "\n";
        }
        
        // done!
        return $stmt;
    }
  
    protected function _modSelect(&$stmt, &$parts){
        // determine count
        $count = ! empty($parts['limit']['count'])
            ? (int) $parts['limit']['count']
            : 0;
        
        // determine offset
        $offset = ! empty($parts['limit']['offset'])
            ? (int) $parts['limit']['offset']
            : 0;
      
        // add the count and offset
        if ($count > 0) {
            $stmt .= "LIMIT $count";
            if ($offset > 0) {
                $stmt .= " OFFSET $offset";
            }
        }
    }
     public function insert($table, $data,$options){
        // the base statement
        $table = $this->quoteName($table);
		$sql=$this->formatsql($data,$options);
		//echo $sql;
		//dump($data);
        $stmt = "INSERT INTO $table SET " 
         .$sql;
		// echo $stmt;

        $result = $this->query($stmt);
        return $result;
    }    
	    /**
     * 指定需要写入的栏目及其值
     *
     * @param array $rows
     * @return Mdb_Query
     */
    public function rows(array $rows,array $options)
    {
        foreach ($rows as $key => $row) {
            $this->_sqlPreBuild['rows'][$this->filterColumn($key)] = is_null($row) ? 'NULL' : $this->quoteValue($row);
        }
		$this->_sqlPreBuild['rows_sql']=$this->formatsql($rows,$options);
        return $this;
    }

	/**
	 * 过滤字串
	 * @param $str string 要过滤的字串
	 * @return string
	 */
	function escape($str) {
		return mysql_escape_string($str);
	}


	public function formatsql($data,$format=null){	
		
		$keys = array();
		$values = array();
		foreach ($data as $key => $value) {
			$keys[] = '`' . $this->escape($key). '`=%s';
			$values[] = '"' . $this->escape($value) . '"';
		}			
		if (sizeof($keys) != sizeof($values)) {
			return false;
		}	 	
		$sql=implode(',',$keys);
		$sql = vsprintf($sql, $values);		 
		return $sql;	
	}
	 

   
    public function update($table, $data, $options){
        // the base statement
        $table = $this->quoteName($table);
        $stmt = "UPDATE $table SET ";
        $table = $this->quoteName($table);
		$stmt.=$this->formatsql($data,$options);
		 
		$stmt.=' where '.$options['where'];
	 

        $result = $this->query($stmt);
        return $result;
    }    

    public function delete($table, $where){
         
        $table = $this->quoteName($table);
		//$where=$options['where'];
		
	 
        $result = $this->query("DELETE FROM $table WHERE $where");
        return $result;
    }
   
	
    function timer_start() {
		$mtime            = explode( ' ', microtime() );
		$this->time_start = $mtime[1] + $mtime[0];
		return true;
	}

	 function timer_stop() {
		$mtime      = explode( ' ', microtime() );
		$time_end   = $mtime[1] + $mtime[0];
		$time_total = $time_end - $this->time_start;
		return $time_total;
	}
	/**
	 * Retrieve the name of the function that called wpdb.
	 *
	 * Searches up the list of functions until it reaches
	 * the one that would most logically had called this method.
	 *
	 * @since 2.5.0
	 *
	 * @return string The name of the calling function
	 */
	function get_caller() {
		$trace  = array_reverse( debug_backtrace() );
		$caller = array();

		foreach ( $trace as $call ) {
			if ( isset( $call['class'] ) && __CLASS__ == $call['class'] )
				continue; // Filter out wpdb calls.
			$caller[] = isset( $call['class'] ) ? "{$call['class']}->{$call['function']}" : $call['function'];
		}

		return join( ', ', $caller );
	}
   
}
?>