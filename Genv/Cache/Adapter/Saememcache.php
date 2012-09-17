<?php
class  Genv_Cache_Adapter_Saememcache extends Genv_Cache_Adapter{
	
	var $_active=false;
	public $memcache;
	 protected function _postConstruct()
    {
        parent::_postConstruct();
	
		$this->memcache = memcache_init();
		if($this->memcache){
			$this ->_active = true;
		}
	}

    public function save($key, $data, $life = null)
    {
        if (! $this->_active) {
            return;
        }
        
        // modify the key to add the prefix
        $key = $this->entry($key);
        
        // life value
        if ($life === null) {
            $life = $this->_life;
        }
        
        // store in memcache
        return $this->memcache->set($key, $data, null, $life);
    }
    
    /**
     * 
     * Inserts cache entry data, but only if the entry does not already exist.
     * 
     * @param string $key The entry ID.
     * 
     * @param mixed $data The data to write into the entry.
     * 
     * @param int $life A custom lifespan, in seconds, for the entry; if null,
     * uses the default lifespan for the adapter instance.
     * 
     * @return bool True on success, false on failure.
     * 
     */
    public function add($key, $data, $life = null)
    {
        if (! $this->_active) {
            return;
        }
        
        // modify the key to add the prefix
        $key = $this->entry($key);
        
        // life value
        if ($life === null) {
            $life = $this->_life;
        }
        
        // add to memcache
        return $this->memcache->add($key, $data, null, $life);
    }
    
    /**
     * 
     * Gets cache entry data.
     * 
     * @param string $key The entry ID.
     * 
     * @return mixed Boolean false on failure, string on success.
     * 
     */
    public function fetch($key)
    {
        if (! $this->_active) {
            return;
        }
        
        // modify the key to add the prefix
        $key = $this->entry($key);
        
        // get from memcache
        return $this->memcache->get($key);
    }
    
    /**
     * 
     * Increments a cache entry value by the specified amount.  If the entry
     * does not exist, creates it at zero, then increments it.
     * 
     * @param string $key The entry ID.
     * 
     * @param string $amt The amount to increment by (default +1).  Using
     * negative values is effectively a decrement.
     * 
     * @return int The new value of the cache entry.
     * 
     */
    public function increment($key, $amt = 1)
    {
        if (! $this->_active) {
            return;
        }
        
        // modify the key to add the prefix
        $key = $this->entry($key);
        
        // make sure we have a key to increment
        $this->add($key, 0, null, $this->_life);
        
        // let memcache do the increment and retain its value
        $val = $this->memcache->increment($key, $amt);
        
        // done
        return $val;
    }
    
    /**
     * 
     * Deletes a cache entry.
     * 
     * @param string $key The entry ID.
     * 
     * @return void
     * 
     */
    public function delete($key)
    {
        if (! $this->_active) {
            return;
        }
        
        // modify the key to add the prefix
        $key = $this->entry($key);
        
        // remove from memcache
        $this->memcache->delete($key);
    }
    
    /**
     * 
     * Removes all cache entries.
     * 
     * @return void
     * 
     */
    public function deleteAll()
    {
        if (! $this->_active) {
            return;
        }
        
        $this->memcache->flush();
    }
    
    /**
     * 
     * Adds servers to a memcache connection pool from configuration.
     * 
     * @return void
     * 
     */
    protected function _createPool()
    {
        $connection_count = 0;
        
        foreach ($this->_config['pool'] as $server) {
            // set all defaults
            $server = array_merge($this->_pool_node, $server);
            
            // separate addServer calls in case failure_callback is 
            // empty
            if (empty($server['failure_callback'])) {
                $result = $this->memcache->addServer(
                    (string) $server['host'],
                    (int)    $server['port'],
                    (bool)   $server['persistent'],
                    (int)    $server['weight'],
                    (int)    $server['retry_interval'],
                    (bool)   $server['status']
                );
                                
            } else {
                $result = $this->memcache->addServer(
                    (string) $server['host'],
                    (int)    $server['port'],
                    (bool)   $server['persistent'],
                    (int)    $server['weight'],
                    (int)    $server['retry_interval'],
                    (bool)   $server['status'],
                             $server['failure_callback']
                );
            }
            
            // Did connection to the last node succeed?
            if ($result === true) {
                $connection_count++;
            }
        
        }
        
        // make sure we connected to at least one
        if ($connection_count < 1) {
            $info = $this->_config['pool'];
            throw $this->_exception('ERR_CONNECTION_FAILED', $info);
        }
    }
}
