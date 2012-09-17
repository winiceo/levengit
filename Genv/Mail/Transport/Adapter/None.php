<?php
/**
 * 
 * Pseudo-transport that does nothing at all.
 * 
 * Useful for some kinds of tests, and for "turning off" mail sending in some
 * cases.
 * 
 * @category Genv
 * 
 * @package Genv_Mail
 * 
 * @author Paul M. Jones <pmjones@Genvphp.com>
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 * @version $Id: None.php 3153 2008-05-05 23:14:16Z pmjones $
 * 
 */
class Genv_Mail_Transport_Adapter_None extends Genv_Mail_Transport_Adapter
{
    /**
     * 
     * Does nothing.
     * 
     * @return bool Always true.
     * 
     */
    protected function _send()
    {
        return true;
    }
}