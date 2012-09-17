<?php
/*
mysql 操作类;
*/

define("OBJECT","OBJECT",true);
define("ARRAY_A","ARRAY_A",true);
define("ARRAY_N","ARRAY_N",true);

class Genv_Db_Adapter_Mysql extends Genv_Db_Adapter{  

	public  function connect($name='dev'){	
		 //$name为连接名称;
        $cfg=$this->_config[$name];//获取配置信息;
		$this->dlink=$this->dbpool[$name]=@mysql_connect( $cfg['host']. ':' .$cfg['port'],$cfg['user'], $cfg['pass']);
		if ( ! $this->dlink ){
			$this->print_error("<ol><b>Error establishing a database connection!</b><li>Are you sure you have the correct user/password?<li>Are you sure that you have typed the correct hostname?<li>Are you sure that the database server is running?</ol>");
		}
		$this->ready = true;

		if ( $this->has_cap( 'collation' ) && !empty( $cfg['charset']) ) {
			 
			if ( function_exists( 'mysql_set_charset' ) ) {
				mysql_set_charset( $cfg['charset'], $this->dlink );
				$this->real_escape = true;
			} else {
				$query = $this->prepare( 'SET NAMES %s', $cfg['charset'] );
				if ( ! empty( $this->collate ) )
					$query .= $this->prepare( ' COLLATE %s', $this->collate );
				$this->query( $query );
			}
		}
 
		$this->selectdb($cfg['name']);

	}

	function flush(){

		// Get rid of these
		$this->last_result = null;
		$this->col_info = null;
		$this->last_query = null;

	}

	public   function selectdb($db){
		if ( !@mysql_select_db($db,$this->dlink)){
			$this->print_error("<ol><b>Error selecting database <u>$db</u>!</b><li>Are you sure it exists?<li>Are you sure there is a valid database connection?</ol>");
		}
		 
	}


	// ==================================================================
	//	Basic Query	- see docs for more detail

	public  function query($query){
		
		// For reg expressions
		$query = trim($query); 
		
		// initialise return
		$return_val = 0;

		// Flush cached values..
		$this->flush();

		// Log how the function was called
		$this->func_call = "\$db->query(\"$query\")";

		// Keep track of the last query for debug..
		$this->last_query = $query;

		//if ( C('SQL_DEBUG') )
			$this->timer_start();

		// Perform the query via std mysql_query function..
		$this->result = @mysql_query($query,$this->dlink);
		$this->num_queries++;
		//$this->logs($query);

 
		$this->queries[] = array( $query.'', $this->timer_stop(), $this->get_caller() );
 
		// If there is an error then take note of it..
		if ( mysql_error() )
		{
			$this->print_error();
			return false;
		}
		
		// Query was an insert, delete, update, replace
		if ( preg_match("/^(insert|delete|update|replace)\s+/i",$query) )
		{
			$this->rows_affected = mysql_affected_rows();
			
			// Take note of the insert_id
			if ( preg_match("/^(insert|replace)\s+/i",$query) )
			{
				$this->rows_affected = mysql_insert_id($this->dlink);	
			}
			//echo $this->rows_affected;
			// Return number fo rows affected
			$return_val = $this->rows_affected;
		}
		// Query was an select
		else
		{
			
			// Take note of column info	
			$i=0;
			while ($i < @mysql_num_fields($this->result))
			{
				$this->col_info[$i] = @mysql_fetch_field($this->result);
				$i++;
			}
			
			// Store Query Results	
			$num_rows=0;
			while ( $row = @mysql_fetch_object($this->result) )
			{
				// Store relults as an objects within main array
				$this->last_result[$num_rows] = $row;
				$num_rows++;
			}

			@mysql_free_result($this->result);

			// Log number of rows the query returned
			$this->num_rows = $num_rows;
			
			// Return number of rows selected
			$return_val = $this->get_results();
		}		
 		return $return_val;

	}
	function logs($sql){
	 $config = array(
         'adapter' => 'Genv_Log_Adapter_File',
         'events'  => '*',
         'file'    => Genv_Config::get('Genv','appname').'/Data/Logs/sql.log.txt',
     );
       $log = Genv::factory('Genv_Log', $config);       
       $log->save($sql,'','');
	  }


