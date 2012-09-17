<?php

/**********************************************************************
*  Author: Justin Vincent (jv@jvmultimedia.com)
*  Web...: http://twitter.com/justinvincent
*  Name..: ezSQL_mysql
*  Desc..: mySQL component (part of ezSQL databse abstraction library)
*
*/

/**********************************************************************
*  ezSQL error strings - mySQL
*/

$ezsql_mysql_str = array
(
	1 => 'Require $dbuser and $dbpassword to connect to a database server',
	2 => 'Error establishing mySQL database connection. Correct user/password? Correct hostname? Database server running?',
	3 => 'Require $dbname to select a database',
	4 => 'mySQL database connection is not active',
	5 => 'Unexpected error while trying to select database'
);

/**********************************************************************
*  ezSQL Database specific class - mySQL
*/

if ( ! function_exists ('mysql_connect') ) die('<b>Fatal Error:</b> ezSQL_mysql requires mySQL Lib to be compiled and or linked in to the PHP engine');
	
class Genv_Db_Adapter_Mysql extends Genv_Db_Adapter{  	

		var $dbuser = false;
		var $dbpassword = false;
		var $dbname = false;
		var $dbhost = false;
		var $dbport =3306;
		var $dbcharset =false;
		
		function config($dbconfig='dev'){
			register_shutdown_function( array( &$this, '__destruct' ) );

			
			$cfg=$this->_config[$dbconfig];//获取配置信息;

			//dump($cfg);
			$this->init_charset($cfg['charset']);			 
			$this->dbuser =  $cfg['user'];
			$this->dbpassword = $cfg['pass'];
			$this->dbname = $cfg['name'];
			$this->dbhost = $cfg['host'];
			$this->dbport = $cfg['port'];
			 
		}
		/**
		 * PHP5 style destructor and will run when database object is destroyed.
		 *
		 * @see wpdb::__construct()
		 * @since 2.0.8
		 * @return bool true
		 */
		function __destruct() {
			return true;
		}

		/**
		 * Set $this->charset and $this->collate
		 *
		 * @since 3.1.0
		 */
		function init_charset($charset) {
			if ( function_exists('is_multisite') && is_multisite() ) {
				$this->dbcharset = 'utf8';
				if ( defined( 'DB_COLLATE' ) && DB_COLLATE )
					$this->collate = DB_COLLATE;
				else
					$this->collate = 'utf8_general_ci';
			} elseif ( defined( 'DB_COLLATE' ) ) {
				$this->collate = DB_COLLATE;
			}

			if ( $charset )
				$this->dbcharset = $charset;
		}

 
		/**********************************************************************
		*  Try to connect to mySQL database server
		*/

		function connect($dbconfig){
			global $ezsql_mysql_str; $return_val = false;
			$this->config($dbconfig);
			// Keep track of how long the DB takes to connect
			$this->timer_start('db_connect_time');

			// Must have a user and a password
			if ( ! $this->dbuser )
			{
				$this->register_error($ezsql_mysql_str[1].' in '.__FILE__.' on line '.__LINE__);
				$this->show_errors ? trigger_error($ezsql_mysql_str[1],E_USER_WARNING) : null;
			}
			// Try to establish the server database handle
			else if ( ! $this->dbh = @mysql_connect($this->dbhost. ':' .$this->port,$this->dbuser,$this->dbpassword,true,131074) )
			{
				$this->register_error($ezsql_mysql_str[2].' in '.__FILE__.' on line '.__LINE__);
				$this->show_errors ? trigger_error($ezsql_mysql_str[2],E_USER_WARNING) : null;
			}
			else
			{
				if ( $this->has_cap( 'collation' ) && !empty( $this->dbcharset ) ) {
			 
					if ( function_exists( 'mysql_set_charset' ) ) {
							mysql_set_charset( $this->dbcharset, $this->dbh );
							
					} else {
						$query = $this->prepare( 'SET NAMES %s', $this->dbcharset );
						if ( ! empty( $this->collate ) )
							$query .= $this->prepare( ' COLLATE %s', $this->collate );
						$this->query( $query );
					}
				}
				if ( ! $this->select($this->dbname) ) ;
				else $return_val = true;
				 
			}

			return $return_val;
		}

