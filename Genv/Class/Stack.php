<?php

class Genv_Class_Stack extends Genv_Base{
   
    protected $_stack = array();
    
    public function get(){
        return $this->_stack;
    }
    
    /**
     * 
     * Adds one or more classes to the stack.
     * 
     * {{code: php
     *     
     *     // add by array
     *     $stack = Genv::factory('Genv_Class_Stack');
     *     $stack->add(array('Base1', 'Base2', 'Base3'));
     *     // $stack->get() reveals that the class search order will be
     *     // 'Base1_', 'Base2_', 'Base3_'.
     *     
     *     // add by string
     *     $stack = Genv::factory('Genv_Class_Stack');
     *     $stack->add('Base1, Base2, Base3');
     *     // $stack->get() reveals that the class search order will be
     *     // 'Base1_', 'Base2_', 'Base3_'.
     *     
     *     // add incrementally -- N.B. THIS IS A SPECIAL CASE
     *     $stack = Genv::factory('Genv_Class_Stack');
     *     $stack->add('Base1');
     *     $stack->add('Base2');
     *     $stack->add('Base3');
     *     // $stack->get() reveals that the directory search order will be
     *     // 'Base3_', 'Base2_', 'Base1_', because the later adds
     *     // override the newer ones.
     * }}
     * 
     * @param array|string $list The classes to add to the stack.
     * 
     * @return void
     * 
     */
    public function add($list){
        if (is_string($list)) {
            $list = explode(',', $list);
        }
        
        if (is_array($list)) {
            $list = array_reverse($list);
        }
        
        foreach ((array) $list as $class) {
            $class = trim($class);
            if (! $class) {
                continue;
            }
            // trim all trailing _, then add just one _,
            // and add to the stack.
            $class = rtrim($class, '_') . '_';
            array_unshift($this->_stack, $class);
        }
    }
    
 
    public function addByParents($spec, $base = null){
        // get the list of parents; always skip Genv_Base
        $parents = Genv_Class::parents($spec, true);
        array_shift($parents);
        
        // if not tracking cross-hierarchy shifts, add parents as they are
        if (! $base) {
            $list = array_reverse($parents);
            return $this->add($list);
        }
        
        // track cross-hierarchy shifts in class names. any time we change
        // "*_Base" prefixes, insert "New_Prefix_Base" into the stack.
        $old = null;
        foreach ($parents as $class) {
            
            $pos = strpos($class, "_$base");
            $new = substr($class, 0, $pos);
            
            // check to see if we crossed vendors or hierarchies
            if ($new != $old) {
                $cross = "{$new}_{$base}";
                $this->add($cross);
            } else {
                $cross = null;
            }
            
            // prevent double-adds where the cross-hierarchy class name ends
            // up being the same as the current class name
            if ($cross != $class) {
                // not the same, add the current class name
                $this->add($class);
            }
            
            // retain old prefix for next loop
            $old = $new;
        }
    }
    
 
    public function addByVendors($spec, $base = null){
        // get the list of parents; retain Genv_Base
        $parents = Genv_Class::parents($spec, true);
        
        // if we have a suffix, put a separator on it
        if ($base) {
            $base = "_$base";
        }
        
        // look through vendor names
        $old = null;
        foreach ($parents as $class) {
            $new = Genv_Class::vendor($class);
            if ($new != $old) {
                // not the same, add the current vendor name and suffix
                $this->add("{$new}{$base}");
            }
            // retain old vendor for next loop
            $old = $new;
        }
    }    
   
    public function set($list){
        $this->_stack = array();
        return $this->add($list);
    }    
  
    public function setByParents($spec, $base = null){
        $this->_stack = array();
        $this->addByParents($spec, $base);
    }
    
  
    public function setByVendors($spec, $base = null){
        $this->_stack = array();
        $this->addByVendors($spec, $base);
    }    
  
    public function load($name, $throw = true){
        // some preliminary checks for valid class names
        if (! $name || $name != trim($name) || ! ctype_alpha($name[0])) {
            if ($throw) {
                throw $this->_exception('ERR_CLASS_NOT_VALID', array(
                    'name'  => $name,
                    'stack' => $this->_stack,
                ));
            } else {
                return false;
            }
        }        
     
        $name = ucfirst($name);
        foreach ($this->_stack as $prefix) {
            
            // the full class name
            $class = "$prefix$name";
         // dump($prefix);
            // pre-empt searching.
            // don't use autoload.
            if (class_exists($class, false)) {
                return $class;
            }
            
            // the related file
            $file = str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
              //dump($class);
            // does the file exist?
            if (! Genv_File::exists($file)) {
                continue;
            }
           // echo $file."<br>";
            // include it in a limited scope. we don't use Genv_File::load()
            // because we want to avoid exceptions.
            $this->_run($file);
            
            // did the class exist within the file?
            // don't use autoload.
//echo $class;
            if (class_exists($class, false)) {
                // yes, we're done
                return $class;
            }
			//去应用程序中加载;
			//$class=str_replace(Genv_Config::get('Genv','appname')."_",'',$class);	
			
			//echo $name;

			 
			$class=$name.Genv_Config::get('App','action');
			//echo $class;
			if (class_exists($class, false)) {               
                return $class;
            }
        }
        
        // failed to find the class in the stack
        if ($throw) {
            throw $this->_exception('ERR_CLASS_NOT_FOUND', array(
                'class' => $name,
                'stack' => $this->_stack,
            ));
        } else {
            return false;
        }
    }
   
    protected function _run(){	 
        require_cache(func_get_arg(0));
    }
}
?>