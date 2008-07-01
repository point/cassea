<?php
//
// $Id:$
//
//{{{ WColorPicker
class WColorPicker extends WEdit
{
	var $size = 7;

    // {{{ __construct
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    */
    function __construct($id = null)
    {
   		parent::__construct($id);
    }
    // }}}
    // {{{ buildComplete
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   void
    */
	function buildComplete()
	{
		if(!isset($this->tpl))
			$this->tpl = $this->createTemplate();
		$controller = Controller::getInstance();
		$controller->addScript("jquery.colorpicker.js");
		$controller->addCSS("colorpicker.css");
		parent::buildComplete();
	}    
	// }}}
}
//}}}
?>
