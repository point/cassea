<?php
//
// $Id:$
//
WidgetLoader::load("WContainer");
//{{{ WTable
class WTable extends WContainer
{
    var

        /**
        * @var      int
        */
        $cellpadding = null,
        /**
        * @var      int
        */
        $cellspacing = null,
        /**
        * @var      string
        */
        $frame = null,
        /**
        * @var      string
        */
        $rules = null,
        /**
        * @var      string
        */
        $border = null,
        /**
        * @var      WidgetCollection&
        */
        $items = null   ,
        /**
        * @var      string
        */
		$width = null,
        /**
        * @var      string
        */
		$summary = null
			
		;
        
    // {{{ __construct
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    */
    function __construct($id=null)
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
		if(isset($elem['cellpadding']))
	        $this->setCellpadding((string)$elem['cellpadding']);
		if(isset($elem['cellspacing']))
	       	$this->setCellspacing ((string)$elem['cellspacing']);
		if(!empty($elem['frame']))
	       	$this->setFrame((string)$elem['frame']);
		if(!empty($elem['rules']))
	       	$this->setRules((string)$elem['rules']);
		if(!empty($elem['border']))
	       	$this->setBorder((string)$elem['border']);
		if(!empty($elem['width']))
	       	$this->setWidth((string)$elem['width']);
		if(!empty($elem['summary']))
	       	$this->setSummary((string)$elem['summary']);

			$this->items = new WidgetCollection($elem);

		$this->addToMemento(array("cellspacing","cellpadding","frame","rules","border","width","summary"));
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
			"cellpadding"=>(isset($this->cellpadding))?"cellpadding=\"".$this->getCellpadding()."\"":"",
			"cellspacing"=>(isset($this->cellspacing))?"cellspacing=\"".$this->getCellspacing()."\"":"",
			"frame"=>(isset($this->frame))?"frame=\"".$this->getFrame()."\"":"",
			"rules"=>(isset($this->rules))?"rules=\"".$this->getRules()."\"":"",
			"border"=>(isset($this->border))?"border=\"".$this->getBorder()."\"":"",
			"width"=>(isset($this->width))?"width=\"".$this->getWidth()."\"":"",
			"summary"=>(isset($this->summary))?"summary=\"".$this->getSummary()."\"":"",
			"table_content"=>$this->items->generateAllHTML()
		));
		parent::assignVars();
    }
	// }}}	

    // {{{ setCellpadding 
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $cellpadding    
    * @return   void
    */
    function setCellpadding($cellpadding)
    {
		if(!isset($cellpadding) || !is_numeric($cellpadding))
			return;
		$this->cellpadding = 0 + $cellpadding;

    }
    // }}}
    
    // {{{ getCellpadding 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getCellpadding()
    {
		return $this->cellpadding;
    }
    // }}}
    
    // {{{ setCellspacing 
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $cellspacing    
    * @return   void
    */
    function setCellspacing($cellspacing)
    {
		if(!isset($cellspacing) || !is_numeric($cellspacing))
			return;
		$this->cellspacing = 0 + $cellspacing;
    }
    // }}}
    // {{{ getCellspacing 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getCellspacing()
    {
		return $this->cellspacing;
    }
    // }}}
    
    // {{{ setFrame 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $frame    
    * @return   void
    */
    function setFrame($frame)
    {
    	if(!isset($frame) || !in_array($frame,array("void" , "above" , "below" , "hsides" , "lhs" , "rhs" , "vsides" , "box" , "border")))
			return;
		$this->frame = "". $frame;
    }
    // }}}
    
    // {{{ getFrame 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getFrame()
    {
		return $this->frame;
    }
    // }}}
    
    // {{{ setBorder
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $border
    * @return   void
    */
    function setBorder($border)
    {
		if(!isset($border) || !is_numeric($border))
			return;
		$this->border = 0+ $border;
    }
    // }}}
    // {{{ getBorder
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getBorder()
    {
		return $this->border;
    }
    // }}}
    // {{{ setSummary
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $summary
    * @return   void
    */
    function setSummary($summary)
    {
	   	if(empty($summary) || !is_scalar($summary))
			return;
		$this->summary = "". $summary;
    }
    // }}}
    // {{{ getSummary
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getSummary()
    {
		return $this->summary;
    }
    // }}}

	// {{{ setRules 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $rules
    * @return   void
    */
    function setRules($rules)
    {
	   	if(!isset($rules) || !in_array($rules,array("none" , "groups" , "rows" , "cols" , "all")))
			return;
		$this->rules = "". $rules;
    }
    // }}}
    // {{{ getRules 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getRules()
    {
		return $this->rules;
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
		$this->width = (string)$width;
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
    * @param    mixed $data
    * @return   void
    */
    function setData(ResultSet $data)
	{
		if($data->getFor() != $this->getId()) return;

		$this->restoreMemento();

		$this->setCellspacing($data->get('cellspacing'));
		$this->setCellpadding($data->get('cellpadding'));
		$this->setFrame($data->get('frame'));
		$this->setBorder($data->get('border'));
		$this->setRules($data->ge('rules'));
		$this->setWidth($data->get('width'));
		$this->setSummary($data->get('summary'));

		parent::setData($data);
    }
    //}}}

}
//}}}

?>
