<?php
/** jQuery_Action */
require_once 'Adapter/Action.php';
/** jQuery_Element */
require_once 'Adapter/Element.php';

/**
 * jQuery
 *
 * @author Anton Shevchuk
 * @access   public
 * @package  jQuery
 * @version  0.8
 */
class Genv_Jquery_Adapter extends Genv_Base
{
    /**
     * static var for realize singlton
     * @var jQuery
     */
    public static $jQuery;

    /**
     * response stack
     * @var array
     */
    public $response = array(
                              // actions (addMessage, addError, eval etc.)
                              'a' => array(),
                              // jqueries
                              'q' => array()
                            );
    /**
     * __construct
     *
     * @access  public
     */
    function __construct()
    {

    }

    /**
     * init
     * init singleton if needed
     *
     * @return void
     */
    public static function init()
    {
        if (empty(Genv_Jquery_Adapter::$jQuery)) {
            Genv_Jquery_Adapter::$jQuery = new Genv_Jquery_Adapter();
        }
        return true;
    }


    /**
     * addData
     *
     * add any data to response
     *
     * @param string $key
     * @param mixed $value
     * @param string $callBack
     * @return jQuery
     */
    public static function addData ($key, $value, $callBack = null)
    {
        Genv_Jquery_Adapter::init();

        $jQuery_Action = new jQuery_Action();
        $jQuery_Action ->add('k', $key);
        $jQuery_Action ->add('v', $value);

        // add call back func into response JSON obj
        if ($callBack) {
            $jQuery_Action ->add("callback", $callBack);
        }

        Genv_Jquery_Adapter::addAction(__FUNCTION__, $jQuery_Action);

        return Genv_Jquery_Adapter::$jQuery;
    }

    /**
     * addMessage
     *
     * @param string $msg
     * @param string $callBack
     * @param array  $params
     * @return jQuery
     */
    public static function addMessage ($msg, $callBack = null, $params = null)
    {
        Genv_Jquery_Adapter::init();

        $jQuery_Action = new jQuery_Action();
        $jQuery_Action ->add("msg", $msg);


        // add call back func into response JSON obj
        if ($callBack) {
            $jQuery_Action ->add("callback", $callBack);
        }

        if ($params) {
            $jQuery_Action ->add("params",  $params);
        }

        Genv_Jquery_Adapter::addAction(__FUNCTION__, $jQuery_Action);

        return Genv_Jquery_Adapter::$jQuery;
    }

    /**
     * addError
     *
     * @param string $msg
     * @param string $callBack
     * @param array  $params
     * @return jQuery
     */
    public static function addError ($msg, $callBack = null, $params = null)
    {
        Genv_Jquery_Adapter::init();

        $jQuery_Action = new jQuery_Action();
        $jQuery_Action ->add("msg", $msg);

        // add call back func into response JSON obj
        if ($callBack) {
            $jQuery_Action ->add("callback", $callBack);
        }

        if ($params) {
            $jQuery_Action ->add("params",  $params);
        }

        Genv_Jquery_Adapter::addAction(__FUNCTION__, $jQuery_Action);

        return Genv_Jquery_Adapter::$jQuery;
    }
    /**
     * evalScript
     *
     * @param  string $foo
     * @return jQuery
     */
    public static function evalScript ($foo)
    {
        Genv_Jquery_Adapter::init();

        $jQuery_Action = new jQuery_Action();
        $jQuery_Action ->add("foo", $foo);

        Genv_Jquery_Adapter::addAction(__FUNCTION__, $jQuery_Action);

        return Genv_Jquery_Adapter::$jQuery;
    }

    /**
     * response
     * init singleton if needed
     *
     * @return string JSON
     */
    /*
    public static function getResponse()
    {
        Genv_Jquery_Adapter::init();
        $json =  Genv::factory('Genv_Json');
        echo $json->encode(Genv_Jquery_Adapter::$jQuery->response);
        exit ();
    }
    */
    public static function fetch()
    {
        Genv_Jquery_Adapter::init();
        $json =  Genv::factory('Genv_Json');
        echo $json->encode(Genv_Jquery_Adapter::$jQuery->response);
        exit ();
    }

    /**
     * addQuery
     * add query to stack
     *
     * @return jQuery_Element
     */
    public static function addQuery($selector)
    {
        Genv_Jquery_Adapter::init();

        return new jQuery_Element($selector);
    }

    /**
     * addQuery
     * add query to stack
     *
     * @param  jQuery_Element $jQuery_Element
     * @return void
     */
    public static function addElement(jQuery_Element &$jQuery_Element)
    {
        Genv_Jquery_Adapter::init();

        array_push(Genv_Jquery_Adapter::$jQuery->response['q'], $jQuery_Element);
    }


    /**
     * addAction
     * add query to stack
     *
     * @param  string $name
     * @param  jQuery_Action $jQuery_Action
     * @return void
     */
    public static function addAction($name, jQuery_Action &$jQuery_Action)
    {
        Genv_Jquery_Adapter::init();

        Genv_Jquery_Adapter::$jQuery->response['a'][$name][] = $jQuery_Action;
    }
}