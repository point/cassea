<?php
//
// $Id:$
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
		$even_class = "roll_even"
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

		$this->items = new IterableCollection($elem);

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

		$controller->getDisplayModeParams()->gatherStat(1);
		if(isset($this->dataset))
			$this->setData($this->dataset->getData($this->getId()));

		parent::preRender();
		$total = $controller->getDisplayModeParams()->getStat('iterative_count');
		$controller->getDisplayModeParams()->gatherStat(0);

		$controller->getDispatcher()->notify(
			new Event("ruler_settotal",$this->getId(),$this->getRuler(),array('total_count'=>$total
			)));


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
				->setIterativeLimits($event->getParam('from'),$event->getParam('limit'));
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
    function setData(ResultSet $data)
	{
		if($data->getFor() != $this->getId()) return;
		$this->restoreMemento();

		parent::setData($data);
    }
    //}}}

}
//}}}
?>
