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
		$messages = array(),

		$widget_ids = array(),

		$widget_fnames = array(),

		$filter = null
		;
	/*const def_message = "wrong value";*/
    
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
			if($c->getName() == "check")
			{
				if(!isset($c['rule'])) continue;
				$this->rules[$i] = (string)$c['rule'];
				if(isset($c['value']))
					$this->values[$i] = (string)$c['value'];
				if(isset($c['message']))
					$this->messages[$i] = (string)$c['message'];
				else $this->messages[$i] = null;
				//else $this->messages[$i] = self::def_message;
				$i++;
			}
            elseif($c->getName() == 'filter')
            {
                if(Filter::getFilter((string)$c) == Filter::NONE)
                    throw new FilterException('Filter '.(string)$c.' doesn\'t exists');
                $this->filter = (string)$c;
            }

		}
		$controller = Controller::getInstance();
		$controller->addScript("jquery.validate.js");
		$controller->addScript("jquery.validate.messages_".Language::currentName().".js");
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
    function getRules($form_id)
    {
        if(/*empty($this->rules) ||*/ empty($this->widget_ids)) return "";

		$t1 = $t2 = "";
		$ta1 = $ta2 = array();
		foreach($this->getWidgetFullNames() as $i => $w_name)
		{
			$w = Controller::getInstance()->getWidget($this->widget_ids[$i])->getName();
			Controller::getInstance()->setChecker($form_id,$w,'filter',$this->filter);
			$t1 = "'".$w_name."'".": {\n";
			foreach($this->rules as $i=>$r)
			{
				$t2 = $r.": ";
				if(isset($this->values[$i]))
				{
	                if(is_string($this->values[$i]))
						if(strpos($this->values[$i],"\"") !== false || strpos($this->values[$i],"[") !== false)
							$t2 .= $this->values[$i];
						else $t2 .= "\"".$this->values[$i]."\"";
				}
				else
					$t2 .= "true";
                Controller::getInstance()->setChecker($form_id,$w,$r,isset($this->values[$i])?$this->values[$i]:"true", !is_null($this->messages[$i])?Language::encodePair($this->messages[$i]):null);
				$ta2[] = $t2;
			}
			$t1 .= implode(",\n",$ta2)."\n}";
			if(empty($ta2)) continue;
			unset($ta2);
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
		foreach($this->getWidgetFullNames() as $w)
		{
			$t1 = "'".$w."': {\n";
			foreach($this->rules as $i=>$r)
				if($this->messages[$i] !== null)
					$ta2[] = $r.": \"".Language::encodePair($this->messages[$i])."\"";
			$t1 .= implode(",\n",$ta2)."\n}";
			if(empty($ta2)) continue;
			unset($ta2);
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
		if(!isset($id) || !Controller::getInstance()->getWidget($id) instanceof WControl)
			return;
		$this->widget_ids[] = $id;
		$this->widget_fnames[] = Controller::getInstance()->getWidget($id)->getFullName();
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
    function getWidgetFullNames()
    {
		return $this->widget_fnames;
    }
    // }}}
}
//}}}

?>
