<?php
/**
 * 
 * Factory class for mail transport adapters.
 * 
 * @category Genv
 * 
 * @package Genv_Mail
 * 
 * @author Paul M. Jones <pmjones@Genvphp.com>
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 * @version $Id: Transport.php 3850 2009-06-24 20:18:27Z pmjones $
 * 
 */
class Genv_Mail_Transport extends Genv_Factory
{
    /**
     * 
     * Default configuration values.
     * 
     * @config string adapter The class to factory.  Default is
     * 'Genv_Mail_Transport_Adapter_Phpmail'.
     * 
     * @var array
     * 
     */
    protected $_Genv_Mail_Transport = array(
        'adapter' => 'Genv_Mail_Transport_Adapter_Phpmail',
    );
}