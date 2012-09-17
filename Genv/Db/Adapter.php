<?php

/**
 * Genv数据库适配器
 * 定义通用的数据库适配接口
 * 改编自 ezsql;
 *  
 */

define('EZSQL_VERSION','2.17');
define('OBJECT','OBJECT',true);
define('ARRAY_A','ARRAY_A',true);
define('ARRAY_N','ARRAY_N',true);
define('DB_COLLATE', '');

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

		var $trace            = false;  // same as $debug_all
		var $debug_all        = false;  // same as $trace
		var $debug_called     = false;
		var $vardump_called   = false;
		var $show_errors      = true;
		var $num_queries      = 0;	
		var $last_query       = null;
		var $queries			  = array();
		var $last_error       = null;
		var $col_info         = null;
		var $captured_errors  = array();
		var $cache_dir        = false;
		var $cache_queries    = false;
		var $cache_inserts    = false;
		var $use_disk_cache   = false;
		var $cache_timeout    = 24; // hours
		var $timers           = array();
		var $total_query_time = 0;
		var $db_connect_time  = 0;
		var $trace_log        = array();
		var $use_trace_log    = false;
		var $sql_log_file     = false;
		var $do_profile       = false;
		var $profile_times    = array();
		// == TJH == default now needed for echo of debug function
		var $debug_echo_is_on = true;
		var $field_types = array();

		 
		protected $_ident_quote_prefix = null;
		protected $_ident_quote_suffix = null;  
	  
		protected function _postConstruct(){
			parent::_postConstruct();       		       
			 
		} 
		

		/**********************************************************************
		*  Print SQL/DB error - over-ridden by specific DB class
		*/

		function register_error($err_str)
		{
			// Keep track of last error
			$this->last_error = $err_str;

			// Capture all errors to an error array no matter what happens
			$this->captured_errors[] = array
			(
				'error_str' => $err_str,
				'query'     => $this->last_query
			);
		}

		/**********************************************************************
		*  Turn error handling on or off..
		*/

		function show_errors()
		{
			$this->show_errors = true;
		}

		function hide_errors()
		{
			$this->show_errors = false;
		}

		/**********************************************************************
		*  Kill cached query results
		*/

		function flush()
		{
			// Get rid of these
			$this->last_result = null;
			$this->col_info = null;
			$this->last_query = null;
			$this->from_disk_cache = false;
		}

		/**********************************************************************
		*  Get one variable from the DB - see docs for more detail
		*/

		function get_var($query=null,$x=0,$y=0)
		{

			// Log how the function was called
			$this->func_call = "\$db->get_var(\"$query\",$x,$y)";

			// If there is a query then perform it if not then use cached results..
			if ( $query )
			{
				$this->query($query);
			}

			// Extract var out of cached results based x,y vals
			if ( $this->last_result[$y] )
			{
				$values = array_values(get_object_vars($this->last_result[$y]));
			}

			// If there is a value return it else return null
			return (isset($values[$x]) && $values[$x]!=='')?$values[$x]:null;
		}

		/**********************************************************************
		*  Get one row from the DB - see docs for more detail
		*/

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

		/**********************************************************************
		*  Function to get 1 column from the cached result set based in X index
		*  see docs for usage and info
		*/

		function get_col($query=null,$x=0)
		{

			$new_array = array();

			// If there is a query then perform it if not then use cached results..
			if ( $query )
			{
				$this->query($query);
			}

			// Extract the column values
			for ( $i=0; $i < count($this->last_result); $i++ )
			{
				$new_array[$i] = $this->get_var(null,$x,$i);
			}

			return $new_array;
		}


		/**********************************************************************
		*  Return the the query as a result set - see docs for more details
		*/

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


		/**********************************************************************
		*  Function to get column meta data info pertaining to the last query
		* see docs for more info and usage
		*/

		function get_col_info($info_type="name",$col_offset=-1)
		{

			if ( $this->col_info )
			{
				if ( $col_offset == -1 )
				{
					$i=0;
					foreach($this->col_info as $col )
					{
						$new_array[$i] = $col->{$info_type};
						$i++;
					}
					return $new_array;
				}
				else
				{
					return $this->col_info[$col_offset]->{$info_type};
				}

			}

		}

		/**********************************************************************
		*  store_cache
		*/

		function store_cache($query,$is_insert)
		{

			// The would be cache file for this query
			$cache_file = $this->cache_dir.'/'.md5($query);

			// disk caching of queries
			if ( $this->use_disk_cache && ( $this->cache_queries && ! $is_insert ) || ( $this->cache_inserts && $is_insert ))
			{
				if ( ! is_dir($this->cache_dir) )
				{
					$this->register_error("Could not open cache dir: $this->cache_dir");
					$this->show_errors ? trigger_error("Could not open cache dir: $this->cache_dir",E_USER_WARNING) : null;
				}
				else
				{
					// Cache all result values
					$result_cache = array
					(
						'col_info' => $this->col_info,
						'last_result' => $this->last_result,
						'num_rows' => $this->num_rows,
						'return_value' => $this->num_rows,
					);
					error_log ( serialize($result_cache), 3, $cache_file);
				}
			}

		}

		/**********************************************************************
		*  get_cache
		*/

		function get_cache($query)
		{

			// The would be cache file for this query
			$cache_file = $this->cache_dir.'/'.md5($query);

			// Try to get previously cached version
			if ( $this->use_disk_cache && file_exists($cache_file) )
			{
				// Only use this cache file if less than 'cache_timeout' (hours)
				if ( (time() - filemtime($cache_file)) > ($this->cache_timeout*3600) )
				{
					unlink($cache_file);
				}
				else
				{
					$result_cache = unserialize(file_get_contents($cache_file));

					$this->col_info = $result_cache['col_info'];
					$this->last_result = $result_cache['last_result'];
					$this->num_rows = $result_cache['num_rows'];

					$this->from_disk_cache = true;

					// If debug ALL queries
					$this->trace || $this->debug_all ? $this->debug() : null ;

					return $result_cache['return_value'];
				}
			}

		}

		/**********************************************************************
		*  Dumps the contents of any input variable to screen in a nicely
		*  formatted and easy to understand way - any type: Object, Var or Array
		*/

		function vardump($mixed='')
		{

			// Start outup buffering
			ob_start();

			echo "<p><table><tr><td bgcolor=ffffff><blockquote><font color=000090>";
			echo "<pre><font face=arial>";

			if ( ! $this->vardump_called )
			{
				echo "<font color=800080><b>ezSQL</b> (v".EZSQL_VERSION.") <b>Variable Dump..</b></font>\n\n";
			}

			$var_type = gettype ($mixed);
			print_r(($mixed?$mixed:"<font color=red>No Value / False</font>"));
			echo "\n\n<b>Type:</b> " . ucfirst($var_type) . "\n";
			echo "<b>Last Query</b> [$this->num_queries]<b>:</b> ".($this->last_query?$this->last_query:"NULL")."\n";
			echo "<b>Last Function Call:</b> " . ($this->func_call?$this->func_call:"None")."\n";
			echo "<b>Last Rows Returned:</b> ".count($this->last_result)."\n";
			echo "</font></pre></font></blockquote></td></tr></table>";
			echo "\n<hr size=1 noshade color=dddddd>";

			// Stop output buffering and capture debug HTML
			$html = ob_get_contents();
			ob_end_clean();

			// Only echo output if it is turned on
			if ( $this->debug_echo_is_on )
			{
				echo $html;
			}

			$this->vardump_called = true;

			return $html;

		}

		/**********************************************************************
		*  Alias for the above function
		*/

		function dumpvar($mixed)
		{
			$this->vardump($mixed);
		}

		/**********************************************************************
		*  Displays the last query string that was sent to the database & a
		* table listing results (if there were any).
		* (abstracted into a seperate file to save server overhead).
		*/

		function debug($print_to_screen=true)
		{

			// Start outup buffering
			ob_start();

			echo "<blockquote>";

			// Only show ezSQL credits once..
			if ( ! $this->debug_called )
			{
				echo "<font color=800080 face=arial size=2><b>ezSQL</b> (v".EZSQL_VERSION.") <b>Debug..</b></font><p>\n";
			}

			if ( $this->last_error )
			{
				echo "<font face=arial size=2 color=000099><b>Last Error --</b> [<font color=000000><b>$this->last_error</b></font>]<p>";
			}

			if ( $this->from_disk_cache )
			{
				echo "<font face=arial size=2 color=000099><b>Results retrieved from disk cache</b></font><p>";
			}

			echo "<font face=arial size=2 color=000099><b>Query</b> [$this->num_queries] <b>--</b> ";
			echo "[<font color=000000><b>$this->last_query</b></font>]</font><p>";

				echo "<font face=arial size=2 color=000099><b>Query Result..</b></font>";
				echo "<blockquote>";

			if ( $this->col_info )
			{

				// =====================================================
				// Results top rows

				echo "<table cellpadding=5 cellspacing=1 bgcolor=555555>";
				echo "<tr bgcolor=eeeeee><td nowrap valign=bottom><font color=555599 face=arial size=2><b>(row)</b></font></td>";


				for ( $i=0; $i < count($this->col_info); $i++ )
				{
					echo "<td nowrap align=left valign=top><font size=1 color=555599 face=arial>{$this->col_info[$i]->type} {$this->col_info[$i]->max_length}</font><br><span style='font-family: arial; font-size: 10pt; font-weight: bold;'>{$this->col_info[$i]->name}</span></td>";
				}

				echo "</tr>";

				// ======================================================
				// print main results

			if ( $this->last_result )
			{

				$i=0;
				foreach ( $this->get_results(null,ARRAY_N) as $one_row )
				{
					$i++;
					echo "<tr bgcolor=ffffff><td bgcolor=eeeeee nowrap align=middle><font size=2 color=555599 face=arial>$i</font></td>";

					foreach ( $one_row as $item )
					{
						echo "<td nowrap><font face=arial size=2>$item</font></td>";
					}

					echo "</tr>";
				}

			} // if last result
			else
			{
				echo "<tr bgcolor=ffffff><td colspan=".(count($this->col_info)+1)."><font face=arial size=2>No Results</font></td></tr>";
			}

			echo "</table>";

			} // if col_info
			else
			{
				echo "<font face=arial size=2>No Results</font>";
			}

			echo "</blockquote></blockquote> <hr noshade color=dddddd size=1>";

			// Stop output buffering and capture debug HTML
			$html = ob_get_contents();
			ob_end_clean();

			// Only echo output if it is turned on
			if ( $this->debug_echo_is_on && $print_to_screen)
			{
				echo $html;
			}

			$this->debug_called = true;

			return $html;

		} 
		/**********************************************************************
		*  Timer related functions
		*/

		function timer_get_cur()
		{
			list($usec, $sec) = explode(" ",microtime());
			return ((float)$usec + (float)$sec);
		}

		function timer_start($timer_name)
		{
			$this->timers[$timer_name] = $this->timer_get_cur();
		}

		function timer_elapsed($timer_name)
		{
			return round($this->timer_get_cur() - $this->timers[$timer_name],2);
		}

		function timer_update_global($timer_name)
		{
			if ( $this->do_profile )
			{
				$this->profile_times[] = array
				(
					'query' => $this->last_query,
					'time' => $this->timer_elapsed($timer_name)
				);
			}
			
			$this->total_query_time += $this->timer_elapsed($timer_name);
		}
 
	

	  public function get_set($data,$format=null){
		$formats = $format = (array) $format;
		$fields = array_keys( $data );
		$formatted_fields = array();
		foreach ( $fields as $field ) {
			if ( !empty( $format ) )
				$form = ( $form = array_shift( $formats ) ) ? $form : $format[0];
			elseif ( isset( $this->field_types[$field] ) )
				$form = $this->field_types[$field];
			else
				$form = '%s';			 
			$formatted_fields[] ="`$field` = {$form}";// '`' . $this->escape($field). '`='.$form.'';
		}
		$values=array_values($data);

		$formatted_values = array();
		foreach ( $values as $val ) {
			    if ( $val === 'true' ) $val = 1;
				if ( $val === 'false' ) $val = 0;			
				if ( $val == 'NOW()' ){
					$val =  $this->escape($val) ;					 
				}else{
					$val =  $this->escape($val) ; 
				}
				$formatted_values[]=$val;
		} 
		if (sizeof($formatted_fields) != sizeof($formatted_values)) {
			return false;
		}			 
		return  $this->prepare(implode(' , ',$formatted_fields), $formatted_values);		 
		 	
	}

	/**
	 * Insert a row into a table.
	 *
	 * <code>
	 * wpdb::insert( 'table', array( 'column' => 'foo', 'field' => 'bar' ) )
	 * wpdb::insert( 'table', array( 'column' => 'foo', 'field' => 1337 ), array( '%s', '%d' ) )
	 * </code>
	 *
	 * @since 2.5.0
	 * @see wpdb::prepare()
	 * @see wpdb::$field_types
	 * @see wp_set_wpdb_vars()
	 *
	 * @param string $table table name
	 * @param array $data Data to insert (in column => value pairs). Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 * @param array|string $format Optional. An array of formats to be mapped to each of the value in $data. If string, that format will be used for all of the values in $data.
	 * 	A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types.
	 * @return int|false The number of rows inserted, or false on error.
	 */
	function insert( $table, $data, $format = null ) {
		return $this->_insert_replace_helper( $table, $data, $format, 'INSERT' );
	}

	/**
	 * Replace a row into a table.
	 *
	 * <code>
	 * wpdb::replace( 'table', array( 'column' => 'foo', 'field' => 'bar' ) )
	 * wpdb::replace( 'table', array( 'column' => 'foo', 'field' => 1337 ), array( '%s', '%d' ) )
	 * </code>
	 *
	 * @since 3.0.0
	 * @see wpdb::prepare()
	 * @see wpdb::$field_types
	 * @see wp_set_wpdb_vars()
	 *
	 * @param string $table table name
	 * @param array $data Data to insert (in column => value pairs). Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 * @param array|string $format Optional. An array of formats to be mapped to each of the value in $data. If string, that format will be used for all of the values in $data.
	 * 	A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types.
	 * @return int|false The number of rows affected, or false on error.
	 */
	function replace( $table, $data, $format = null ) {
		return $this->_insert_replace_helper( $table, $data, $format, 'REPLACE' );
	}

	function _insert_replace_helper( $table, $data, $format = null, $type = 'INSERT' ) {
		if ( ! in_array( strtoupper( $type ), array( 'REPLACE', 'INSERT' ) ) )
			return false;
		$table = $this->quoteName($table);
        $sql = "{$type} INTO $table SET ";
        
		$sql.=$this->get_set($data,$format);
		  
		return $this->query( $sql );
	}

	/**
	 * Update a row in the table
	 *
	 * <code>
	 * wpdb::update( 'table', array( 'column' => 'foo', 'field' => 'bar' ), array( 'ID' => 1 ) )
	 * wpdb::update( 'table', array( 'column' => 'foo', 'field' => 1337 ), array( 'ID' => 1 ), array( '%s', '%d' ), array( '%d' ) )
	 * </code>
	 *
	 * @since 2.5.0
	 * @see wpdb::prepare()
	 * @see wpdb::$field_types
	 * @see wp_set_wpdb_vars()
	 *
	 * @param string $table table name
	 * @param array $data Data to update (in column => value pairs). Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 * @param array $where A named array of WHERE clauses (in column => value pairs). Multiple clauses will be joined with ANDs. Both $where columns and $where values should be "raw".
	 * @param array|string $format Optional. An array of formats to be mapped to each of the values in $data. If string, that format will be used for all of the values in $data.
	 * 	A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types.
	 * @param array|string $where_format Optional. An array of formats to be mapped to each of the values in $where. If string, that format will be used for all of the items in $where.  A format is one of '%d', '%f', '%s' (integer, float, string).  If omitted, all values in $where will be treated as strings.
	 * @return int|false The number of rows updated, or false on error.
	 */
	 
	 

    
    public function update($table, $data, $where,$format=null){
        // the base statement
        $table = $this->quoteName($table);
        $sql = "UPDATE $table SET ";        
		$sql.=$this->get_set($data,$format);		 
		$sql.=' where '.$where;
		return $this->query($sql);
        
    }    

    public function delete($table, $where){
         
        $table = $this->quoteName($table);
		//$where=$options['where'];	 
        $result = $this->query("DELETE FROM $table WHERE $where");
        return $result;
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
    /*---------------------------------------*/
     function logs($sql){
			$config = array(
			 'adapter' => 'Genv_Log_Adapter_File',
			 'events'  => '*',
			 'file'    => Genv_Config::get('Genv','appname').'/Data/Logs/sql.log.txt',
			);
			$log = Genv::factory('Genv_Log', $config);       
			$log->save($sql,'','');
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
}
