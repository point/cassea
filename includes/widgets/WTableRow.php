<?php
//
// $Id:$
//
//{{{ WTableRow
class WTableRow extends WContainer
{
    protected

        /**
        * @var      string
        */
        $align = null,
        /**
        * @var      string
        */
        $valign = null,
        /**
        * @var		WidgetCollection&
        */
		$items = null;

	private 
		$thead = 0	;
    
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
		if(isset($elem['align']))
			$this->setAlign((string)$elem['align']);
		if(isset($elem['valign']))
			$this->setValign((string)$elem['valign']);

		$this->items = new WidgetCollection($elem);

		$this->addToMemento(array("align","valign"));
		parent::parseParams($elem);		    	
    }
    // } }}
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
		if($this->items->has("WTableHeader"))
			$this->thead = 1;
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
			"align"=>(isset($this->align))?"align=\"".$this->getAlign()."\"":"",
			"valign"=>(isset($this->valign))?"valign=\"".$this->getValign()."\"":"",
			"row_content"=>$this->items->generateAllHTML(),
			"thead"=>$this->thead
		));
		parent::assignVars();
    }
	// }}}	

    // {{{ setAlign 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $align    
    * @return   void
    */
    function setAlign($align)
    {
		if(!isset($align) || !in_array($align,array("left" , "center" , "right" , "justify")))
			return;
		$this->align = "".$align;
		
    }
    // }}}
    // {{{ getAlign 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getAlign()
    {
		return  $this->align;
    }
    // }}}
    
    // {{{ setValign 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $valign    
    * @return   void
    */
    function setValign($valign)
    {
		if(!isset($valign) || !in_array($valign,array("top" , "middle" , "bottom" , "baseline")))
			return;
		$this->valign = "".$valign;

    }
    // }}}
    // {{{ getValign 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getValign()
    {
		return $this->valign;
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

		$this->setAlign($data->get('align'));
		$this->setValign($data->get('valign'));

		parent::setData($data);
    }
    //}}}
}
//}}}

?>
