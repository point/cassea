<?php
//
// $Id:$
//
WidgetLoader::load("WTableColumn");
WidgetLoader::load("WHelper");
//{{{ WTableHeader
class WTableHeader extends WTableColumn
{
    protected
        /**
        * @var      string
        */
		$sortby = null,
        /**
        * @var      WidgetCollection&
		*/
		$sorter = null
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
		if(isset($elem['sortby']))
			$this->setSortBy((string)$elem['sortby']);

		$this->addToMemento(array("sortby"));

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

		Controller::getInstance()->getDispatcher()->addEvent("TableHeader_sortby");	

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
		parent::preRender();
		if(isset($this->sortby))
		{
			$controller = Controller::getInstance();
			
			if(($mode = $controller->get->{"sortby".$this->getId()}) && ($mode === "asc" || $mode === "desc"))
			{
				$controller->getDispatcher()->notify(
					new Event("TableHeader_sortby",$this->getId(),null,array("mode"=>$mode,"sortby"=>$this->getSortBy()))
					);	
			}
			else $mode = "desc";

			$b = new WComplexBuilder("WHyperLink",array("href"=>$controller->makeURL(null,null,null,array("sortby".$this->getId()=>$mode=="asc"?"desc":"asc"))));
			$b->addValue(new WBuilder("WImage",array("src"=>$mode == "desc"?"/w_images/s_asc.gif":"/w_images/s_desc.gif")));

			$this->sorter = new WidgetCollection($b->build());
		}
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
		if(isset($this->sortby))
			$this->tpl->setParamsArray(array(
				"sorter"=>$this->sorter->generateAllHTML()
			));
		parent::assignVars();
    }
	// }}}	

   // {{{ setSortBy
    function setSortBy($sortby)
    {
		if(!isset($sortby) || !is_scalar($sortby))
			return;
		$this->sortby = "".$sortby;
    }
    // }}}
	
    // {{{ getSortBy
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getSortBy()
    {
		return $this->sortby;
    }
    // }}}

	// {{{ setData
    /**
    * Method description
    *
    * More detailed method description
    * @param    array $data  
    * @return   void
    */
    function setData(ResultSet $data)
    {
		if($data->getFor() != $this->getId()) return;
		$this->restoreMemento();
		$this->setSortBy($data->get('sortby'));

		parent::setData($data);
    }
    // }}}

}
// }}}

?>
