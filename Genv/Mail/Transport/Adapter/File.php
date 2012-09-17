<?php
/**
 * 
 * Pseudo-transport that writes the message headers and content to a file.
 * 
 * The files are saved in a configurable directory location, and are named
 * "Genv_email_{date('Y-m-d_H-i-s.u')}" by default.
 * 
 * @category Genv
 * 
 * @package Genv_Mail
 * 
 * @author Paul M. Jones <pmjones@Genvphp.com>
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 * @version $Id: File.php 4263 2009-12-07 19:25:31Z pmjones $
 * 
 */
class Genv_Mail_Transport_Adapter_File extends Genv_Mail_Transport_Adapter
{
    /**
     * 
     * Default configuration values.
     * 
     * @config string dir The directory where email files should be saved.  Default
     *   is the system temp directory.
     * 
     * @config string prefix Prefix file names with this value; default is 'Genv_email_'.
     * 
     * @var array
     * 
     */
    protected $_Genv_Mail_Transport_Adapter_File = array(
        'dir'    => null,
        'prefix' => 'Genv_email_',
    );
    
    /**
     * 
     * Sets the default directory to write emails to (the temp dir).
     * 
     * @return void
     * 
     */
    protected function _preConfig()
    {
        parent::_preConfig();
        
        if (Genv::$system) {
            $tmp = Genv::$system . '/tmp/mail/';
        } else {
            $tmp = Genv_Dir::tmp('/Genv_Mail_Transport_Adapter_File/');
        }
        
        $this->_Genv_Mail_Transport_Adapter_File['dir'] = $tmp;
    }
    
    /**
     * 
     * Writes the Genv_Mail_Message headers and content to a file.
     * 
     * @return bool True on success, false on failure.
     * 
     */
    protected function _send()
    {
        $file = Genv_Dir::fix($this->_config['dir'])
              . $this->_config['prefix']
              . date('Y-m-d_H-i-s')
              . '.' . substr(microtime(), 2, 6);
        
        $text = $this->_headersToString($this->_mail->fetchHeaders())
              . $this->_mail->getCrlf()
              . $this->_mail->fetchContent();
        
        $result = file_put_contents($file, $text);
        
        if ($result === false) {
            return false;
        } else {
            return true;
        }
    }
}