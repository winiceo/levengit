<?php
class Genv_Class{

    protected static $_parents = array();    
    
    public static function autoload($name){
      
        if (trim($name) == '') {			
            throw Genv::exception(
                'Genv_Class',
                'ERR_AUTOLOAD_EMPTY',
                'No class or interface named for loading.',
                array('name' => $name)
            );
        }
		
        $exists = class_exists($name, false)
               || interface_exists($name, false);
        
        if ($exists) {
            return;
        } 
	 
        $file = Genv_Class::nameToFile($name); 
		$files= Genv_Config::get("Temp",'file');
		$files[]=$file;
		Genv_Config::set('Temp','file',$files);
		   //echo $file."<br>";

         Genv_File::load($file);
        
        // if the class or interface was not in the file, we have a problem.
        // do not use autoload, because this method is registered with
        // spl_autoload already.
		//dump( $name);

        $exists = class_exists($name, false)
               || interface_exists($name, false);
        
        if (! $exists) {			
            throw Genv::exception(
                'Genv_Class',
                'ERR_AUTOLOAD_FAILED',
                'Class or interface does not exist in loaded file',
                array('name' => $name, 'file' => $file)
            );
        }
    }   
   
    public static function nameToFile($spec){       
        $pos = strrpos($spec, '\\');

		 
        if ($pos === false) {            
            $namespace = '';
            $class     = $spec;
			 
        } else {
            // pre-convert namespace portion to file path
            $namespace = substr($spec, 0, $pos);
            $namespace = str_replace('\\', DIRECTORY_SEPARATOR, $namespace)
                       . DIRECTORY_SEPARATOR;
            
            // class portion
            $class = substr($spec, $pos + 1);
        }
			//substr($content, 35,12)
			 
         //ECHO   str_replace('_',  DIRECTORY_SEPARATOR, $class)
            // . '.php<br>';//
             
        // convert class underscores, and done
        return $namespace
             . str_replace('_',  DIRECTORY_SEPARATOR, $class)
             . '.php';
    }
    
    /**
     * 
     * Returns an array of the parent classes for a given class.
     * 
     * @param string|object $spec The class or object to find parents
     * for.
     * 
     * @param bool $include_class If true, the class name is element 0,
     * the parent is element 1, the grandparent is element 2, etc.
     * 
     * @return array
     * 
     */
    public static function parents($spec, $include_class = false)
    {
        if (is_object($spec)) {
            $class = get_class($spec);
        } else {
            $class = $spec;
        }
        
        // do we need to load the parent stack?
        if (empty(Genv_Class::$_parents[$class])) {
            // use SPL class_parents(), which uses autoload by default.  use
            // only the array values, not the keys, since that messes up BC.
            $parents = array_values(class_parents($class));
            Genv_Class::$_parents[$class] = array_reverse($parents);
        }
        
        // get the parent stack
        $stack = Genv_Class::$_parents[$class];
        

		// dump($spec);dump($class);

        // add the class itself?
        if ($include_class) {
            $stack[] = $class;
        }
		
        
        // done
        return $stack;
    }
    
    /**
     * 
     * Returns the directory for a specific class, plus an optional
     * subdirectory path.
     * 
     * @param string|object $spec The class or object to find parents
     * for.
     * 
     * @param string $sub Append this subdirectory.
     * 
     * @return string The class directory, with optional subdirectory.
     * 
     */
    public static function dir($spec, $sub = null)
    {
        if (is_object($spec)) {
            $class = get_class($spec);
        } else {
            $class = $spec;
        }
        
        // convert the class to a base directory to stem from
        $base = str_replace('_', DIRECTORY_SEPARATOR, $class);
        
        // does the directory exist?
        $dir = Genv_Dir::exists($base);
        if (! $dir) {
            throw Genv::exception(
                'Genv_Class',
                'ERR_NO_DIR_FOR_CLASS',
                'Directory does not exist',
                array('class' => $class, 'base' => $base)
            );
        } else {
            return Genv_Dir::fix($dir . DIRECTORY_SEPARATOR. $sub);
        }
    }
    
 
    public static function file($spec, $file)
    {
        $dir = Genv_Class::dir($spec);
        return Genv_File::exists($dir . $file);
    }
    
  
    public static function vendor($spec)
    {
        if (is_object($spec)) {
            $class = get_class($spec);
        } else {
            $class = $spec;
        }
        
        
        $pos = strpos($class, '_');
        if ($pos !== false) {
            // return the part up to the first underscore
            return substr($class, 0, $pos);
        } else {
            // no underscores, must be an arch-class
            return $class;
        }
    }
    
  
    public static function vendors($spec)
    {
      
        $stack = array();      
       
        $parents = Genv_Class::parents($spec, true);      
      
        $old = null;
        foreach ($parents as $class) {
            $new = Genv_Class::vendor($class);
            if ($new != $old) {
                // not the same, add the current vendor name and suffix
                $stack[] = $new;
            }
            // retain old vendor for next loop
            $old = $new;
        }
        
        return $stack;
    }
}
?>