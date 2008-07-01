<?php
//
// $Id:$
//
WidgetLoader::load("WContainer");
//{{{ WTab
class WTab extends WContainer
{
    var

        /**
        * @var      string
        */
        $tab_title = null,
        /**
        * @var		MixedCollection&
        */
		$items = null,
        /**
        * @var      string
        */
		$href = null,
        /**
		* @var      bool
        */
		$selected = 0

        		
		;
    // {{{ WTabPanePage 
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
		if(isset($elem['tab_title'])) 
			$this->setTabTitle((string)$elem['tab_title']);
		if(isset($elem['href'])) 
			$this->setHref((string)$elem['href']);
		if(isset($elem['selected'])) 
			$this->setSelected((string)$elem['selected']);
		$this->items = new MixedCollection($elem);
		$this->addToMemento(array("tab_title","href","selected"));
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
			$this->setData($this->dataset->getData($this->getId()));
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
		$this->tpl->setParamsArray(array(
			"content" => $this->items->generateAllHTML()
		));
	
		parent::assignVars();
    }
	// }}}	

    // {{{ setData 
    /**
    * Method description
    *
    * More detailed method description
    * @param    mixed $data
    * @return   void
    */
    function setData(ResultSet $data)
    {
		if($data->getFor() != $this->getId()) return;	
		$this->restoreMemento();
		
		$this->setTabTitle($data->get('tab_title'));
		$this->setHref($data->get('href'));
		$this->setSelected($data->get('selected'));
		parent::setData($data);
    }
    //}}}

    // {{{ setTabTitle 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $title
    * @return   void
    */
    function setTabTitle($title)
    {
    	if(empty($title) || !is_scalar($title))
			return;
    	$this->tab_title = $title;
	}
    // }}}
    
    // {{{ getTabTitle 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getTabTitle()
    {
		return $this->tab_title;
    }
     // }}}

	// {{{ setHref
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $href
    * @return   void
    */
    function setHref($href)
    {
    	if(empty($href) || !is_scalar($href))
			return;
    	$this->href = $href;
	}
    // }}}
    
    // {{{ getHref
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getHref()
    {
		return $this->href;
    }
     // }}}
	
	// {{{ setSelected
    /**
    * Method description
    *
    * More detailed method description
    * @param    bool $selected
    * @return   void
    */
    function setSelected($selected)
    {
    	if(!isset($selected) || !is_scalar($selected))
			return;
    	$this->selected = 0+$selected;
	}
    // }}}
    
    // {{{ getSelected
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   bool
    */
    function getSelected()
    {
		return $this->selected;
    }
     // }}}
}
//}}}

?>
