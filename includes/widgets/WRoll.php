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
//{{{ WRoll
class WRoll extends WContainer implements iOddEven
{
    protected
        /**
        * @var		string
        */
        $ruler_id = null,
        /**
        * @var      string
		*/
		$sort_mode = "asc",
        /**
        * @var      string
		*/
		$sort_by = null,
	    /**
        * @var      WidgetCollection
        */
        $items = null,
	    /**
        * @var		string
		*/
		$odd_class = null,
	    /**
        * @var		string
		*/
		$even_class = null,
	    /**
        * @var		int
		*/
		$count = 0,
	    /**
        * @var		string
		*/
		$if_empty = null

		;
    // {{{ WRoll 
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
		if(isset($elem['ruler']))
			$this->setRuler((string)$elem['ruler']);
        if(isset($elem['count']))
            $this->setCount((string)$elem['count']);
		if(isset($elem['if_empty']))
			$this->setIfEmpty((string)$elem['if_empty']);
		if(isset($elem['even_class']))
			$this->setEvenClass((string)$elem['even_class']);
		if(isset($elem['odd_class']))
			$this->setOddClass((string)$elem['odd_class']);

		$this->items = new IterableCollection($this->getId(),$elem);
		$this->addToMemento(array("count"));


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

		Controller::getInstance()->getDispatcher()->addEvent("ruler_settotal");
		Controller::getInstance()->getDispatcher()->addSubscriber("roll_setlimits",$this->getId());
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
		$controller = Controller::getInstance();

        $this->checkAndSetData();


		if($this->getRuler())
			$controller->getDispatcher()->notify(
				new WidgetEvent("ruler_settotal",$this->getId(),$this->getRuler(),array('total_count'=>$this->getCount()
				)));
		else
			Controller::getInstance()->getDisplayModeParams()
				->set($this->getId(), 0, $this->getCount(),$this->getCount());


		parent::preRender();
    }
	// }}}    
	
	// {{{ messageInterchange
	/**
    * method description
    *
    * more detailed method description
    * @param    void
    * @return   void
    */
	function messageInterchange()
	{
		if(($w = Controller::getInstance()->getWidget($this->getIfEmpty())) && $w instanceof WComponent)
			if(!$this->getCount())
			{
				$w->setEnabled(1);
				$w->setVisible(1);
			}
			else
				$w->setVisible(0);

		parent::messageInterchange();
	}
	//}}}	
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
		$this->tpl->setParams(t(new TemplateParams())
			->set("content",$this->items->generateAllHTML()));
		parent::assignVars();
    }
	// }}}	
    // {{{ setRuler 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $ruler_id
    * @return   void
    */
    function setRuler($ruler_id)
	{
		if(!isset($ruler_id) || !is_scalar($ruler_id))
			return;
		$this->ruler_id = $ruler_id;
    }
    // }}}
    
    // {{{ getRuler 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getRuler()
    {
		return $this->ruler_id;
    }
    // }}}

    // {{{ SetSorterState 
    /**
    * Method description
    *
    * More detailed method description
    * @param    boolean $state    
    * @return   void
    */
    function setSorterState($state)
    {
		if(!isset($state) || !is_scalar($state))
			return;
		$this->sorter =  0 + $state;
    }
    // }}}
	
    // {{{ getSorterState 
    /**
    * Method description
    *
    * More detailed method description
    * @param    null
    * @return   boolean
    */
    function getSorterState()
    {
		return $this->sorter;
    }
    // }}}

	// {{{ handleEvent 
    /**
    * Method description
    *
    * More detailed method description
    * @param    WidgetEvent
    * @return   void
    */

    function handleEvent(WidgetEvent $event)
    {
		if($event->getName() == "roll_setlimits")
		{
			Controller::getInstance()->getDisplayModeParams()
				->set($this->getId(),$event->getParam('from'),$event->getParam('limit'),$this->getCount());
		}
		parent::handleEvent($event);
    }
	//}}}
	
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
        $this->setCount($data->getDef());
		$this->setCount($data->get('count'));
		parent::setData($data);
    }
    //}}}

    // {{{ setCount
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $count
    * @return   void
    */
    function setCount($count)
	{
		if(!isset($count) || !is_numeric($count) || $count < 0) return;
		$this->count = 0+$count;
    }
	// }}}
	
    // {{{ getCount
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getCount()
    {
		return $this->count;
    }
    // }}}
	
    // {{{ setIfEmpty
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $if_empty
    * @return   void
    */
    function setIfEmpty($if_empty)
	{
		if(!isset($if_empty) || !is_string($if_empty)) return;
		$this->if_empty = "".$if_empty;
    }
	// }}}
	
    // {{{ getifEmpty
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getIfEmpty()
    {
		return $this->if_empty;
    }
    // }}}
	
	// {{{ setOddclass 
    /**
    * Method description
    *
    * More detailed method description
    * @param    oddclass 
    * @return   void
    */
    function setOddClass($class)
	{
		if(!isset($class) || !is_string($class)) return;
		$this->odd_class=$class;
    }
    // }}}
	
	// {{{ setEvenclass 
    /**
    * Method description
    *
    * More detailed method description
    * @param    evenclass 
    * @return   void
    */
    function setEvenClass($class)
    {
		if(!isset($class) || !is_string($class)) return;
		$this->even_class=$class;
    }
	// }}}
	
	// {{{ getEvenClass 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void 
    * @return   string
    */
    function getEvenClass()
    {
		return $this->even_class;
    }
    // }}}
	
	// {{{ getOddClass 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void 
    * @return   string
    */
    function getOddClass()
    {
		return $this->odd_class;
    }
    // }}}
}
//}}}
?>
