<?php
class Genv_Jquery extends Genv_Base
{
    /**
     * jQuery
     *
     * alias for jQuery::jQuery
     *
     * @access  public
     * @param   string   $selector
     * @return  jQuery_Element
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array(array('Genv_Jquery_Adapter', $name), $arguments);
    }
    public function error()
    {
        $request = Genv_Registry::get('request');
        if (!$request->isXhr()) {
            return true;
        }
    }
}