		/**********************************************************************
		*  Try to select a mySQL database
		*/

		function select($dbname=''){
			global $ezsql_mysql_str; $return_val = false;

			// Must have a database name
			if ( ! $dbname )
			{
				$this->register_error($ezsql_mysql_str[3].' in '.__FILE__.' on line '.__LINE__);
				$this->show_errors ? trigger_error($ezsql_mysql_str[3],E_USER_WARNING) : null;
			}

			// Must have an active database connection
			else if ( ! $this->dbh )
			{
				$this->register_error($ezsql_mysql_str[4].' in '.__FILE__.' on line '.__LINE__);
				$this->show_errors ? trigger_error($ezsql_mysql_str[4],E_USER_WARNING) : null;
			}

			// Try to connect to the database
			else if ( !@mysql_select_db($dbname,$this->dbh) )
			{
				// Try to get error supplied by mysql if not use our own
				if ( !$str = @mysql_error($this->dbh))
					  $str = $ezsql_mysql_str[5];

				$this->register_error($str.' in '.__FILE__.' on line '.__LINE__);
				$this->show_errors ? trigger_error($str,E_USER_WARNING) : null;
			}
			else
			{
				$this->dbname = $dbname;
				$return_val = true;
			}

			return $return_val;
		}

		/**********************************************************************
		*  Format a mySQL string correctly for safe mySQL insert
		*  (no mater if magic quotes are on or not)
		*/

		

		/**********************************************************************
		*  Return mySQL specific system date syntax
		*  i.e. Oracle: SYSDATE Mysql: NOW()
		*/

		function sysdate()
		{
			return 'NOW()';
		}

		/**********************************************************************
		*  Perform mySQL query and try to detirmin result value
		*/

		function query($query)
		{

			// This keeps the connection alive for very long running scripts
			if ( $this->num_queries >= 500 )
			{
				$this->disconnect();
				$this->quick_connect($this->dbuser,$this->dbpassword,$this->dbname,$this->dbhost);
			}

			// Initialise return
			$return_val = 0;

			// Flush cached values..
			$this->flush();

			// For reg expressions
			$query = trim($query);

			// Log how the function was called
			$this->func_call = "\$db->query(\"$query\")";

			// Keep track of the last query for debug..
			$this->last_query = $query;

			// Count how many queries there have been
			$this->num_queries++;
			
			// Start timer
			$this->timer_start($this->num_queries);
			
			// Use core file cache function
			if ( $cache = $this->get_cache($query) )
			{
				// Keep tack of how long all queries have taken
				$this->timer_update_global($this->num_queries);

				// Trace all queries
				if ( $this->use_trace_log )
				{
					$this->trace_log[] = $this->debug(false);
				}
				
				return $cache;
			}

			// If there is no existing database connection then try to connect
			if ( ! isset($this->dbh) || ! $this->dbh )
			{
				$this->connect($this->dbuser, $this->dbpassword, $this->dbhost);
				$this->select($this->dbname);
			}

			// Perform the query via std mysql_query function..
		    $this->result = @mysql_query($query,$this->dbh);
           
		   $this->queries[] = array( $query.'', $this->timer_elapsed($this->num_queries), $this->get_caller() );
			// If there is an error then take note of it..
			if ( $str = @mysql_error($this->dbh) )
			{
				$is_insert = true;
				$this->register_error($str);
				$this->show_errors ? trigger_error($str,E_USER_WARNING) : null;
				return false;
			}

			// Query was an insert, delete, update, replace
			$is_insert = false;
			if ( preg_match("/^(insert|delete|update|replace|truncate|drop|create|alter)\s+/i",$query) )
			{
				$this->rows_affected = @mysql_affected_rows($this->dbh);

				// Take note of the insert_id
				if ( preg_match("/^(insert|replace)\s+/i",$query) )
				{
					$this->rows_affected=$this->insert_id = @mysql_insert_id($this->dbh);
				}


				// Return number fo rows affected
				$return_val = $this->rows_affected;



			}
			// Query was a select
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
				$return_val = $this->num_rows;
			}

			// disk caching of queries
			$this->store_cache($query,$is_insert);

			// If debug ALL queries
			$this->trace || $this->debug_all ? $this->debug() : null ;

