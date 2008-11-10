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
//{{{ WRoll
class WRoll extends WContainer
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
		$odd_class = "roll_odd",
	    /**
        * @var		string
		*/
		$even_class = "roll_even",
	    /**
        * @var		int
		*/
		$count = 1

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

		$this->setData(DataRetriever::getData($this->getId()));


		if($this->getRuler())
			$controller->getDispatcher()->notify(
				new Event("ruler_settotal",$this->getId(),$this->getRuler(),array('total_count'=>$this->getCount()
				)));
		else
			Controller::getInstance()->getDisplayModeParams()
				->set($this->getId(), 0, $this->getCount(),$this->getCount());

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

    function handleEvent($event)
    {
		if($event->getName() == "roll_setlimits")
		{
			Controller::getInstance()->getDisplayModeParams()
				->set($this->getId(),$event->getParam('from'),$event->getParam('limit'),$this->getCount());
		}
    }
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
}
//}}}
?>
