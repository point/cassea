<?php
//
// $Id:$
//
//{{{ WTableColumn
class WTableColumn extends WContainer
{
    var

        /**
        * @var      string
        */
        $align = null ,
        /**
        * @var      int
        */
        $colspan = null,
        /**
        * @var      int
        */
        $rowspan = null,
        /**
        * @var      string
        */
        $valign = null,
        /**
        * @var     string 
        */
        $width = null,
        /**
        * @var      array
        */
        $items    ;
        
    
    // {{{ __construct
    /**
    * Method description
    *
    * More detailed method description
    * @param    array
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
		if(isset($elem['colspan']))
			$this->setColspan((string)$elem['colspan']);
		if(isset($elem['rowspan']))
			$this->setRowspan((string)$elem['rowspan']);
		if(isset($elem['width']))
			$this->setWidth((string)$elem['width']);

		$this->items = new MixedCollection($elem);
		$this->addToMemento(array("align","valign","colspan","rowspan","width"));
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
			"align"=>(isset($this->align))?"align=\"".$this->getAlign()."\"":"",
			"valign"=>(isset($this->valign))?"valign=\"".$this->getValign()."\"":"",
			"colspan"=>(isset($this->colspan))?"colspan=\"".$this->getColspan()."\"":"",
			"rowspan"=>(isset($this->rowspan))?"rowspan=\"".$this->getRowspan()."\"":"",
			"width"=>(isset($this->width))?"width=\"".$this->getWidth()."\"":"",
			"column_content"=>$this->items->generateAllHTML()
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
		if(!isset($align) || !in_array($align,array("left" , "center" , "right" , "justify" )))
			return;
		$this->align = "". $align;
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
		return $this->align;
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
		$this->valign = "". $valign;

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
    // {{{ setColspan
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $colspan
    * @return   void
    */
    function setColspan($colspan)
    {
		if(!isset($colspan) || !is_numeric($colspan))
			return;
		$this->colspan = 0 + $colspan;

    }
    // }}}
    
    // {{{ getColspan
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getColspan()
    {
		return $this->colspan;
    }
    // }}}
    // {{{ setRowspan
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $colspan
    * @return   void
    */
    function setRowspan($rowspan)
    {
		if(!isset($rowspan) || !is_numeric($rowspan))
			return;
		$this->rowspan = 0 + $rowspan;

    }
    // }}}
    
    // {{{ getRowspan
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getRowspan()
    {
		return $this->rowspan;
    }
    // }}}
	// {{{ setWidth 
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $width    
    * @return   void
    */
    function setWidth($width)
    {
		if(!isset($width) || !is_scalar($width)) 
			return ;
		$this->width = "".$width;
    }
    // }}}
    
    // {{{ getWidth 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getWidth()
    {
		return $this->width;
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
		
		$this->setWidth($data->get('width'));
		$this->setAlign($data->get('align'));
		$this->setAlign($data->get('valign'));
		$this->setColspan($data->get('colspan'));
		$this->setRowspan($data->get('rowspan'));

		parent::setData($data);
    }
    // }}}
}
//}}}

?>