			// Keep tack of how long all queries have taken
			$this->timer_update_global($this->num_queries);

			// Trace all queries
			if ( $this->use_trace_log )
			{
				$this->trace_log[] = $this->debug(false);
			}

			return $return_val;

		}
		
		/**********************************************************************
		*  Close the active mySQL connection
		*/

		function disconnect()
		{
			@mysql_close($this->dbh);	
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

		 /*function escape($str)
		{
			// If there is no existing database connection then try to connect
			if ( ! isset($this->dbh) || ! $this->dbh )
			{
				$this->connect($this->dbuser, $this->dbpassword, $this->dbhost);
				$this->select($this->dbname);
			}

			return mysql_real_escape_string(stripslashes($str));
		}*/

		function _weak_escape( $string ) {
			return addslashes( $string );
		}

	/**
	 * Real escape, using mysql_real_escape_string() or addslashes()
	 *
	 * @see mysql_real_escape_string()
	 * @see addslashes()
	 * @since 2.8.0
	 * @access private
	 *
	 * @param  string $string to escape
	 * @return string escaped
	 */
	function _real_escape( $string ) {

		if ( ! isset($this->dbh) || ! $this->dbh )
			{
				$this->connect($this->dbuser, $this->dbpassword, $this->dbhost);
				$this->select($this->dbname);
			}

		return mysql_real_escape_string(stripslashes($string));
		/*if ( $this->dbh && $this->real_escape )
			return mysql_real_escape_string( $string, $this->dbh );
		else
			return addslashes( $string );
	    */
	}

	/**
	 * Escape data. Works on arrays.
	 *
	 * @uses wpdb::_escape()
	 * @uses wpdb::_real_escape()
	 * @since  2.8.0
	 * @access private
	 *
	 * @param  string|array $data
	 * @return string|array escaped
	 */
	function _escape( $data ) {
		if ( is_array( $data ) ) {
			foreach ( (array) $data as $k => $v ) {
				if ( is_array($v) )
					$data[$k] = $this->_escape( $v );
				else
					$data[$k] = $this->_real_escape( $v );
			}
		} else {
			$data = $this->_real_escape( $data );
		}

		return $data;
	}

	/**
	 * Escapes content for insertion into the database using addslashes(), for security.
	 *
	 * Works on arrays.
	 *
	 * @since 0.71
	 * @param string|array $data to escape
	 * @return string|array escaped as query safe string
	 */
	function escape( $data ) {
		if ( is_array( $data ) ) {
			foreach ( (array) $data as $k => $v ) {
				if ( is_array( $v ) )
					$data[$k] = $this->escape( $v );
				else
					$data[$k] = $this->_weak_escape( $v );
			}
		} else {
			$data = $this->_weak_escape( $data );
		}

		return $data;
	}

	/**
	 * Escapes content by reference for insertion into the database, for security
	 *
	 * @uses wpdb::_real_escape()
	 * @since 2.3.0
	 * @param string $string to escape
	 * @return void
	 */
	function escape_by_ref( &$string ) {
		$string = $this->_real_escape( $string );
	}


		function prepare( $query = null ) { // ( $query, *$args )
			if ( is_null( $query ) )
				return;

			$args = func_get_args();
			array_shift( $args );
			// If args were passed as an array (as in vsprintf), move them up
			if ( isset( $args[0] ) && is_array($args[0]) )
				$args = $args[0];
			$query = str_replace( "'%s'", '%s', $query ); // in case someone mistakenly already singlequoted it
			$query = str_replace( '"%s"', '%s', $query ); // doublequote unquoting
			$query = preg_replace( '|(?<!%)%s|', "'%s'", $query ); // quote the strings, avoiding escaped strings like %%s
			array_walk( $args, array( &$this, 'escape_by_ref' ) );
			return @vsprintf( $query, $args );
		}

		function db_version() {
	  
			return preg_replace( '/[^0-9.].*/', '', mysql_get_server_info( $this->dbh ) );
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

	 
   
	


	 public function quoteColumn($string){
        return '`' . $string . '`';
    }
 
    public function quoteValue($string)
    {
        return '\'' . str_replace(array('\'', '\\'), array('\'\'', '\\\\'), $string) . '\'';
    }




}