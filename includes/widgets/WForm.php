<?php
//
// $Id:$
//
WidgetLoader::load("WContainer");
//{{{ WForm
class WForm extends WContainer
{
    var

        /**
        * @var      string
        */
        $action = null,
        /**
        * @var      string
        */
        $enctype = "multipart/form-data",
        /**
        * @var      array
        */
		$method = "post",
        /**
        * @var      WidgetCollection&
        */
        $items = null,
        /**
        * @var     string
        */
		$inner_valuecheckers = array(),
		$vc_rules = null,
		$vc_messages = null
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
		if (isset($elem['action']))
		   $this->setAction((string)$elem['action']);
        if (isset($elem['enctype'])) 
			$this->setEnctype((string)$elem['enctype']);

		if(isset($elem['method']))
			$this->setMethod((string)$elem['method']);

		$this->items = new WidgetCollection($elem);

		$this->addToMemento(array("action","enctype","method"));

		parent::parseParams($elem);		    	
    }
    // }}}
	
    // {{{ setAction 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $action    
    * @return   void
    */
    function setAction($action)
    {
		if(!isset($action) || !is_scalar($action))
			return;
		$this->action = (string)$action;
    }
    // }}}
    
    // {{{ getAction 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getAction()
    {
		return $this->action;
    }
    // }}}
    
    // {{{ setEnctype 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $enctype    
    * @return   void
    */
    function setEnctype($enctype)
    {
		if(!isset($enctype) || !is_scalar($enctype))
			return;
		$this->enctype = (string)$enctype;

    }
    // }}}
    
    // {{{ getEnctype 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getEnctype()
 	{
		return $this->enctype;
    }
    // }}}
    
    // {{{ setMethod
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $method
    * @return   void
    */
    function setMethod($method)
    {
		if(empty($method) || ($method != 'post' && $method != 'get'))
			return;
		$this->method = (string)$method;
    }
    // }}}
    
    // {{{ getMethod
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getMethod()
    {
		return $this->method;
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

		$controller = Controller::getInstance()->getDispatcher()->addEvent("have_valuechecker");	

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
		$controller->getDispatcher()->
			addSubscriber("have_valuechecker",$this->getId());

		if(isset($this->dataset))
			$this->setData($this->dataset->getData($this->getId()));

		parent::preRender();

		if(!empty($this->inner_valuecheckers))
			foreach($this->inner_valuecheckers as $k=>$v)
				if(($v = $controller->getValueChecker($k)) !== null)
				{
					$this->vc_rules .= $v->getRules();
					$this->vc_messages .= $v->getMessages();
				}
/*		$controller->dispatcher->deleteSubscriber("valuechecker_puttosubmit",$this->id);


		$event = new CEvent();
		$event->event_name = "valuechecker_getfunc";
		$event->notifywidget_id = $this->id;
		$controller->dispatcher->notify($event);

		for($i = 0, $c = count($this->plain_on_submit); $i < $c; $i++)
			$this->javascript->addPlain("onsubmit",$this->plain_on_submit[$i]);
		for($i = 0, $c = count($this->conditional_on_submit); $i < $c; $i++)
			$this->javascript->addConditional("onsubmit",$this->conditional_on_submit[$i]);

		$this->javascript->addBeforeWidget($this->valuechecker_function);
 */
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
			"action"=>$this->getAction(),
			"enctype"=>$this->getEnctype(),
			"method"=>$this->getMethod(),
			"form_content" =>$this->items->generateAllHTML(),
			"vc_rules"=>$this->vc_rules,
			"vc_messages"=>$this->vc_messages
		));
		parent::assignVars();
    }
	// }}}	
    function handleEvent($event)
	{
		if($event->event_name == "have_valuechecker" && !empty($event->event_params['id']))
			$this->inner_valuecheckers[$event->event_params['id']] = 1;
    }
	// {{{ 
	function postRender()
	{
		$controller = Controller::getInstance();
		//$controller->dispatcher->deleteSubscriber("valuechecker_puttosubmit",$this->id);
		
		//$controller->getDispatcher->deleteEvent("valuechecker_getfunc");
		parent::postRender();
	}
	//}}}	
    // {{{ setData
    /**
    * Method description
    *
    * More detailed method description
    * @param    array $data  
    * @return   void
    */
    function setData(ResultSet $data)
	{
		if($data->getFor() != $this->getId()) return;

		$this->setAction($data->get('action'));
		$this->setEnctype($data->get('enctype'));
		$this->setMethod($data->get('method'));
		
    	parent::setData($data);
    }
    // }}}
}
//}}}

?>
