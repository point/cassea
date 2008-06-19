<?php
//
// $Id:$
//
WidgetLoader::load("WObject");
//{{{ WValueChecker
class WValueChecker extends WObject
{
    protected

        /**
        * @var      array
        */
		$rules = array(),
        /**
        * @var      array
		*/
		$values = array(),
		 /**
        * @var      array
		*/
		$messages = array()
		;
	const def_message = "wrong value";
    
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
    * @param    array $params
    * @return void
    */
    function parseParams(SimpleXMLElement $elem)
	{
		$i = 0;
		foreach($elem as $c)
		{
			if($c->getName() != "check") continue;
			if(!isset($c['rule'])) continue;
			$this->rules[$i] = (string)$c['rule'];
			if(isset($c['value']))
				$this->values[$i] = (string)$c['value'];
			if(isset($c['message']))
				$this->messages[$i] = (string)$c['message'];
			else $this->messages[$i] = self::def_message;
			$i++;
		}
		$controller = Controller::getInstance();
		$controller->addScript("jquery.validate.js");
    }
    // }}}
    
    // {{{ getRules
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getRules()
    {
		if(empty($this->rules) || empty($this->widget_ids)) return "";

		$t1 = $t2 = "";
		$ta1 = $ta2 = array();
		foreach($this->getWidgetIds() as $w)
		{
			$t1 = $w.": {\n";
			foreach($this->rules as $i=>$r)
			{
				$t2 = $r.": ";
				if(isset($this->values[$i]))
					if($this->values[$i] == "1")
						$t2 .= 'true';
					elseif($this->values[$i] == "0")
						$t2 .= 'false';
					else
						$t2 .= "\"".$this->values[$i]."\"";
				else
					$t2 .= "true";
				$ta2[] = $t2;
			}
			$t1 .= implode(",\n",$ta2)."\n}";
			$ta1[] = $t1;
		}
		return implode(",\n",$ta1);
	}
    // }}}
    // {{{ getMessages
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getMessages()
    {
		if(empty($this->rules)|| empty($this->widget_ids)) return "";

		$t1 = $t2 = "";
		$ta1 = $ta2 = array();
		foreach($this->getWidgetIds() as $w)
		{
			$t1 = $w.": {\n";
			foreach($this->rules as $i=>$r)
				$ta2[] = $r.": \"".$this->messages[$i]."\"";
			$t1 .= implode(",\n",$ta2)."\n}";
			$ta1[] = $t1;
		}
		return implode(",\n",$ta1);
	}
    // }}}

    
    // {{{ addWidgetId
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $id
    * @return   void
    */
    function addWidgetId($id)
    {
		if(!isset($id))
		{
	   		$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,
				"Parameter name must have not null value"),LOG_LEVEL_CRITICAL);
			return;
		}
		$this->widget_ids[] = $id;
    }
    // }}}
    // {{{ getWidgetIds
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   array
    */
    function getWidgetIds()
    {
		return $this->widget_ids;
    }
    // }}}
}
//}}}

?>
