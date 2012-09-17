<?php
/**
 * 
 * SMTP adapter with no authentication.
 * 
 * @category Genv
 * 
 * @package Genv_Smtp
 * 
 * @author Paul M. Jones <pmjones@Genvphp.com>
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 * @version $Id: NoAuth.php 3153 2008-05-05 23:14:16Z pmjones $
 * 
 */
class Genv_Smtp_Adapter_NoAuth extends Genv_Smtp_Adapter
{
    /**
     * 
     * Authentication is never attempted, and always fails.
     * 
     * @return bool
     * 
     */
    public function auth()
    {
        return $this->_auth;
    }
}
