<?php
/*
еДжц;
*/
class Genv_Config{   

    static protected $_store = array(); 
   
    static protected $_build = array();
    
   
    static public function get($class = null, $key = null, $val = null){
       
        if ($class === null) {          
            return Genv_Config::$_store;
        }       
        
        if ($key === null) {            
           
            if ($val === null) {
                $val = array();
            }          
          
            if (! array_key_exists($class, Genv_Config::$_store)) {
                return $val;
            } else {
                return Genv_Config::$_store[$class];
            }
            
        } else {            
           
            $exists = array_key_exists($class, Genv_Config::$_store)
                   && array_key_exists($key, Genv_Config::$_store[$class]);
            
            if (! $exists) {
                return $val;
            } else {
                return Genv_Config::$_store[$class][$key];
            }
        }
    }
  
    static public function load($spec){

        Genv_Config::$_store = Genv_Config::fetch($spec);
        Genv_Config::$_build = array();
        $callback = Genv_Config::get('Genv_Config', 'load_callback');
        if ($callback) {
            $merge = (array) call_user_func($callback);
            Genv_Config::$_store = array_merge(Genv_Config::$_store, $merge);
        }
    }
   
    static public function set($class, $key, $val){
        if (! $key) {
            Genv_Config::$_store[$class] = $val;
        } else {
            Genv_Config::$_store[$class][$key] = $val;
        }
        Genv_Config::$_build = array();
    }
    
   
    static public function fetch($spec = null)    {
       
        if (is_array($spec) || is_object($spec)) {
            $config = (array) $spec;
        } elseif (is_string($spec)) {         
            $config = (array) Genv_File::load($spec);
        } else {           
            $config = array();
        }        
        return $config;
    }    
  
    static public function setBuild($class, $config){
        Genv_Config::$_build[$class] = (array) $config;
    }    
  
    static public function getBuild($class){
        if (array_key_exists($class, Genv_Config::$_build)) {
            return Genv_Config::$_build[$class];
        }
    }

}?>