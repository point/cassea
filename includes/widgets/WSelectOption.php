<?php
/*- vim:expandtab:shiftwidth=4:tabstop=4: 
{{{ LICENSE  
* Copyright (c) 2008, Cassea Project
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
// $Id: WListItem.php 45 2008-10-07 14:03:38Z point $
//
WidgetLoader::load("WComponent");
//{{{ WSelectOption
class WSelectOption extends WComponent
{
    var

        /**
        * @var      string
        */
        $text = "",
        /**
        * @var      boolean
        */
        $selected = null,
        /**
        * @var      string
        */
        $value = null

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
		if(!empty($elem['text']))
            $this->setText((string)$elem['text']);
		elseif(!count($elem))
			$this->setText((string)$elem);
        if(!empty($elem['selected']))
            $this->setSelected((string)$elem['selected']);
        if(!empty($elem['value']))
            $this->setValue((string)$elem['value']);
        else
            $this->setValue($this->getText());

		$this->addToMemento(array("text","selected","value"));
		parent::parseParams($elem);		    	
    }
    // }}}
    
    // {{{ setText 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $text
    * @return   void
    */
    function setText($text)
    {
		if(!isset($text) || !is_scalar($text))
			return;
		$this->text = "".$text;
    }
    // }}} 
    
    // {{{ getText 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getText()
    {
		return $this->text;
    }
    // }}}

    // {{{ setSelected
    /**
    * Method description
    *
    * More detailed method description
    * @param    boolean $selected
    * @return   void
    */
    function setSelected($selected)
    {
		if(!isset($selected) || !is_scalar($selected))
			return;
		$this->selected = 0+$selected;
    }
    // }}} 
    
    // {{{ getSelected
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   boolean
    */
    function getSelected()
    {
		return $this->selected;
    }
    // }}}
    
    // {{{ setValue
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $value
    * @return   void
    */
    function setValue($value)
    {
		if(!isset($value) || !is_scalar($value))
			return;
		$this->value = (string)$value;
    }
    // }}} 
    
    // {{{ getValue
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getValue()
    {
		return $this->value;
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
		$this->setData(DataRetriever::getData($this->getId()));
		parent::preRender();
    }
	// }}}    
    //  {{{ assignVars
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
            "text"=>Language::encodePair($this->getText()),
            "value"=>Language::encodePair($this->getValue()),
            "selected"=>$this->getSelected()?"selected=\"1\"":""
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
    function setData(WidgetResultSet $data)
	{
		$this->restoreMemento();

		$this->setText($data->get('text'));
        $this->setValue($data->get('value'));
        $this->setValue($data->getDef());
        $this->setSelected($data->get('selected'));

    	parent::setData($data);
    }
    //}}}
}
//}}}
?>
