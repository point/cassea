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
WidgetLoader::load("WComponent");
//{{{ WControl
abstract class WControl extends WComponent
{
    protected

        /**
        * @var      string
        */
        $name = "",
        /**
        * @var      string
        */
        $datahandler = null,
        /**
        * @var      int
        */
        $value = null,
        /**
        * @var      string
        */
        $alt = null,
        /**
        * @var      boolean
        */
        $readonly = 0,
        /**
        * @var      boolean
        */
        $disabled = 0,
        /**
        * @var      WValueChecker&
        */
        $valuechecker = null ,
        /**
        * @var      string
        */
		$filter_error_string = null,
        /**
        * @var      string
        */
		$additional_id = null,
        /**
        * @var      boolean
        */
		$name_w_braces = 0
		
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
		if(isset($id))
			$this->setName($id);
		parent::__construct($id);
		/*if(!isset($id))
            $this->setName($this->getId());*/
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
		$val = (string)$elem;
		if(isset($val))
			$this->setValue($val);
		if(isset($elem['value']))
			$this->setValue((string)$elem['value']);

		if(isset($elem['name']))
		{
	       	$this->setName((string) $elem['name'] );
            // DISALLOW. Id sets in constructor
			/*if(!isset($elem['id']))
				$this->setId((string)$elem['name']);*/
		}
		if(isset($elem['readonly']))
			$this->setReadOnly((string)$elem['readonly'] );
		if(isset($elem['disabled']))
	       	$this->setDisabled((string) $elem['disabled']);

		$this->addToMemento(array("value","name","readonly","disabled","alt","additional_id","filter_error_string","name_w_braces"));

