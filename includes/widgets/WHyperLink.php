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
WidgetLoader::load("WContainer");
//{{{ WHyperLink
class WHyperLink extends WContainer implements StringProcessable
{
    
	protected
        /**
        * @var      string
        */
        $href = null,
        /**
        * @var      string
        */
        $rel = null,
        /**
        * @var      string
        */
        $rev = null,
        /**
        * @var      WidgetCollection
        */
        $items = null,
        /**
        * @var      string
        */
		$text = null,
        /**
        * @var      string
        */
        $target = null,
        /**
        * @var      string
        */
        $name = null

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
    	if(isset($elem['href']))
			$this->setHREF((string)$elem['href']);
		if(isset($elem['baseurl']))
			$this->setBaseURL((string)$elem['baseurl']);

		if(isset($elem['rel']))
			$this->setAttribute('rel',(string)$elem['rel']);
		if(isset($elem['rev']))
			$this->setAttribute('rev',(string)$elem['rev']);
		if(isset($elem['target']))
            $this->setAttribute('target',(string)$elem['target']);
        if(isset($elem['text']))
            $this->setText((string)$elem['text']);
        if(isset($elem['name']))
            $this->setName((string)$elem['name']);

		$this->items = new MixedCollection($this->getId(),$elem);
        $this->addToMemento(array("href","baseurl","label","rel","rev","target","name"));

		parent::parseParams($elem);		    	
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
		$this->setHREF($data->getDef());
		$this->setAttribute('rel',$data->get('rel'));
		$this->setAttribute('rev',$data->get('rev'));
		$this->setAttribute('target',$data->get('target'));
        if(($text = $data->get('text')) !== null && !$this->items->count())
            $this->items->setText($text);


		$href = $data->get('href');
		if(isset($href))
			if(is_array($href))
			{
				$page = null;
				if(isset($href['page']))
					{$page = $href['page'];unset($href['page']);	}
				$this->setHREF(Controller::getInstance()->makeURL($page,$href));
			}
			else
				$this->setHREF($href);

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
        if(isset($this->name) && !isset($this->href))
            $this->setHREF("");
        if(!isset($this->name) && !isset($this->href))
            $this->setHREF("#");
        if($this->items->isEmpty())
            if(isset($this->text))
                $this->items->setText($this->getText());
            else
                $this->items->setText($this->getHREF());

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
			'href'=> $this->getHREF(), 
            'name' => (!empty($this->name))?(" name=\"".$this->getName()."\" "):'',
			"rev"=> (!empty($this->rev))?(' rev="'.$this->rev.'" '):'',
			"rel" => (!empty($this->rel))?(' rel="'.$this->rel.'" '):'',
			"target"=>(!empty($this->target))?(' target="'.$this->target.'" '):'',
			"text"=>Language::encodePair($this->items->generateAllHTML())
		));
		parent::assignVars();
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
    
    // {{{ setName 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $name    
    * @return   void
    */
    function setName($name = null)
    {
		if(!isset($name) || !is_scalar($name)) return ;
		$this->name = (string)$name;
    }
    // }}}
    
    // {{{ getName 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getName()
    {
		return $this->name;
    }
    // }}}
}
//}}}

?>
