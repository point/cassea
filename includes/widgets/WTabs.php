<?php
//
// $Id:$
//
WidgetLoader::load("WContainer");
//{{{ WTabPane
class WTabs extends WContainer
{
    var

        /**
        * @var      WidgetCollection&
        */
        $tabs = null;
    
    // {{{ WTabPane 
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
    // {{{ parseParams
    /**
    * Method description
    *
    * More detailed method description
    * @param    array
    * @return void
    */
    function parseParams(SimpleXMLElement $elem)
    {
		$this->tabs = new WidgetCollection($elem);
		parent::parseParams($elem);		    	
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
		$controller->addScript("ui.core.js");
		$controller->addScript("ui.tabs.js");
		$controller->addCSS("ui.tabs.css");

		$this->tabs->filter("WTab");
		parent::buildComplete();
	}    
	// }}}
    // {{{ preRender
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   void
    */
    function preRender()
    {
		if(isset($this->dataset))
			$this->setData($this->dataset->getData($this->getID()));
		parent::preRender();
    }
	// }}}    
    // {{{ assignVars
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   void
    */
    function assignVars()
    {
		$hrefs = array();
		$titles = array();
		$selected = 0;
		for($i = 0, $c = $this->tabs->count(); $i < $c; $i++)
		{
			if($this->tabs->getItem($i)->getHref())
				$hrefs[$i] = $this->tabs->getItem($i)->getHref();
			else $hrefs[$i] = "#".$this->tabs->getItem($i)->getId();
			$titles[$i] = $this->tabs->getItem($i)->getTabTitle();
			if($this->tabs->getItem($i)->getSelected())
				$selected = $i;
		}
		$this->tpl->setParams(t(new TemplateParams())
			->set("href",$hrefs)
			->set("title",$titles)
			->set("tabs",$this->tabs->generateAllHTML())
			->set('selected',$selected));
		parent::assignVars();
    }
 	// }}}	
}
//}}}

?>
