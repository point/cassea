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
WidgetLoader::load("WComponent");
//{{{ WBlock
class WHTML extends WComponent
{
	protected
		/**
		* @var string
		*/
		$src = null,
		/**
		* @var string
		*/
		$text = null
		;
	private $page_text;
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
			$this->setSrc((string)$elem['src']);
		else
			$this->setText(trim((string)$elem));
		$this->addToMemento(array("src","text"));
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
		if(Config::get("CACHE_STATIC_PAGES"))
		{
			$page = Controller::getInstance()->getPage();
			$cn = Controller::getInstance()->getControllerName();
			$storage = 	Storage::create("WHTML cache");
			if(!$storage->is_set($cn."_".$page."_".$this->getId()))
			{
				$this->page_text = 
					$this->getSrc()?file_get_contents($this->getSrc()):
						($this->getText()?$this->getText():null);
			}
			else
			{
				$p = $storage->get($cn."_".$page."_".$this->getId());
				$mtime = 0;
				$changed = 0;
				if($this->getSrc())
				{
                    if(pageChanged($this->getSrc(),$p['cache_time']) ||
                        Controller::getInstance()->XMLPageChanged($p['cache_time']))
                    {
                        $this->page_text = $this->getSrc()?file_get_contents($this->getSrc()):"";
				        $storage->set($cn."_".$page."_".$this->getId(),array("text"=>$this->page_text,"cache_time"=>time()));
                    }
                    else
                        $this->page_text = $p['text'];
				}
                else
                    $this->page_text = $this->getText();
            }
        }
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
				"content"=>$this->page_text
			));
		parent::assignVars();
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

		/*$storage = 	Storage::create("WHTML cache");
		$page = Controller::getInstance()->getPage();
		$cn = Controller::getInstance()->getControllerName();
		if($storage->is_set($cn."_".$page."_".$this->getId())
            && ($a = $storage->get($cn."_".$page."_".$this->getId())) && $a['text'] != "") return;
        */
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
        if($src{0} == "/")
            $src = substr($src,1);
		if(file_exists($src))
			$this->src = $src;
		/*elseif(file_exists(Config::get("ROOT_DIR").$src))
            $this->src = Config::get("ROOT_DIR").$src;*/
		elseif(file_exists(Config::get("HTML_DIR").'/'.$src))
            $this->src = Config::get("HTML_DIR").'/'.$src;
        elseif(file_exists(Config::get("HTML_DIR").'/'.Language::$current_language_name.'/'.$src))
            $this->src = Config::get("HTML_DIR").'/'.Language::$current_language_name.'/'.$src;
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
}
//}}}
?>