	function get_results($query=null, $output = ARRAY_A)
	{

		// Log how the function was called
		$this->func_call = "\$db->get_results(\"$query\", $output)";

		// If there is a query then perform it if not then use cached results..
		if ( $query )
		{
			$this->query($query);
		}

		// Send back array of objects. Each row is an object
		if ( $output == OBJECT )
		{
			return $this->last_result;
		}
		elseif ( $output == ARRAY_A || $output == ARRAY_N )
		{
			if ( $this->last_result )
			{
				$i=0;
				foreach( $this->last_result as $row )
				{

					$new_array[$i] = get_object_vars($row);

					if ( $output == ARRAY_N )
					{
						$new_array[$i] = array_values($new_array[$i]);
					}

					$i++;
				}

				return $new_array;
			}
			else
			{
				return null;
			}
		}
	}

	function has_cap( $db_cap ) {
		$version = $this->db_version();

		switch ( strtolower( $db_cap ) ) {
			case 'collation' :    // @since 2.5.0
			case 'group_concat' : // @since 2.7
			case 'subqueries' :   // @since 2.7
				return version_compare( $version, '4.1', '>=' );
		};

		return false;
	}

		/**
	 * The database version number.
	 *
	 * @return false|string false on failure, version number on success
	 */
	function db_version() {
	  // dump($this->dlink);
		return preg_replace( '/[^0-9.].*/', '', mysql_get_server_info( $this->dlink ) );
	}

    public function quoteColumn($string){
        return '`' . $string . '`';
    }
/**
     * 引号转义函数
     *
     * @param string $string 需要转义的字符串
     * @return string
     */
    public function quoteValue($string)
    {
        return '\'' . str_replace(array('\'', '\\'), array('\'\'', '\\\\'), $string) . '\'';
    }

	function get_row($query=null,$output=ARRAY_A,$y=0)
	{

		// Log how the function was called
		$this->func_call = "\$db->get_row(\"$query\",$output,$y)";

		// If there is a query then perform it if not then use cached results..
		if ( $query )
		{
			$this->query($query);
		}

		// If the output is an object then return object using the row offset..
		if ( $output == OBJECT )
		{
			return $this->last_result[$y]?$this->last_result[$y]:null;
		}
		// If the output is an associative array then return row as such..
		elseif ( $output == ARRAY_A )
		{
			return $this->last_result[$y]?get_object_vars($this->last_result[$y]):null;
		}
		// If the output is an numerical array then return row as such..
		elseif ( $output == ARRAY_N )
		{
			return $this->last_result[$y]?array_values(get_object_vars($this->last_result[$y])):null;
		}
		// If invalid output type was specified..
		else
		{
			$this->print_error(" \$db->get_row(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N");
		}

	}

	 public function getFields($tableName) {
		$result=$this->get_results('SHOW COLUMNS FROM '.$tableName);
	 
					
        $info   =   array();
        if($result) {
			 
            foreach ($result as $key => $val) {
				 
                $info[$val['Field']] = array(
                    'name'    => $val['Field'],
                    'type'    => $val['Type'],
                    'notnull' => (bool) ($val['Null'] === ''), // not null is empty, null is yes
                    'default' => $val['Default'],
                    'primary' => (strtolower($val['Key']) == 'pri'),
                    'autoinc' => (strtolower($val['Extra']) == 'auto_increment'),
                );
            }
        }
		 
        return $info;
    }

 
    public function getTables($dbName='') {
        if(!empty($dbName)) {
           $sql    = 'SHOW TABLES FROM '.$dbName;
        }else{
           $sql    = 'SHOW TABLES ';
        }
        $result =   $this->query($sql);
        $info   =   array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

}
?>