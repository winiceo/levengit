<?php
/*
»ù´¡Àà;

*/
 
abstract class Genv_Base{
    
    
    protected $_config = array();    
   
    public function __construct($config = null){
        
        $this->_preConfig();
        
        //dump($this->_buildConfig(get_class($this)));
       
		$this->_config = array_merge(
            $this->_buildConfig(get_class($this)),
            (array) $config
        );    
		
       
        $this->_postConfig();
        
        $this->_postConstruct();
    }    
   
    public function __destruct(){
    }
    
   
    public function dump($var = null, $label = null)
    {
        $obj = Genv::factory('Genv_Debug_Var');
        if (is_null($var)) {
            // clone $this and remove the parent config arrays
            $clone = clone($this);
            foreach (Genv_Class::parents($this) as $class) {
                $key = "_$class";
                unset($clone->$key);
            }
            $obj->display($clone, $label);
        } elseif (is_string($var)) {
            // display a property
            $obj->display($this->$var, $label);
        } else {
            // display the passed variable
            $obj->display($var, $label);
        }
    }
    
  
    public function locale($key, $num = 1, $replace = null){

        static $class;
        if (! $class) {
            $class = get_class($this);
        }
        
        static $locale;
        if (! $locale) {
            $locale = Genv_Registry::get('locale');
		}
        
        return $locale->fetch($class, $key, $num, $replace);
    }
    
   
    protected function _buildConfig($class){
        if (! $class) {
            return array();
        }
        
        $config = Genv_Config::getBuild($class);
        
 		
        if ($config === null) {
        
            $var    = "_$class";
            $prop   = empty($this->$var)
                    ? array()
                    : (array) $this->$var;
                    
            $parent = get_parent_class($class);
            
            $config = array_merge(
                // parent values
                $this->_buildConfig($parent),
                // override with class property config
                $prop,
                // override with Genv config for the class
                Genv_Config::get($class, null, array())
            );
           
            // cache for future reference
            Genv_Config::setBuild($class, $config);
        }
        
        return $config;
    }
    
    
    protected function _preConfig()
    {
		//echo 55;
    }
    
   
    protected function _postConfig()
    {
    }
   
    protected function _postConstruct()
    {
    }
    
  
    protected function _exception($code, $info = array())
    {
        static $class;
        if (! $class) {
            $class = get_class($this);
        }
       
        return Genv::exception(
            $class,
            $code,
            $this->locale($code, 1, $info),
            (array) $info
        );
    }
}
?>