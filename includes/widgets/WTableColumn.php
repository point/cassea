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

		$this->items = new MixedCollection($this->getId(),$elem);
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
    function setData(WidgetResultSet $data)
	{
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
