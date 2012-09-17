<?php

class Genv_File{  

    protected static $_file;    
    public static function exists($file){
        
        $file = trim($file);
        if (! $file) {
            return false;
        }
       
        $abs = ($file[0] == '/' || $file[0] == '\\' || $file[1] == ':');
        if ($abs && file_exists($file)) {
            return $file;
        }        
        
        $path= explode(PATH_SEPARATOR, ini_get('include_path'));
		$path[]= str_replace("Genv",'',dirname(__FILE__));//dirname(__FILE__);
 
        foreach ($path as $base) {		 
            
            $target = rtrim($base, '\\/') . DIRECTORY_SEPARATOR . $file; 
			  
            if (file_exists($target)) {				 
                return $target;
            }
        }
        
        // never found it
        return false;
    }    
  
    public static function tmp($file){

        // convert slashes to OS-specific separators,
        // then remove leading and trailing separators
        $file = str_replace('/', DIRECTORY_SEPARATOR, $file);
        $file = trim($file, DIRECTORY_SEPARATOR);
        
        // return the tmp dir plus file name
        return Genv_Dir::tmp() . $file;
    }
    
  
    public static function load($file){
        Genv_File::$_file = Genv_File::exists($file);
 	  //echo Genv_File::$_file."<br>";
 
        if (! Genv_File::$_file) {
			 
            $code = 'ERR_FILE_NOT_READABLE';			
            $text = Genv_Registry::get('locale')->fetch('Genv', $code);
            throw Genv::exception(
                'Genv',
                $code,
                $text,
                array('file' => $file)
            );
        }      
        unset($file);
        return require_cache(Genv_File::$_file);
    }    
}
?>