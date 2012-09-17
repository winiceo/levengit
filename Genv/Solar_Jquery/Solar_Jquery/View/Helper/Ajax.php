<?php
class Genv_View_Helper_Ajax extends Genv_View_Helper
{
    /**
     *
     * Returns an ajax event string or array.
     * Usage 1:
     * $data = array('title'=> 'i love you', 'content' => 'Thanks');
     * echo $this->action('/blog/ajax', 'Roy',
     * $this->ajax('/blog/ajax', 'onclick', $data, array('class'=> 'ajax'), false));\?>
     *
     * Usage 2:
     * <button type="button" <?php echo $this->ajax('/blog/ajax', 'onMouseOver',
     * $data, array('class'=> 'ajax'));\?>>提交</button>
     *
     * @param string|Genv_Uri_Action $spec The action specification.
     *
     * @param string $event JavaScript event.
     *
     * @param array $data Ajax Post data
     *
     * @param array $attribs Additional attributes for the anchor.
     *
     * @param boolean $isHtml indicate whether to use raw html or Genv view helper
     *
     * @return mixed if $isHtml is true, return string else return array
     *
     *
     */
    public function ajax($spec, $event, array $data = array(), array $attribs = array(), $isHtml = true)
    {
        $href = $this->_view->actionHref($spec);
        $json =  Genv::factory('Genv_Json');
        $data = $json->encode($data);
        $attribs[$event] = 'javascript:$.php("'.$href.'",'.$data.');return false;';
        if ($isHtml) {
            $attribs = $this->_view->attribs($attribs);
        }
        $this->_view->head()->addScriptBase('http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js');
        $this->_view->head()->addScriptBase('Genv/View/Helper/Pager/jquery.php.js');
        return $attribs;
    }
}