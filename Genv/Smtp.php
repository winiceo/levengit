<?php
/**
 * 
 * Factory class for SMTP connections.
 * 
 * @category Genv
 * 
 * @package Genv_Smtp Adapters for sending email via SMTP.
 * 
 * @author Paul M. Jones <pmjones@Genvphp.com>
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 * @version $Id: Smtp.php 4380 2010-02-14 16:06:52Z pmjones $
 * 
 */
class Genv_Smtp extends Genv_Factory
{
    /**
     * 
     * Default configuration values.
     * 
     * @config string adapter The class to factory, for example 'Genv_Smtp_Adapter_NoAuth'.
     * 
     * @var array
     * 
     */
    protected $_Genv_Smtp = array(
        'adapter' => 'Genv_Smtp_Adapter_NoAuth',
    );
}