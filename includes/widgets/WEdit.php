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
WidgetLoader::load("WControl");
//{{{ WEdit
class WEdit extends WControl
{
    protected

        /**
        * @var      int
        */
        $maxlength = 255, 
        /**
        * @var      int
        */
        $size = 40,
        /**
        * @var      string
        */
		$type = "text"
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
		if(isset($elem['maxlength']))
			$this->setMaxLength((string)$elem['maxlength']);
		if(isset($elem['size']))
			$this->setSize((string)$elem['size']);
		if(isset($elem['type']))
			$this->setType((string)$elem['type']);

		$this->addToMemento(array("maxlength","size","type"));

		parent::parseParams($elem);		    	
    }
    // }}}

    // {{{ setMaxLength 
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $maxlength    
    * @return   void
    */
    function setMaxLength($maxlength)
    {
		if(!isset($maxlength) || !is_scalar($maxlength) || (0+$maxlength > 1024))
			return;
		$this->maxlength = 0 + $maxlength;
    }
    // }}}
    
    // {{{ getMaxLength 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getMaxLength()
    {
		return $this->maxlength;
    }
    // }}}
    
    // {{{ setSize 
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $size    
    * @return   void
    */
    function setSize($size)
    {
		if(!isset($size) || !is_scalar($size) || (0+$size) > 1024)
			return;
		$this->size = 0 + $size;
    }
    // }}}
    
    // {{{ getSize 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getSize()
    {
		return $this->size;
    }
    // }}}
    
    // {{{ setType 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $type    
    * @return   void
    */
    function setType($type)
    {
		if(!isset($type) || ($type != "text" && $type != "password"))
			return;
        $this->type = "".$type;
    }
    // }}}
    // {{{ getType 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getType()
    {
		return $this->type;
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
		$this->setMaxLength($data->get('maxlength'));
		$this->setSize($data->get('size'));
		$this->setType($data->get('type'));

		parent::setData($data);
    }
    //}}}
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
			"type"=>$this->getType(),
			"maxlength"=>$this->getMaxLength(),
			"size"=>$this->getSize()	
		));
		parent::assignVars();
    }
	// }}}	
}
//}}}

?>
