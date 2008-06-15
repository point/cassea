<?php
//
// $Id:$
//
WidgetLoader::load("WComponent");
//{{{ WControl
class WControl extends WComponent
{
    protected

        /**
        * @var      string
        */
        $name = "",
        /**
        * @var      string
        */
        $datahandler = null,
        /**
        * @var      int
        */
        $value = null,
        /**
        * @var      string
        */
        $alt = null,
        /**
        * @var      boolean
        */
        $readonly = 0,
        /**
        * @var      boolean
        */
        $disabled = 0,
        /**
        * @var      WValueChecker&
        */
        $valuechecker = null ,
        /**
        * @var      string
        */
		$filter_error_string = null,
        /**
        * @var      string
        */
		$additional_id = null,
        /**
        * @var      boolean
        */
		$name_w_braces = 0

		
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
		if(isset($id))
			$this->setName($id);
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
		$val = (string)$elem;
		if(!empty($val))
			$this->setValue($val);
		if(isset($elem['value']))
			$this->setValue((string)$elem['value']);

		if(!empty($elem['name']))
		{
	       	$this->setName((string) $elem['name'] );
			if(!isset($elem['id']))
				$this->setId((string)$elem['name']);
		}
		if(isset($elem['readonly']))
			$this->setReadOnly((string)$elem['readonly'] );
		if(isset($elem['disabled']))
	       	$this->setDisabled((string) $elem['disabled']);

		$this->addToMemento(array("value","name","readonly","disabled"));

		parent::parseParams($elem);		    	
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
		$name = "".$name;
		if(substr($name,-2,2) == "[]")
		{
			$this->name_w_braces = 1;
			$this->name = substr($name,0,-2);
		}
		else $this->name = $name;
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
    
    // {{{ setDataHandler 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $datahandler    
    * @return   void
    */
    function setDataHandler($datahandler)
    {
		//$datahandler == datahandler name
		if(empty($datahandler))
			return;	
		$this->datahandler = $datahandler;
	}
    // }}}
    
    // {{{ getDataHandler 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getDataHandler()
    {
		return $this->datahandler;
    }
    // }}}
    
    // {{{ setValue 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $value    
    * @return   void
    */
    function setValue($value)
    {
		if(!isset($value) || !is_scalar($value))
			return;
		$this->value = Filter::filter($value,Filter::STRING_QUOTE_ENCODE);
    }
    // }}}
    
    // {{{ getValue 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getValue()
    {
		return $this->value;
    }
    // }}}
    
    // {{{ setReadOnly 
    /**
    * Method description
    *
    * More detailed method description
    * @param    boolean $readonly    
    * @return   void
    */
    function setReadOnly($readonly)
    {
		if(!isset($readonly) || !is_scalar($readonly))
			return;
		$this->readonly = 0 + $readonly;
		
    }
    // }}}
    
    // {{{ getReadOnly 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   boolean
    */
    function getReadOnly()
    {
		return $this->readonly;
    }
    // }}}
    
    // {{{ setDisabled 
    /**
    * Method description
    *
    * More detailed method description
    * @param    boolean $disabled    
    * @return   void
    */
    function setDisabled($disabled)
    {
		if(!isset($disabled) || !is_scalar($disabled))
			return;
		$this->disabled = 0 + $disabled;
		
    }
    // }}}
    
    // {{{ getDisabled 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   boolean
    */
    function getDisabled()
    {
		return $this->disabled;
    }
    // }}}
    
    // {{{ setValueChecker 
    /**
    * Method description
    *
    * More detailed method description
    * @param    WValueChecker& $valuechecker    
    * @return   void
    */
    function setValueChecker($valuechecker)
    {
		if(!$valuechecker instanceof WValueChecker)
			return;	
		$this->valuechecker = $valuechecker;
	    }
    // }}}
    
    // {{{ getValueChecker 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   WValueChecker&
    */
    function getValueChecker()
    {
		return $this->valuechecker;
    }
    // }}}
    
    // {{{
    function preRender()
    {
		parent::preRender();

    	if(!empty($this->valuechecker))
    	{
			$this->valuechecker->addWidgetId($this->id);
   			$controller = &CController::getInstance();
			$controller->dispatcher->addEvent("valuechecker_puttosubmit");

			$event = new CEvent();
			$event->event_name = "valuechecker_puttosubmit";
			$event->notifywidget_id = $this->id;
			$vc_js = $this->valuechecker->generateJS();
			if(empty($vc_js)) return;
			$event->event_params['js_name'] = $vc_js['name'];
			$event->event_params['js_function'] = $vc_js['function'];
			$controller->dispatcher->notify($event);
		}
    }
    // }}}
    // {{{ setAdditionalID
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $value    
    * @return   void
    */
    function setAdditionalID($value)
    {
		if(!isset($value) || !is_scalar($value))
			return;
		$this->additional_id = "".$value;
		//$this->name = $this->name."_".$this->additional_id;
    }
    // }}}
    
    // {{{ getAdditionalID
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getAdditionalID()
    {
		return $this->additional_id;
    }
    // }}}
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
		if($this->getId() != $data->getFor()) return;
		$this->setName($data->get('name'));
		$this->setValue($data->getDef());
		$this->setValue($data->get('value'));
		$this->setReadonly($data->get('readonly'));
		$this->setAdditionalID($data->get('additional_id'));
		$this->setDisabled($data->get('disabled'));
		
		parent::setData($data);
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
			"name"=>(isset($this->additional_id))?
				($this->name.'['.$this->additional_id.']'.($this->name_w_braces?"[]":"")):
				($this->name.($this->name_w_braces?"[]":"")),
			"value"=>$this->value,
			//"alt"=>(isset($this->alt))?('alt="'.$this->alt.'"'):'',
			"readonly"=>($this->readonly)?('readonly="'.$this->readonly.'"'):'',
			"disabled"=>($this->disabled)?('disabled="'.$this->disabled.'"'):''
			));
		if(isset($this->filter_error_string))
			$tpl->assign_vars(array("error_string"=>
			"<font style=\"color:red\">*</font> ".$this->filter_error_string));

		parent::assignVars();
    }
	// }}}	
    // {{{ addBracesToName
    /**
    * Method description
    *
    * More detailed method description
    * @param    boolean $add
    * @return   void
    */
    function addBracesToName($add = 1)
    {
		$this->name_w_braces = 0+$add;
    }
    // }}}
    
    // {{{ getBracesToName
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getBracesToName()
    {
		return $this->name_w_braces;
    }
    // }}}

}
//}}}

?>
