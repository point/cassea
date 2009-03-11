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
// $Id$
//
WidgetLoader::load("WControl");
//{{{ WButton
class WButton extends WControl
{
    protected

        /**
        * @var      string
        */
		$type = "submit",
        /**
        * @var      string
        */
        $src = null,
        /**
        * @var      string
        */
		$alt = null   
		;

    
    // {{{ WButton 
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
        if(isset($elem['src']) /*&& $elem['type'] == "image"*/)
			$this->setSrc((string)$elem['src']);
		if(isset($elem['type']))
			$this->setType((string)$elem['type']);
		if(isset($elem['alt']))
			$this->setAlt((string) $elem['alt']);

		$this->addToMemento(array("type","src","alt"));
		parent::parseParams($elem);		    	
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
		if($type == "submit" || 
			$type == "image" ||
			$type == "reset" ||
			$type == "button")

			$this->type = $type;
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
    
    // {{{ setSrc
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $src
    * @return   void
    */
    function setSrc($src)
    {
		if(!isset($src) || !is_scalar($src))
			return;	
		$this->src = $src;

    }
    // }}}
    
    // {{{ getSrc
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getSrc()
    {
		return $this->src;
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
		if(!isset($this->value)) $this->setValue("OK");
        if(isset($this->src)) $this->setType("image");
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
        $this->checkAndSetData();

		if($this->getType() == "image")
		{
			$this->setTemplate("image");
			/*$controller = &CController::getInstance();
			$controller->addToCorrespMap($this->getName()."_x",$this->datahandler,1);
			$controller->addToCorrespMap($this->getName()."_y",$this->datahandler,1);*/
		}

		if(!isset($this->tpl))
			$this->tpl = $this->createTemplate();
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
			"src"=>$this->getSrc(),
			"type"=>$this->getType(),
            "alt"=>$this->getAlt()
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
		$this->setSrc($data->get('src'));
		$this->setAlt($data->get('alt'));
		$this->setType($data->get('type'));
		
		parent::setData($data);
    }
    //}}}
	
    // {{{ setAlt 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $alt    
    * @return   void
    */
    function setAlt($alt)
    {
		if(!isset($alt) || !is_scalar($alt))
			return;
		$this->alt = "".$alt;
		
    }
    // }}}
    
    // {{{ getAlt 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getAlt()
    {
		return $this->alt;
    }
    // }}}
}
//}}}

?>
