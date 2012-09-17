<?php
/**
 * 
 * Factory class for session save-handlers.
 * 
 * @category Genv
 * 
 * @package Genv_Session
 * 
 * @author Antti Holvikari <anttih@gmail.com>
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 * @version $Id: Handler.php 3850 2009-06-24 20:18:27Z pmjones $
 * 
 */
class Genv_Session_Handler extends Genv_Factory
{
    /**
     * 
     * Default configuration values.
     * 
     * @config string adapter The class to factory, for example
     *   'Genv_Session_Handler_Adapter_Native'.
     * 
     * @var array
     * 
     */
    protected $_Genv_Session_Handler = array(
        'adapter' => 'Genv_Session_Handler_Adapter_Native',
    );
}

?>