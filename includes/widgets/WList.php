<?php
//
// $Id:$
//
WidgetLoader::load("WContainer");
//{{{ WList
class WList extends WContainer
{
    var

        /**
        * @var      boolean
        */
        $is_ul = 1,
        /**
        * @var      boolean
        */
        $is_ol = 0,
        /**
        * @var      WidgetCollection&
        */
		$items = null,
        /**
        * @var      boolean
        */
		$item_created = 0

		    ;
    
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
       	if(!empty($elem['ul']))
			{$this->setIsUl((string)$elem['ul']); $this->setIsOl(0);}
		if( !empty($elem['ol']))
			{$this->setIsOl((string)$elem['ol']); $this->setIsUl(0);}
		$this->items = new IterableCollection($elem);

		$this->addToMemento(array("is_ul","is_ol"));
		
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
		if($this->getIsUl())
			$this->setTemplate("ul");
		if($this->getIsOl())
			$this->setTemplate("ol");

		if(!isset($this->tpl))
			$this->tpl = $this->createTemplate();

		$this->items->filter("WListItem");
		/*if(empty($this->items))
		{
			$b1 = new WBuilder(array(
				"widget_name"=>"WListItem",
				"id"=>"li".$this->id
			));
			$this->items = new WidgetCollection(array($b1->build()));
			$this->item_created = 1;
		}*/

		parent::buildComplete();
	}    
	// }}}
  
    // {{{ setIsUl 
    /**
    * Method description
    *
    * More detailed method description
    * @param    boolean $is_ul    
    * @return   void
    */
    function setIsUl($is_ul)
    {
		if(!isset($is_ul) || !is_scalar($is_ul)) return;

		$this->is_ul = 0+$is_ul;
    }
    // }}}
    
    // {{{ getIsUl 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   boolean
    */
    function getIsUl()
    {
		return $this->is_ul;
    }
    // }}}
    
    // {{{ setIsOl 
    /**
    * Method description
    *
    * More detailed method description
    * @param    boolean $is_ol    
    * @return   void
    */
    function setIsOl($is_ol)
    {
		if(!isset($is_ol) || !is_scalar($is_ol)) return;

		$this->is_ol = 0 + $is_ol;
    }
    // }}}
    
    // {{{ getIsOl 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   boolean
    */
    function getIsOl()
    {
		return $this->is_ol;
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
		//if(!$this->items->data_setted && $this->item_created) $this->items->clear();
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
					"li_content"=>$this->items->generateAllHTML()
				));
		parent::assignVars();
    }
	// }}}	
}
//}}}

?>