		parent::parseParams($elem);		    	
    }
    // }}}
    // {{{ setName 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $name    
    * @return   void
    */
    function setName($name = null)
    {
		if(!isset($name) || !is_scalar($name)) return ;
		$name = "".$name;
		//if(substr($name,-2,2) == "[]")
		if(strpos($name,'[') !== false)
		{
			$this->name_w_braces = 1;
			$this->name = preg_replace("/\[.*\]/","",$name);
		}
		else $this->name = $name;
    }
    // }}}
    
    // {{{ getName 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getName()
    {
		return $this->name;
    }
    // }}}
	
    // {{{ getFullName 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getFullName()
    {
		return (isset($this->additional_id))?
				($this->name.'['.$this->additional_id.']'.($this->name_w_braces?"[]":"")):
				($this->name.($this->name_w_braces?"[]":""));
    }
    // }}}
    
    // {{{ setDataHandler 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $datahandler    
    * @return   void
	*/
	// DEPRECATED
    /*function setDataHandler($datahandler)
    {
		//$datahandler == datahandler name
		if(empty($datahandler))
			return;	
		$this->datahandler = $datahandler;
	}*/
    // }}}
    
    // {{{ getDataHandler 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getDataHandler()
    {
		return $this->datahandler;
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
		$this->value = $value;//Filter::filter($value,Filter::STRING_QUOTE_ENCODE);
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
    
    // {{{ setReadOnly 
    /**
    * Method description
    *
    * More detailed method description
    * @param    boolean $readonly    
    * @return   void
    */
    function setReadOnly($readonly)
    {
		if(!isset($readonly) || !is_scalar($readonly))
			return;
		$this->readonly = 0 + $readonly;
		
    }
    // }}}
    
    // {{{ getReadOnly 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   boolean
    */
    function getReadOnly()
    {
		return $this->readonly;
    }
    // }}}
    
    // {{{ setDisabled 
    /**
    * Method description
    *
    * More detailed method description
    * @param    boolean $disabled    
    * @return   void
    */
    function setDisabled($disabled)
    {
		if(!isset($disabled) || !is_scalar($disabled))
			return;
		$this->disabled = 0 + $disabled;
		
    }
    // }}}
    
    // {{{ getDisabled 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   boolean
    */
    function getDisabled()
    {
		return $this->disabled;
    }
    // }}}
    
    // {{{ setValueChecker 
    /**
    * Method description
    *
    * More detailed method description
    * @param    WValueChecker& $valuechecker    
    * @return   void
    */
    function setValueChecker($valuechecker)
    {
		if(!$valuechecker instanceof WValueChecker)
			return;	
		$this->valuechecker = $valuechecker;
	    }
    // }}}
    
    // {{{ getValueChecker 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   WValueChecker&
    */
    function getValueChecker()
    {
		return $this->valuechecker;
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
		/*if(POSTErrors::hasErrors())
            $this->restorePOST();*/
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
    	if(isset($this->valuechecker))
    	{
			$this->valuechecker->addWidgetId($this->getId());

			$event = new Event("have_valuechecker",$this->getId());
			$event->setParams(array('id' => $this->valuechecker->getId()));
			Controller::getInstance()->getDispatcher()->notify($event);
		}
		parent::preRender();
		if(POSTErrors::hasErrors())
            $this->restorePOST();
    }
    // }}}
    // {{{ setAdditionalID
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $value    
    * @return   void
    */
    function setAdditionalID($value)
    {
		if(!isset($value) || !is_scalar($value))
			return;
		$this->additional_id = "".$value;
		//$this->name = $this->name."_".$this->additional_id;
    }
    // }}}
    
    // {{{ getAdditionalID
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getAdditionalID()
    {
		return $this->additional_id;
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
		$this->setName($data->get('name'));
		$this->setValue($data->getDef());
		$this->setValue($data->get('value'));
		$this->setReadonly($data->get('readonly'));
		$this->setAdditionalID($data->get('additional_id'));
		$this->setDisabled($data->get('disabled'));
		
		parent::setData($data);
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
			"name"=>(isset($this->additional_id))?
				($this->getName().'['.$this->additional_id.']'.($this->name_w_braces?"[]":"")):
				($this->getName().($this->name_w_braces?"[]":"")),
			"value"=>Language::encodePair($this->getValue()),
			//"alt"=>(isset($this->alt))?('alt="'.$this->alt.'"'):'',
			"readonly"=>($this->getReadonly())?('readonly="'.$this->getReadonly().'"'):'',
			"disabled"=>($this->getDisabled())?('disabled="'.$this->getDisabled().'"'):''
			));
        if($this instanceof StringProcessable)
            $this->tpl->setParams(t(new TemplateParams)->set('value',
                StringProcessorFactory::create($this->getStringProcess())->process(Language::encodePair($this->getValue()))));
        else
            $this->tpl->setParams(t(new TemplateParams)->set('value',Language::encodePair($this->getValue())));

		if(isset($this->filter_error_string))
			$this->tpl->setParamsArray(array("error_string"=>
            "<span class=\"widget_error\">".Language::encodePair($this->getFilterError())."</span>"));

		parent::assignVars();
    }
	// }}}	
    // {{{ addBracesToName
    /**
    * Method description
    *
    * More detailed method description
    * @param    boolean $add
    * @return   void
    */
    function addBracesToName($add = 1)
    {
		$this->name_w_braces = 0+$add;
    }
    // }}}
    
    // {{{ getBracesToName
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getBracesToName()
    {
		return $this->name_w_braces;
    }
    // }}}

    // {{{ restorePOST
    /**
    * Method description
    *
    * More detailed method description
    * @param    mixed $post
    * @param    array $errors
    * @return   string
    */
    function restorePOST()
	{
        $errors = POSTErrors::getErrorFor($this->getName(),$this->getAdditionalID());
    	if($errors !== null)
        {
			$this->setFilterError(implode("<br/>",$errors));
        }
        $this->setValue(POSTErrors::getPOSTData($this->getName(),$this->getAdditionalID()));
    }
    // }}}
	
    // {{{ setFilterError
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $string
    * @return   void
    */
    function setFilterError($str)
	{
		if(!isset($str) || !is_string($str)) return;

		$this->filter_error_string = $str;
	}
	// }}}
	
    // {{{ getFilterError
    /**
    * Method description
    *
    * More detailed method description
    * @return   string
    */
    function getFilterError()
	{
		return $this->filter_error_string;
	}
	// }}}
}
//}}}

?>
