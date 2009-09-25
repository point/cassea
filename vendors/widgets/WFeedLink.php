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
// $Id:$
//
WidgetLoader::load("WComponent");
Autoload::addVendor("feed");
//{{{ WFeedLink
class WFeedLink extends WComponent
{
    
	protected 
		$title = null,
		$href = null,
		$type = Feed::ATOM
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
    	if(isset($elem['title']))
			$this->setTitle((string)$elem['title']);

    	if(isset($elem['href']))
			$this->setHREF((string)$elem['href']);

    	if(isset($elem['type']))
			$this->setType((string)$elem['type']);


		parent::parseParams($elem);		    	
    }
    // }}}
	
    // {{{ setTitle 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $title
    * @return   void
    */
    function setTitle($title)
    {
		if(!isset($title) || !is_scalar($title))
			return;
		if(strpos($title, '"') !== false)
			$title = str_replace('"',"'",$title);
		$this->title = "".$title;
    }
    // }}}
    
    // {{{ getTitle
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getTitle()
    {
		return $this->title;
    }
    // }}}
	
    // {{{ setHREF
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $href
    * @return   void
    */
    function setHREF($href)
    {
		if(!isset($href) || !is_scalar($href))
			return;
		$this->href = str_replace('&amp;',"&",
			str_replace('javascript:','',(string)$href));
    }
    // }}}
    
    // {{{ getHREF
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getHREF()
    {
		return $this->href;
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
		if(!isset($type) || !in_array($type,array(Feed::ATOM,Feed::RSS)))
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
		$this->setTitle($this->getTitle()." - ".$this->getType());
		Header::get()->addLink(array('rel'=>'alternate', 'type'=>Feed::getMimeType($this->getType()),
			'title'=>$this->getTitle(), 'href'=>$this->getHREF()));
		parent::buildComplete();
	}    
	// }}}
}
//}}}

?>
