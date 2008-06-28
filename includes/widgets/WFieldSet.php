<?php
//
// $Id:$
//
WidgetLoader::load("WContainer");
//{{{ WFieldSet
class WFieldSet extends WContainer
{
    var

        /**
        * @var      WidgetCollection&
        */
        $items = null,
        /**
        * @var  string
        */
		$legend_text = null
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
		if(isset($elem['legend']))
			$this->setLegend((string)$elem['legend']);
		$this->items = new WidgetCollection($elem);
		
		$this->addToMemento(array("legend_text"));

		parent::parseParams($elem);		    	
    }
    // }}}
    
    // {{{ setLegend 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $legend    
    * @return   void
    */
    function setLegend($legend)
    {
		if(!isset($legend) || !is_scalar($legend))
			return;
		$this->legend_text = (string)$legend;
    }
    // }}}
    
    // {{{ getLegend 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getLegend()
    {
		return $this->legend_text;
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
			"legend"=>$this->getLegend(),
			"fieldset_content"=>$this->items->generateAllHTML()
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
		$this->setLegend($data->get('legend'));
    	parent::setData($data);
    }
    //}}}

}
//}}}

?>
