<?php
/**
 * 
 * Support class to provide an iterator for Genv_Struct objects.
 * 
 * Note that this class does not extend Genv_Base; its only purpose is to
 * implement the Iterator interface as lightly as possible.
 * 
 * @category Genv
 * 
 * @package Genv
 * 
 * @author Paul M. Jones <pmjones@Genvphp.com>
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 * @version $Id: Iterator.php 4272 2009-12-17 21:56:32Z pmjones $
 * 
 */
class Genv_Struct_Iterator implements Iterator
{
    /**
     * 
     * The struct over which we are iterating.
     * 
     * @var Genv_Struct
     * 
     */
    protected $_struct = array();
    
    /**
     * 
     * Is the current iterator position valid?
     * 
     * @var bool
     * 
     */
    protected $_valid = false;
    
    /**
     * 
     * The list of all keys in the struct.
     * 
     * @var bool
     * 
     */
    protected $_keys = array();
    
    /**
     * 
     * Constructor; note that this is **not** a Genv constructor.
     * 
     * @param Genv_Struct $struct The struct for which this iterator will be
     * used.
     * 
     */
    public function __construct(Genv_Struct $struct)
    {
        $this->_struct = $struct;
        $this->_keys   = $struct->getKeys();
    }
    
    /**
     * 
     * Returns the struct value for the current iterator position.
     * 
     * @return mixed
     * 
     */
    public function current()
    {
        return $this->_struct->__get($this->key());
    }
    
    /**
     * 
     * Returns the current iterator position.
     * 
     * @return mixed
     * 
     */
    public function key()
    {
        return current($this->_keys);
    }
    
    /**
     * 
     * Moves the iterator to the next position.
     * 
     * @return void
     * 
     */
    public function next()
    {
        $this->_valid = (next($this->_keys) !== false);
    }
    
    /**
     * 
     * Moves the iterator to the first position.
     * 
     * @return void
     * 
     */
    public function rewind()
    {
        $this->_valid = (reset($this->_keys) !== false);
    }
    
    /**
     * 
     * Is the current iterator position valid?
     * 
     * @return void
     * 
     */
    public function valid()
    {
        return $this->_valid;
    }
}
