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
// $Id:$
//
//{{{ WFlash
class WFlash extends WComponent
{
    protected

        /**
        * @var      string
        */
        $bgcolor = "#000000",
        /**
        * @var      string
        */
        $height = 100,
        /**
        * @var      string
        */
        $width = 100 ,
        /**
        * @var      string
        */
		$src = null
		;
    
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
    * @param    array
    * @return void
    */
    function parseParams(SimpleXMLElement $elem)
    {
		if(!empty($elem['height']))
	       	$this->setHeight((string)$elem['height']);
		if(!empty($elem['width']))
	       	$this->setWidth((string)$elem['width']);
		if(!empty($elem['src']))
			$this->setSrc((string)$elem['src']);       	
		if(!empty($elem['bgcolor']))
			$this->setBgColor((string)$elem['bgcolor']);

		$this->addToMemento(array("height","width","src", "bgcolor"));

		parent::parseParams($elem);		    	
    }
    // }}}
    // {{{ setBgColor
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $alt    
    * @return   void
    */
    function setBgColor($bgcolor)
    {
		if(!isset($bgcolor) || !is_scalar($bgcolor)) 
			return ;
		$this->bgcolor = "".$bgcolor;
    }
    // }}}
    
    // {{{ getBgColor
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getBgColor()
    {
		return $this->bgcolor;
    }
    // }}}
    
    // {{{ setHeight 
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $height    
    * @return   void
    */
    function setHeight($height)
    {
		if(!isset($height) || !is_scalar($height)) 
			return ;
		$this->height = 0 + $height;
    }
    // }}}
    
    // {{{ getHeight 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getHeight()
    {
		return $this->height;
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
		$this->width = 0 + $width;
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
			return ;
		if(strpos($src,"/") !== 0)
			$src = "/".$src;
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
		$this->setSrc($data->getDef());
		$this->setSrc($data->get('src'));
		$this->setWidth($data->get('width'));
		$this->setHeight($data->get('height'));
		$this->setBgColor($data->get('bgcolor'));

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

		$controller = Controller::getInstance();
		$controller->addScript("swfobject.js");
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
			"width"=>$this->getWidth(),
			"height"=>$this->getHeight(),
			"src"=>$this->getSrc(),
			'bgcolor'=>$this->getBgColor()
		));
		parent::assignVars();
    }
	// }}}	
}
//}}}

?>
