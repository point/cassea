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
// $Id: $
//
WidgetLoader::load("WJSEvent");
//{{{ WJavaScript
class WJavaScript extends WObject
{
    protected

        /**
        * @var     WJSEvent& 
        */
        $onclick = null,
        /**
        * @var      WJSEvent&
        */
        $ondblclick = null,
        /**
        * @var      WJSEvent&
        */
        $onmousedown = null,
        /**
        * @var      WJSEvent&
        */
        $onmouseup = null,
        /**
        * @var      WJSEvent&
        */
        $onmouseover = null,
        /**
        * @var      WJSEvent&
        */
        $onmousemove = null,
        /**
        * @var      WJSEvent&
        */
        $onmouseout = null,
        /**
        * @var      WJSEvent&
        */
        $onkeypress = null,
        /**
        * @var      WJSEvent&
        */
        $onkeydown = null,
        /**
        * @var      WJSEvent&
        */
        $onkeyup = null,
        /**
        * @var      array
        */
        $beforewidget = array(),
        /**
        * @var      array
        */
        $afterwidget     = array();
	private
    	/**
        * @var      string
        */
		$src = null
		;

    // {{{ generateJS 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function generateJS()
    {
		$vars = get_object_vars($this);
		$final_js = "";
		foreach($vars as $k => $v)
		{
			if($k == "beforewidget" || $k == "afterwidget" || $k == "id" || $k == "log" || $k == "src") continue;
			if(isset($this->$k))
				$final_js .= $k."=\"".$this->$k->generateJS()."\"";
		}
		return $final_js;
    }
    // }}}
    
    // {{{ __construct
    /**
    * Method description
    *
    * More detailed method description
    * @param    array $params
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
    * @param    array $params
    * @return void
    */
    function parseParams(SimpleXMLElement $params = null)
    {
		if(isset($params)) 
			foreach($params->attributes() as $k => $v)
				$this->setAttribute((string)$k,(string)$v);

		if(isset($params->before))
			$this->addBeforeWidget((string) $params->before);
		if(isset($params->after))
			$this->addAfterWidget((string) $params->after);
    }
    // }}}
    
    // {{{ setAttribute 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $attribute    
    * @param    mixed $value    
    * @return   void
    */
    function setAttribute($attribute, $value)
    {
		if(empty($attribute)  || !isset($value) || $attribute == "id" ||
			!property_exists_safe(get_class($this),$attribute)) 
			return;
		if($attribute == "src" )
		{
			$controller = Controller::getInstance();
			$controller->addScript($value);
			return;
		}	
		if(!isset($this->$attribute))
			$this->$attribute = new WJSEvent();
		$this->$attribute->add($value);
    }
    // }}}
    
    // {{{ getAttribute 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $attribute    
    * @return   mixed
    */
    function getAttribute($attribute)
    {
		if(empty($attribute) || !property_exists_safe(get_class($this),$attribute)) 
			return;
		if($attribute == "src")
			return $this->src;
		return $this->$attribute->generateJS();
    }
    // }}}
    // {{{ addToConditional
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $attribute    
    * @param    mixed $value    
    * @return   void
    */
    function addConditional($attribute, $value)
    {
		if(empty($attribute) || empty($value) || !property_exists_safe(get_class($this),$attribute)) 
			return;
		if(!isset($this->$attribute))
			$this->$attribute = new WJSEvent();
		$this->$attribute->addToConditional($value);
    }
    // }}}
    // {{{ addToPlain
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $attribute    
    * @param    mixed $value    
    * @return   void
    */
    function addPlain($attribute, $value)
    {
		if(empty($attribute) || empty($value) || !property_exists_safe(get_class($this),$attribute))
			return;
		if(!isset($this->$attribute))
			$this->$attribute = new WJSEvent();
		$this->$attribute->addToPlain($value);
    }
    // }}}
    // {{{ addBeforeWidget
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $str    
    * @return   void
    */
    function addBeforeWidget($str)
    {
		if(empty($str) || !is_string($str)) return; 
		$this->beforewidget[] = $str;
    }
    // }}}
    // {{{ getBeforeWidget
    /**
    * Method description
    *
    * More detailed method description
    * @return   string
    */
    function getBeforeWidget()
    {
		if(!count($this->beforewidget)) return "";
		return "<script type=\"text/javascript\">".implode("\n",$this->beforewidget)."</script>";
    }
    // }}}

    // {{{ addAfterWidget
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $str    
    * @return   void
    */
    function addAfterWidget($str)
    {
		if(empty($str) || !is_string($str)) return; 
		$this->afterwidget[] = $str;
    }
    // }}}
    // {{{ getAfterWidget
    /**
    * Method description
    *
    * More detailed method description
    * @return   string
    */
    function getAfterWidget()
    {
		if(!count($this->afterwidget)) return "";
		return "<script type=\"text/javascript\">".implode("\n",$this->afterwidget)."</script>";
    }
    // }}}
}
//}}}

?>
