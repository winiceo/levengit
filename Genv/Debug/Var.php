<?php
/**
 * 
 * Class to help examine and debug variables.
 * 
 * Captures the output of
 * [[php::var_dump() | ]] and outputs it to the screen either as
 * plaintext or in HTML format.
 * 
 * For example ...
 * 
 * {{code: php
 *     require_once 'Genv.php';
 *     Genv::start();
 * 
 *     // an array to dump as an example
 *     $example = array(0, 1, 2, 3);
 * 
 *     // the hard way
 *     $debug = Genv::factory('Genv_Debug_Var');
 *     $debug->display($example);
 * 
 *     // the easy way
 *     Genv::dump($example);
 * }}
 * 
 * Note also that Genv_Base has a custom dump() method as well, so any
 * class descended from Genv_Base can be dumped directly.
 * 
 * {{code: php
 *     // an array to dump as an example
 *     $example = Genv::factory('Genv_Example');
 *     $example->dump();
 * }}
 * 
 * In general, you will never need to instantiate this class, as it is more
 * easily accessed via [[Genv::dump()]] and [[Genv_Base::dump()]].
 * 
 * @category Genv
 * 
 * @package Genv_Debug
 * 
 * @author Paul M. Jones <pmjones@Genvphp.com>
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 * @version $Id: Var.php 4263 2009-12-07 19:25:31Z pmjones $
 * 
 */
class Genv_Debug_Var extends Genv_Base
{
    /**
     * 
     * Default configuration values.
     * 
     * @config string output Output mode.  Set to 'html' for HTML; 
     *   or 'text' for plain text.  Default autodetects by SAPI version.
     * 
     * @var array
     * 
     */
    protected $_Genv_Debug_Var = array(
        'output' => null,
    );
    
    /**
     * 
     * Modifies the default config.
     * 
     * @return void
     * 
     */
    protected function _preConfig()
    {
        parent::_preConfig();
        $output = (PHP_SAPI == 'cli') ? 'text' : 'html';
        $this->_Genv_Debug_Var['output'] = $output;
    }
    
    /**
     * 
     * Prints the output of Genv_Debug_Var::fetch() with a label.
     * 
     * Use this for debugging variables to see exactly what they contain.
     * 
     * @param mixed $var The variable to dump.
     * 
     * @param string $label A label to prefix to the dump.
     * 
     * @return string The labeled results of var_dump().
     * 
     */
    public function display($var, $label = null)
    {
        // if there's a label, add a space after it
        if ($label) {
            $label .= ' ';
        }
        
        // get the output
        $output = $label . $this->fetch($var);
        
        // done
        echo $output;
    }
    
    /**
     * 
     * Returns formatted output from var_dump().
     * 
     * Buffers the [[php::var_dump | ]] for a variable and applies some
     * simple formatting for readability.
     * 
     * @param mixed $var The variable to dump.
     * 
     * @return string The formatted results of var_dump().
     * 
     */
    public function fetch($var)
    {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
        
        if (strtolower($this->_config['output']) == 'html') {
            $output = '<pre>' . htmlspecialchars($output) . '</pre>';
        }
        
        return $output;
    }
}
?>