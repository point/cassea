<?php
//
// $Id:$
//
WidgetLoader::load("WControl");
//{{{ WTextarea
class WTextarea extends WControl
{
    protected

        /**
        * @var      int
        */
        $cols = 60,
        /**
        * @var      int
        */
        $rows = 10   ;
    
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
		if(!empty($elem['cols']))
			$this->setCols((string)$elem['cols']);
		if(!empty($elem['rows']))
	       	$this->setRows((string)$elem['rows']);

		$this->addToMemento(array("cols","rows"));

		parent::parseParams($elem);		    	
    }
    // }}}
    // {{{ setRows 
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $rows    
    * @return   void
    */
    function setRows($rows)
    {
		if(!isset($rows) || !is_numeric($rows))
			return;
		$this->rows = 0 + $rows;
    }
    // }}}
	
    // {{{ getRows 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getRows()
    {
		return $this->rows;
    }
    // }}}
    
    // {{{ setCols  
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $cols    
    * @return   void
    */
    function setCols($cols)
    {
		if(!isset($cols) || !is_numeric($cols))
			return;
		$this->cols = 0 + $cols;
    }
    // }}}
    
    // {{{ getCols 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getCols()
    {
		return $this->cols;
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
		if($this->getId() != $data->getFor()) return;
		$this->restoreMemento();
		$this->setCols($data->get('cols'));
		$this->setRows($data->get('rows'));

		parent::setData($data);
    }
    //}}}
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
			"cols"=>$this->getCols(),
			"rows"=>$this->getRows()
		));
		parent::assignVars();
    }
	// }}}	
}
//}}}

?>
