<?php
/*- vim:noet:shiftwidth=4:tabstop=4: 
{{{ LICENSE  
* Copyright (c) 2008,2009 Cassea Project
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*     * Redistributions of source code must retain the above copyright
*       notice, this list of conditions and the following disclaimer.
*     * Redistributions in binary form must reproduce the above copyright
*       notice, this list of conditions and the following disclaimer in the
*       documentation and/or other materials provided with the distribution.
*     * Neither the name of the Cassea Project nor the
*       names of its contributors may be used to endorse or promote products
*       derived from this software without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY CASSEA PROJECT ''AS IS'' AND ANY
* EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
* DISCLAIMED. IN NO EVENT SHALL CASSEA PROJECT BE LIABLE FOR ANY
* DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
}}} -*/


//
// $Id$
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
		$summary = null,
        /**
        * @var      bool
        */
		$table_sorter = 0,
	    /**
        * @var		string
		*/
		$odd_class = null,
	    /**
        * @var		string
		*/
		$even_class = null,
	    /**
        * @var		string
		*/
		$hover_class = null
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
		if(isset($elem['frame']))
	       	$this->setFrame((string)$elem['frame']);
		if(isset($elem['rules']))
	       	$this->setRules((string)$elem['rules']);
		if(isset($elem['border']))
	       	$this->setBorder((string)$elem['border']);
		if(isset($elem['width']))
	       	$this->setWidth((string)$elem['width']);
		if(isset($elem['summary']))
	       	$this->setSummary((string)$elem['summary']);
		if(isset($elem['table_sorter']))
	       	$this->setTableSorter((string)$elem['table_sorter']);

		if(isset($elem['odd']))
			$this->setOddClass((string)$elem['odd']);
		if(isset($elem['even']))
			$this->setEvenClass((string)$elem['even']);
		if(isset($elem['hover']))
			$this->setHoverClass((string)$elem['hover']);

		$this->items = new WidgetCollection($this->getId(),$elem);

		$this->addToMemento(array("cellspacing","cellpadding","frame","rules","border","width","summary",
			"table_sorter","odd_class","even_class","hover_class"));
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

		if($this->getTableSorter())
		{
			$controller = Controller::getInstance();
			$controller->addScript("jquery.metadata.js");
			$controller->addScript("jquery.tablesorter.js");
			$controller->addCSS("jquery.tablesorter.css");
			$this->addStyleClass("tablesorter");
		}
		parent::buildComplete();
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
			"table_content"=>$this->items->generateAllHTML(),
			"table_sorter"=>$this->getTableSorter(),
			"odd_class"=>$this->getOddClass(),
			"even_class"=>$this->getEvenClass(),
			"hover_class"=>$this->getHoverClass()
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
	
   // {{{ setTableSorter
    function setTableSorter($sorter)
    {
		if(!isset($sorter) || !is_scalar($sorter))
			return;
		$this->table_sorter = 0+$sorter;
    }
    // }}}
	
    // {{{ getTableSorter
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getTableSorter()
    {
		return $this->table_sorter;
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
    function setData(WidgetResultSet $data)
	{
		$this->setCellspacing($data->get('cellspacing'));
		$this->setCellpadding($data->get('cellpadding'));
		$this->setFrame($data->get('frame'));
		$this->setBorder($data->get('border'));
		$this->setRules($data->get('rules'));
		$this->setWidth($data->get('width'));
		$this->setSummary($data->get('summary'));

		$this->setOddClass($data->get('odd'));
		$this->setEvenClass($data->get('even'));
		$this->setHoverClass($data->get('even'));

		parent::setData($data);
    }
    //}}}

    // {{{ setEvenClass
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $even_class
    * @return   void
    */
    function setEvenClass($even_class)
    {
		if(!isset($even_class) || !is_scalar($even_class))
			return;
		$this->even_class = "".$even_class;

    }
    // }}}
    // {{{ getEvenClass
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getEvenClass()
    {
		return $this->even_class;
    }
    // }}}

    // {{{ setOddClass
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $odd_class
    * @return   void
    */
    function setOddClass($odd_class)
    {
		if(!isset($odd_class) || !is_scalar($odd_class))
			return;
		$this->odd_class = "".$odd_class;

    }
    // }}}
    // {{{ getOddClass
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getOddClass()
    {
		return $this->odd_class;
    }
    // }}}
	
    // {{{ setHoverClass
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $hover_class
    * @return   void
    */
    function setHoverClass($hover_class)
    {
		if(!isset($hover_class) || !is_scalar($hover_class))
			return;
		$this->hover_class = "".$hover_class;

    }
    // }}}
    // {{{ getHoverClass
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getHoverClass()
    {
		return $this->hover_class;
    }
    // }}}
}
//}}}

?>
