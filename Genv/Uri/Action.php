<?php
 
class Genv_Uri_Action extends Genv_Uri{
 
    protected $_Genv_Uri_Action = array(
        'path' => '/index.php',
    );    
 
    protected function _preConfig(){
        parent::_preConfig();
        $this->_request = Genv_Registry::get('request');
        $this->_Genv_Uri_Action['path'] = $this->_request->server(
            'Genv_URI_ACTION_PATH',
            '/index.php'
        );
    }    
  
    public function getFrontPath(){      
        return (empty($this->path)         ? '' : $this->_pathEncode($this->path))
             . (trim($this->format) === '' ? '' : '.' . urlencode($this->format));
    }
}
?>