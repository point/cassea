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
// $Id: WForm.php 104 2009-05-29 15:39:01Z point $
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
		$no_check = 0,
		$inner_valuecheckers = array(),
		$vc_rules = array(),
		$vc_messages = array(),
		$form_signature = null
		;
    const signature_name = "__sig";
	const no_check_attribute = "no_check";
    //const formid_name = "__formname";
    
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
		$this->form_signature = $this->getId().":".md5(mt_rand()*time());
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

		if(isset($elem['no_check']))
			if($this->id[0] == "_")
				trigger_error("In order to use no_check set proper id for the form");
			else
				$this->no_check = 0+$elem['no_check'];

		$this->items = new WidgetCollection($this->getId(),$elem);

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

		Controller::getInstance()->getDispatcher()->addEvent("have_valuechecker");	
		Controller::getInstance()->getDispatcher()->addEvent("echo_formid");	
		Controller::getInstance()->getDispatcher()->addEvent("echo_form_signature");	


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

		$event = new WidgetEvent("echo_formid",$this->getId());
        $event->setParams(array("formid"=>$this->getId()));
        $controller->getDispatcher()->notify($event);

		$event = new WidgetEvent("echo_form_signature",$this->getId());
        $event->setParams(array("signature"=>$this->getSignature()));
        $controller->getDispatcher()->notify($event);

        $this->checkAndSetData();

		parent::preRender();

		if(!empty($this->inner_valuecheckers))
			foreach($this->inner_valuecheckers as $k=>$v)
				if(($v = $controller->getValueChecker($k)) !== null)
				{
                    $r = $v->getRules($this->getId());
                    if(!empty($r))
					    //$this->vc_rules .= $r.", ";
					    $this->vc_rules[] = $r;
                    $m = $v->getMessages();
                    if(!empty($m))
					//$this->vc_messages .= $v->getMessages();
					    $this->vc_messages[] = $v->getMessages();
				}

		if(strpos($this->getAction(),"http://") === false)
			Controller::getInstance()->addSignature($this->getSignature());
	    Controller::getInstance()->getDispatcher()->deleteSubscriber("echo_formid");
	    Controller::getInstance()->getDispatcher()->deleteSubscriber("echo_form_signature");
	    Controller::getInstance()->getDispatcher()->deleteEvent("echo_formid");
	    Controller::getInstance()->getDispatcher()->deleteEvent("echo_form_signature");
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

    // {{{ getSignature
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
	function getSignature()
	{
		return $this->form_signature;
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
			"vc_rules"=>!empty($this->vc_rules)?implode(", ",$this->vc_rules):null,
            "vc_messages"=>!empty($this->vc_messages)?Language::encodePair(implode(", ",$this->vc_messages)):null,
			"signature"=>$this->getMethod() != "post"?null:$this->form_signature,
			"signature_name"=>$this->getMethod() != "post"?null:self::signature_name,
            //"formid_name" =>$this->getMethod() != "post"?null: self::formid_name,
			"widget_id"=>$this->getId()
		));
		
		parent::assignVars();
    }
	// }}}	
    function handleEvent(WidgetEvent $event)
	{
		if($event->getName() == "have_valuechecker" && ($id = $event->getParam('id')))
            $this->inner_valuecheckers[$id] = 1;
		parent::handleEvent($event);
    }
	// {{{ 
	function postRender()
	{
	    Controller::getInstance()->getDispatcher()->deleteSubscriber("have_valuechecker",$this->getId());
	    Controller::getInstance()->getDispatcher()->deleteEvent("have_valuechecker");
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
    function setData(WidgetResultSet $data)
	{
		$this->setAction($data->get('action'));
		$this->setEnctype($data->get('enctype'));
		$this->setMethod($data->get('method'));
		
    	parent::setData($data);
    }
    // }}}
}
//}}}

?>
