<?php

abstract class Genv_Factory extends Genv_Base{
 
    protected $_Genv_Factory = array(
        'adapter' => null,
    );
    
  
    final public function __call($method, $params){

        throw $this->_exception('ERR_METHOD_NOT_IMPLEMENTED', array(
            'method' => $method,
            'params' => $params,
        ));
    }
    
   
    public function factory(){      
        $config = $this->_config;		
        $class = $config['adapter']; 
        unset($config['adapter']); 
		 
        return new $class($config);
    }
}
?>