<?php
/*
зЂВсвЛИіРр;
*/
class Genv_Registry{
    
    protected static $_obj = array();    
   
    final private function __construct() {}    
   
    public static function get($name){	 
       
        if (! Genv_Registry::exists($name)) {			 
            throw Genv::exception(
                'Genv_Registry',
                'ERR_NOT_IN_REGISTRY',
                "Object with name '$name' not in registry.",
                array('name' => $name)
            );
        }        
       
        if (is_array(Genv_Registry::$_obj[$name])) {			
 			
            $val = Genv_Registry::$_obj[$name]; 
		 
			$obj = Genv::factory($val[0], $val[1]);
            Genv_Registry::$_obj[$name] = $obj;
        }
        
    
        return Genv_Registry::$_obj[$name];
    }    
   
    public static function set($name, $spec, $config = null){
        if (Genv_Registry::exists($name)) {
           
            $class = get_class(Genv_Registry::$_obj[$name]);
		
            throw Genv::exception(
                'Genv_Registry',
                'ERR_REGISTRY_NAME_EXISTS',
                "Object with '$name' of class '$class' already in registry", 
                array('name' => $name, 'class' => $class)
            );
        }

        if (is_object($spec)) {          
            Genv_Registry::$_obj[$name] = $spec;
        } elseif (is_string($spec)) {           
            Genv_Registry::$_obj[$name] = array($spec, $config);
        } else {
            throw Genv::exception(
                'Genv_Registry',
                'ERR_REGISTRY_FAILURE',
                'Please pass an object, or a class name and a config array',
                array()
            );
        }
    }  
    public static function exists($name){
        return ! empty(Genv_Registry::$_obj[$name]);
    }    
}
?>