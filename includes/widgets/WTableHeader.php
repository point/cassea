<?php
//
// $Id:$
//
WidgetLoader::load("WTableColumn");
//{{{ WTableHeader
class WTableHeader extends WTableColumn
{
    protected
        /**
        * @var      bool
		*/
		$sorter = 1
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
		if(isset($elem['sorter']))
			$this->setSorter((string)$elem['sorter']);

		$this->addToMemento(array("sorter"));

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
		if(!$this->getSorter())
			$this->setStyleClass("{sorter:false}");
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
		parent::assignVars();
    }
	// }}}	

   // {{{ setSorter
    function setSorter($sorter)
    {
		if(!isset($sorter) || !is_scalar($sorter))
			return;
		$this->sorter = 0+$sorter;
    }
    // }}}
	
    // {{{ getSorter
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getSorter()
    {
		return $this->sorter;
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
