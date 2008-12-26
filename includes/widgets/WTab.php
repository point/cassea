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
WidgetLoader::load("WContainer");
//{{{ WTab
class WTab extends WContainer
{
    var

        /**
        * @var      string
        */
        $tab_title = null,
        /**
        * @var		MixedCollection&
        */
		$items = null,
        /**
        * @var      string
        */
		$href = null,
        /**
		* @var      bool
        */
		$selected = 0

        		
		;
    // {{{ WTabPanePage 
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
		if(isset($elem['tab_title'])) 
			$this->setTabTitle((string)$elem['tab_title']);
		if(isset($elem['href'])) 
			$this->setHref((string)$elem['href']);
		if(isset($elem['selected'])) 
			$this->setSelected((string)$elem['selected']);
		$this->items = new MixedCollection($this->getId(),$elem);
		$this->addToMemento(array("tab_title","href","selected"));
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
			"content" => $this->items->generateAllHTML()
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
        $this->setTabTitle($data->getDef());
		$this->setTabTitle($data->get('tab_title'));
		$this->setHref($data->get('href'));
		$this->setSelected($data->get('selected'));
		parent::setData($data);
    }
    //}}}

    // {{{ setTabTitle 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $title
    * @return   void
    */
    function setTabTitle($title)
    {
    	if(empty($title) || !is_scalar($title))
			return;
    	$this->tab_title = $title;
	}
    // }}}
    
    // {{{ getTabTitle 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getTabTitle()
    {
		return $this->tab_title;
    }
     // }}}

	// {{{ setHref
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $href
    * @return   void
    */
    function setHref($href)
    {
    	if(empty($href) || !is_scalar($href))
			return;
    	$this->href = $href;
	}
    // }}}
    
    // {{{ getHref
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getHref()
    {
		return $this->href;
    }
     // }}}
	
	// {{{ setSelected
    /**
    * Method description
    *
    * More detailed method description
    * @param    bool $selected
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
    * @return   bool
    */
    function getSelected()
    {
		return $this->selected;
    }
     // }}}
}
//}}}

?>
