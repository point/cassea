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
//{{{ WInlineScript
class WInlineScript extends WComponent
{
    protected
		/**
		* @var string
		*/
        $condition = null,

		/**
		* @var string
		*/
		$code = null
		;

    // {{{ __construct
    /**
    * Method description
    *
    * More detailed method description
    * @param    array $elem
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
    * @param    array $elem
    * @return void
    */
    function parseParams(SimpleXMLElement $elem)
    {
		if(isset($elem) && (string)$elem != "")
			$this->setCode((string)$elem);

		if(isset($elem['condition']))
			$this->setCond((string)$elem['condition']);
		
		parent::parseParams($elem);
    }
    // }}}
    
    // {{{ setCode
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $code
    * @return   void
    */
    function setCode($code)
    {
		if(empty($code)  || !is_scalar($code)) return; 
		$this->code = "".$code;
    }
    // }}}
    
    // {{{ getCode
    /**
    * Method description
    *
    * More detailed method description
    * @return   string
    */
    function getCode()
    {
		return $this->code;
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
			'code'=> $this->getCode(),
			"condition"=>$this->getCond()
		));
		parent::assignVars();
    }
	// }}}	
	
    // {{{ setCond
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $cond
    * @return   void
    */
    function setCond($cond)
    {
		if(!isset($cond) || !is_scalar($cond)) 
            return ;
        $this->condition = (string)$cond;
    }
    // }}}
    
    // {{{ getCond
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getCond()
    {
		return $this->condition;
    }
    // }}}
}
//}}}

?>
