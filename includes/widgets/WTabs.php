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
//{{{ WTabPane
class WTabs extends WContainer
{
    var

        /**
        * @var      WidgetCollection&
        */
        $tabs = null;
    
    // {{{ WTabPane 
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
		$this->tabs = new WidgetCollection($this->getId(),$elem);
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

		$controller = Controller::getInstance();
		$controller->addScript("ui.core.js");
		$controller->addScript("ui.tabs.js");
		$controller->addCSS("ui.tabs.css");

		$this->tabs->filter("WTab");
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
		$hrefs = array();
		$titles = array();
		$selected = 0;
		for($i = 0, $c = $this->tabs->count(); $i < $c; $i++)
		{
			if($this->tabs->getItem($i)->getHref())
				$hrefs[$i] = $this->tabs->getItem($i)->getHref();
			else $hrefs[$i] = "#".$this->tabs->getItem($i)->getId();
			$titles[$i] = Language::encodePair($this->tabs->getItem($i)->getTabTitle());
			if($this->tabs->getItem($i)->getSelected())
				$selected = $i;
		}
		$this->tpl->setParams(t(new TemplateParams())
			->set("href",$hrefs)
			->set("title",$titles)
			->set("tabs",$this->tabs->generateAllHTML())
			->set('selected',$selected));
		parent::assignVars();
    }
 	// }}}	
}
//}}}

?>
