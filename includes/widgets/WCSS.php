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
// $Id: WHTML.php 56 2008-11-10 00:20:48Z point $
//
WidgetLoader::load("WComponent");
//{{{ WCSS
class WCSS extends WComponent
{
	protected
		/**
		* @var string
		*/
		$src = null,
		/**
		* @var string
		*/
        $condition = null,
		/**
		* @var string
		*/
        $media = null,
		/**
		* @var string
		*/
		$text = null

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
		if(isset($elem['src']))
        {
			$this->setSrc((string)$elem['src']);
		    if(isset($elem['condition']))
			    $this->setCond((string)$elem['condition']);
        }
        elseif((string)$elem)
			$this->setText(trim((string)$elem));
        if(isset($elem['media']))
            $this->setMedia((string)$elem['media']);
		$this->addToMemento(array("src","condition","media"));
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
        if(isset($this->src))
            Controller::getInstance()->addCSS($this->getSrc(),$this->getCond(),$this->getMedia());

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
        if(!isset($this->src))
		    $this->tpl->setParamsArray(array(
                "content"=>$this->getText(),
                "media"=>$this->getMedia()
			    ));
		parent::assignVars();
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

		if(!isset($this->tpl) && !isset($this->src))
			$this->tpl = $this->createTemplate();
		parent::preRender();

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
        $this->setText($data->getDef());
		parent::setData($data);
    }
    //}}}
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
        if(substr($src,-4) != ".css")
            $src .= ".css";
        $this->src = (string)$src;
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
    
    // {{{ setMedia
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $media
    * @return   void
    */
    function setMedia($media)
    {
		if(!isset($media) || !is_scalar($media)) 
            return ;
        $this->media = (string)$media;
    }
    // }}}
    
    // {{{ getMedia
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getMedia()
    {
		return $this->media;
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
	
}
//}}}
?>
