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

/**
 * This file contains implementation of almost the basic class in
 * the whole widgets hierarchy. The successors of this class
 * should be displayed to the browser.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */

//{{{ WComponent
/**
 * This class defines basic behavior, basic methods for all
 * widgets.
 */
abstract class WComponent extends WObject
{
	protected
		/**
		 * @var string holds CSS classes for the widget
		 */
		$style_class = null,
		/**
		 * @var boolean defines whether widget is in "enabled" or "disabled" state
		 */
		$state = true,
		/**
		 * @var string holds the name of the file with template to render to show the widget
		 */
		$template_name = "default",
		/**
		 * @var CTemplate& object which renders the HTML responce
		 */
		$tpl = null,
		/**
		 * @var string HTML title property
		 */
		$title = null,
		/**
		 * @var boolean defines visibility of the widget
		 */
		$visible = true,
		/**
		 * @var      string
		 */
		$html_id = "",
		/**
		 * @var array holds all the class variables (including protected)
		 */
		$class_vars = array(),
		/**
		 * @var string parameters for {@link StringProcessor}
		 */
		$string_process = null
		;

	/**
	 * @var int if no id is given, this incremented value will be used in id creation
	 */
	private static $w_counter = 0;
	private
		/**
		 * @var boolean equals true if particular widget is inside the roll
		 */
		$inside_roll = false,
		/**
		 * @var boolean defines whether the add_html_id should be incremented. Often used in inter-widget 
		 * communication
		 */
		$do_increment = false,
		/**
		 * @var integer when widget is inside the roll the html id should be incremented
		 */
		$add_html_id=0,
		/**
		 * @var array holds vars values to recover later
		 */
		$memento = array(),
		/**
		 * @var array list of the var names to store in memnto
		 */
		$memento_vars = array(),
		/**
		 * @var string id of the widget, lowercased. Optimization for WidgetSelector
		 */
		$id_lower = null,
		/**
		 * @var string id of the widget, lowercased. Optimization for WidgetSelector
		 */
		$class_lower = null,
		/**
		 * @var string shows whether setData method was already called
		 */
		$data_set = false
		;

	//{{{ setID 
	/**
	 * Redefines parent's setId method to handle empty id attribute
	 * and to cache lowercased versions of id and class name.
	 *
	 * @param    string id of the widget
	 * @return   void
	 */
	function setID($id = null)
	{
		if(!isset($id) || !is_scalar($id))
			$id = "__w".(self::$w_counter++);
		parent::setId($id);
		$this->id_lower = strtolower($this->getId());
		$this->class_lower = strtolower(get_class($this));
	}
	//}}}
    
	//{{{ getIDLower
	/**
	 * Returns cached lowercased version of the id of the widget
	 *
	 * @param    void
	 * @return   string
	 */
	function getIDLower()
	{
		return $this->id_lower;
	}
	//}}}

	//{{{ getClassLower
	/**
	 * Returns cached lowercased class name
	 *
	 * @param    void
	 * @return   string
	 */
	function getClassLower()
	{
		return $this->class_lower;
	}
	//}}}
	
	//{{{ setEnabled 
	/**
	 * Alias of setState.
	 *
	 * @param    boolean $state
	 * @return   void
	 */
	function setEnabled($state)
	{
		$this->setState($state);
	}
	//}}}

	//{{{ getEnabled 
	/**
	 * Alias of getState.
	 *
	 * @param    void
	 * @return   bool
	 */
	function getEnabled()
	{
		return $this->state;
	}
	//}}}

	//{{{ generateHTML 
	/**
	 * This function triggers assignment of the variables to template and 
	 * rendering it. The output is gathering by the Controller to glue
	 * into the response.
	 * If the widget is not visible or disabled, the empty string will be returned.
	 *
	 * @param    void
	 * @return   string
	 */
	function generateHTML()
	{
		if(!$this->getState()) return "";
		$this->assignVars();
		if($this->getVisible() && isset($this->tpl)) return $this->tpl->getHTML();
		else return "";

	}
	//}}}
    
    // {{{ setTemplate 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $template_name    
    * @return   void
    */
    function setTemplate($template_name = null)
	{
		if(!isset($template_name)) return;
		$this->template_name = $template_name;
    }
    // }}}
    
    // {{{ getStyle 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   WStyle
    */
    function getStyle()
    {
		return $this->style;
    }
    // }}}
	
    // {{{ getStyleClass
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getStyleClass()
    {
		return $this->style_class;
    }
    // }}}
    
    // {{{ setStyle 
    /**
    * Method description
    *
    * More detailed method description
    * @param    WStyle& $style    
    * @return   void
    */
    function setStyle($style)
    {
		if(!isset($style) || !$style instanceof WStyle) 
			return;
		$this->style = $style;
    }
    // }}}
	
    // {{{ setStyleClass
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $style_class
    * @return   void
    */
    function setStyleClass($style_class = null)
    {
		if(!isset($style_class)) 
			return;
		$this->style_class .= " ".$style_class;
    }
    // }}}
    
    // {{{ setVisible 
    /**
    * Method description
    *
    * More detailed method description
    * @param    boolean $visible    
    * @return   void
    */
    function setVisible($visible)
    {
		if(!isset($visible)) 
			return;

		$this->visible = ($visible)?1:0;
    }
    // }}}
    
    // {{{ getVisible 
    /**
    * Method description
    *
    * More detailed method description
    * @param    null
    * @return   boolean
    */
	function getVisible()
	{
		return $this->visible;
    }
    // }}}

    // {{{ setState
    /**
    * Method description
    * assign vars and generate html
    * More detailed method description
    * @param    boolean $state
    * @return   void
    */
    function setState($state)
    {
		if(!isset($state)) 
			return;
		$this->state = ($state)?1:0;
    }
    // }}}

    // {{{ getState
    /**
    * Method description
    *
    * More detailed method description
    * @param    null
    * @return   boolean
    */
    function getState()
    {
    	return $this->state;
    }
    // }}}
	
    // {{{ setTitle 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $title    
    * @return   void
    */
    function setTitle($title)
    {
		if(!isset($title) || !is_scalar($title)) 
			return;

		$this->title = "".$title;
    }
    // }}}
    
    // {{{ getTitle 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getTitle()
    {
		return $this->title;
    }
    // }}}
    
    // {{{ setJavaScript 
    /**
    * Method description
    *
    * More detailed method description
    * @param    JavaScript& $javascript    
    * @return   void
    */
    function setJavaScript(WJavaScript $javascript)
	{
		if(!isset($javascript) || !$javascript instanceof WJavaScript) 
			return;
		$this->javascript = $javascript;		
    }
    // }}}
    
    // {{{ getJavaScript 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   WJavaScript&
    */
    function getJavaScript()
    {
		return $this->javascript;
    }
    // }}}
    
    // {{{ setAttribute 
    /**
    * method description
    *
    * more detailed method description
    * @param    string $attribute    
    * @param    mixed $value    
    * @return   void
    */
    function setAttribute($attribute, $value)
    {
		if(!isset($attribute) || !isset($value)) 
			return false;

		$vars = get_object_vars($this);
		$set = 0;
		foreach($vars as $k=>$v)
		{
			if($attribute == $k)
			{
				$this->$k = $value;
				$set = 1;
				break;
			}
		}
		return $set;
	}
    // }}}
    
    // {{{ getAttribute 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $attribute    
    * @return   mixed
    */
    function getAttribute($attribute)
    {
		if(!isset($attribute)) 
			return null;

		$vars = get_object_vars($this);
		foreach($vars as $k=>$v)
			if($attribute == $k)
				return $v;

		return null;
    }
    // }}}
    
    // {{{ getDataSet
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   bool
    */
    function getDataSet()
    {
		return $this->data_set;
    }
    // }}}
    
    // {{{ setDataSet
    /**
    * Method description
    *
    * More detailed method description
    * @param    bool $set
    * @return   void
    */
    function setDataSet($set)
    {
        if(!isset($set)) return;
        $this->data_set = (bool)$set;
    }
    // }}}

    // {{{ checkAndSetData
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   void
    */
    function checkAndSetData()
    {
        if(!$this->getDataSet() && !$this instanceof iNotSelectable)
        {
		    $this->restoreMemento();
		    DataRetriever::manageData($this->getId());
        }
    }
    // }}}

    // {{{ getDataSetterMethod
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   void
    */
	function getDataSetterMethod()
	{
		return "setData";
	}
	//}}}

    // {{{ getDataSet 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   WDataSet
     */
    // DEPRECATED
    /*function getDataSet()
    {
		return $this->dataset;
    }*/
    // }}}
    
    // {{{ parseParams 
    /**
    * Method description
    *
    * More detailed method description
    * @param    array $params
    * @return   void
    */
    function parseParams(SimpleXMLElement $params)
	{
        if(isset($params['enabled'])) $this->setState(0+$params['enabled']);
        $a = $d = null;
        if(isset($params['allow']))
            $a = (string)$params['allow'];
        if(isset($params['deny']))
            $d = (string)$params['deny'];
        if(!ACL::check($a,$d))
            $this->setState(0);
		if(isset($params['title'])) $this->setTitle((string)$params['title']);
		if(isset($params['visible'])) $this->setVisible(0+$params['visible']);
		if(isset($params['html_id'])) $this->setHTMLId((string)$params['html_id']);
		else $this->setHTMLId($this->getId());

        if(isset($params['class'])) 
            $this->setStyleClass((string)$params['class']);
        $this->setTemplate(isset($params['template'])?(string)$params['template']:null);
		if(isset($params['tooltip']))
			$this->setTooltip((string)$params['tooltip']);

		if(isset($params['hide_if_empty']))
			$this->setHideIfEmpty((string)$params['hide_if_empty']);

		if(isset($params['hide_if_hidden']))
			$this->setHideIfHidden((string)$params['hide_if_hidden']);

        if(isset($params['process']))
            $this->setStringProcess((string)$params['process']);

		$controller = Controller::getInstance();
		$controller->getDispatcher()->addEvent("increment_id");	
		$controller->getDispatcher()->addEvent("all_build_complete");	
		//$controller->getDispatcher()->addSubscriber("roll_inside", $this->getId());
		$controller->getDispatcher()->addSubscriber("all_build_complete", $this->getId());;
		//$controller->getDispatcher()->addSubscriber("increment_id", $this->getId());
		
        $this->addToMemento(array("enabled","title","visible","html_id","style_class","tooltip","javascript",
			"javascript_before","javascript_after","hide_if_hidden","hide_if_empty"/*,"tpl"*/));

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
    function setData(WidgetResultSet $data)
	{
		$controller = Controller::getInstance();
		if(isset($data->style))
			$this->setStyle($controller->getStyleByName($data->get('style')));

		if(isset($data->javascript))
			$this->setJavaScript($controller->getJavaScriptByName($data->get('javascript')));

		if(isset($data->visible))
			$this->setVisible($data->get('visible'));

		if(isset($data->enabled))
			$this->setState($data->get('enabled'));

		if(isset($data->title))
			$this->setTitle($data->get('title'));

		if(isset($data->tooltip))
			$this->setTooltip($data->get('tooltip'));

		if(isset($data->html_id))
			$this->setHTMLId($data->html_id);

		if(isset($data->class))
			$this->setStyleClass($data->get('class'));

		if(isset($data->hide_if_empty))
			$this->setHideIfEmpty($data->get('hide_if_empty'));

		if(isset($data->hide_if_hidden))
			$this->setHideIfHidden($data->get('hide_if_hidden'));

        $this->setDataSet(true);
    }
	// }}}
    
    // {{{ setTooltip
    /**
    * method description
    *
    * more detailed method description
    * @param    string $tooltip
    * @return   void
    */
    function setTooltip($tooltip)
    {
		if(!isset($tooltip)) 
			return;
		$replacement = array("\"", "'",  "\r","\n","\r\n");
		$replace_to  = array("\\'","\\'"," " ," " , " "  );
		$this->tooltip = trim(str_replace($replacement,$replace_to,$tooltip));
    }
	// }}}
    
	// {{{ getTooltip
    /**
    * method description
    *
    * more detailed method description
    * @return   string
    */
    function getTooltip()
    {
		return $this->tooltip;
    }
	// }}}
    
    // {{{ setHTMLId
    /**
    * method description
    *
    * more detailed method description
    * @param    string $html_id
    * @return   void
    */
    function setHTMLId($html_id)
    {
		if(!isset($html_id) || !is_scalar($html_id)) 
			return;

		$html_id = ltrim($html_id,"_");

		if(strpos($html_id,'[') !== false || strpos($html_id,']') !== false)
			$html_id = str_replace('[','_',
				str_replace(']','_',$html_id));

		$this->html_id = $html_id;	
    }
	// }}}
    
	// {{{ getHTMLId
    /**
    * method description
    *
    * more detailed method description
    * @return   string
    */
    function getHTMLId()
    {
		return $this->html_id;
    }
	// }}}

	// {{{ assignVars
    /**
    * method description
    *
    * more detailed method description
    * @param    string $tooltip
    * @return   void
    */
    function assignVars()
    {

		if(!$this->getState()) return;
		//TODO rework this part
		if(!empty($this->html_id))
        {
            $final_html_id = $this->getHTMLId();
        }
		if($this->inside_roll || $this->do_increment)
		{
			//$final_html_id = ltrim($this->id,"_")."_".$this->add_html_id;
			//$this->setHTMLId($final_html_id);
			$this->setHTMLId($final_html_id = $this->html_id."_".$this->add_html_id);
		}
		/*else 
		{
			$final_html_id = ltrim($this->id,"_");
			$this->setHTMLId($final_html_id);
		}*/
		if(isset($this->tooltip))
		{
			$html_id = $this->getHTMLId();
			$js = <<<EOD
$(document).ready(function(){
	$('#{$html_id}').tooltip({track: true,delay: 0,showURL: false,showBody:false,opacity: 0.85 });
});
EOD;
			$this->javascript->addBeforeWidget($js);
			$this->setTitle($this->getTooltip());
		}

        if(isset($this->tpl))
        {
            $this->tpl->setParamsArray(array("title"=>isset($this->title)?" title=\"".Language::encodePair($this->getTitle())."\" ":"",
                "id"=>$this->getHTMLId()));
            if(!empty($this->style_class)) 
                $this->tpl->setParamsArray(array("class"=>" class=\"".$this->getStyleClass()."\" "));
            if(isset($this->style) && !$this->style->isEmpty()) 
                $this->tpl->setParamsArray(array("style"=>" style=\"".$this->style->generateStyle()."\" "));
            if(!empty($this->javascript)) 
                $this->tpl->setParamsArray(array("javascript"=>Language::encodePair(" ".$this->javascript->generateJS()),
                    "javascript_before"=>Language::encodePair($this->javascript->getBeforeWidget()),
                    "javascript_after"=>Language::encodePair($this->javascript->getAfterWidget())));
        }
    }
	// }}}
    
	// {{{ handleEvent
	/**
    * method description
    *
    * more detailed method description
    * @param    WidgetEvent $event
    * @return   void
    */
    function handleEvent(WidgetEvent $event)
    {
		if($event->getName() === "increment_id" /*&& $event->inDst($this->getId())*/)
		{
			$this->do_increment = 0 + $event->getParam('do_increment');
		}
		elseif($event->getName() == "all_build_complete")
		{
			$controller = Controller::getInstance();
			$_w2 = $this;
			$has_roll = false;
			while($_w2 && ($_p = $controller->getAdjacencyList()->getParentForId($_w2->getId())) !== null)
				if($controller->getWidget($_p) instanceof WRoll) {$has_roll = true;break;}
				else  $_w2 = $controller->getWidget($_p);
			if($has_roll)
			{
				$controller->getDispatcher()->addSubscriber("increment_id", $this->getId());
				$this->inside_roll = 1;
				$this->do_increment = 1;
			}
		}
    }
	//}}}
    
	//{{{ createMemento
	/**
    * method description
    *
    * more detailed method description
    * @param    void
    * @return   void
    */
	function createMemento()
	{
		$x = new ReflectionClass(get_class($this));
		foreach($this->memento_vars as $v)
			if($x->hasProperty($v))
				if(is_object($this->$v))
					$this->memento[$v] = clone $this->$v;
				else
					$this->memento[$v] = $this->$v;
	}
	//}}}	
    
	//{{{ addToMemento
	/**
    * method description
    *
    * more detailed method description
    * @param    array $vars
    * @return   void
    */
	function addToMemento($vars)
	{
		if(!is_array($vars)) return;
		$this->memento_vars = array_merge($this->memento_vars,$vars);
	}
	//}}}
    
	// {{{ restoreMemento
	/**
    * method description
    *
    * more detailed method description
    * @param    void
    * @return   void
    */
	function restoreMemento()
	{
		foreach($this->memento as $k => $v)
			$this->$k = $this->memento[$k];
	}
	//}}}	

	// {{{ buildComplete
	/**
    * method description
    *
    * more detailed method description
    * @param    void
    * @return   void
    */
	function buildComplete()
	{
		if(empty($this->class_vars))
			$this->class_vars = $this->getProperties();
		if(!$this instanceof iNotSelectable)
			$this->createMemento();
	}
	//}}}	

	// {{{ preRender
	/**
    * method description
    *
    * more detailed method description
    * @param    void
    * @return   void
    */
	function preRender()
    {
        $this->checkAndSetData();


		$controller = Controller::getInstance();
		if($this->do_increment)
			$this->add_html_id++;
        $this->setDataSet(false);
	}
	//}}}	

	// {{{ postRender
	/**
    * method description
    *
    * more detailed method description
    * @param    void
    * @return   void
    */
	function postRender()
	{
		$controller = Controller::getInstance();
		$controller->getDispatcher()->deleteSubscriber("roll_inside", $this->id);
		//$controller->getDispatcher()->deleteSubscriber("increment_id", $this->id);
	}
	//}}}	
    
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
		$controller = Controller::getInstance();
		if(isset($this->hide_if_hidden_id))
		{
			$w = null;
			$w = $controller->getWidget($this->getHideIfHidden());
			if(!empty($w) && (!$w->getVisible() || !$w->getState()))
				$this->setVisible(0);
		}
		if(isset($this->hide_if_empty_id))
		{
			$w = null;
			$w = $controller->getWidget($this->getHideIfEmpty());
			if(isset($w))
			{
				if(method_exists($w,"getText"))
				{
					$t = $w->getText();
					if(empty($t))
						$this->setVisible(0);
				}
				if($w instanceof WControl)
				{
					$v = $w->getValue();
					if(empty($v))
						$this->setVisible(0);
				}
				if(isset($w->items) && $w->items instanceof WidgetCollection && $w->items->isEmpty())
					$this->setVisible(0);
			}
		}
	}
	//}}}	
    
	// {{{ setHideIfHidden
    /**
    * Method description
    *
    * More detailed method description
    * @param    boolean $hidden_id
    * @return   void
    */
    function setHideIfHidden($hidden_id)
    {
		if(!isset($hidden_id)) 
			return;
		$this->hide_if_hidden_id = "".$hidden_id;
    }
    // }}}
    
    // {{{ getHideIfHidden
    /**
    * Method description
    *
    * More detailed method description
    * @param    null
    * @return   string
    */
    function getHideIfHidden()
    {
    	return $this->hide_if_hidden_id;
    }
    // }}}
    
	// {{{ setHideIfEmpty
    /**
    * Method description
    *
    * More detailed method description
    * @param    boolean $empty_id
    * @return   void
    */
    function setHideIfEmpty($empty_id)
    {
		if(!isset($empty_id)) 
		{
			/*$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,
				"Enable parameter is empty"),LOG_LEVEL_WARNING);*/
			return;
		}	
		$this->hide_if_empty_id = "".$empty_id;
    }
    // }}}
    
    // {{{ getHideIfEmpty
    /**
    * Method description
    *
    * More detailed method description
    * @param    null
    * @return   string
    */
    function getHideIfEmpty()
    {
    	return $this->hide_if_empty_id;
    }
    // }}}
	
	// {{{ setStringProcess
    /**
    * Method description
    *
    * More detailed method description
    * @param    boolean $empty_id
    * @return   void
    */
    function setStringProcess($str)
    {
		if(empty($str) || !is_string($str)) return;
		$this->string_process = "".$str;
    }
    // }}}
    
    // {{{ getStringProcess
    /**
    * Method description
    *
    * More detailed method description
    * @param    null
    * @return   string
    */
    function getStringProcess()
    {
    	return $this->string_process;
    }
    // }}}
	
	// {{{ createTemplate
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $path
    * @param    string $tpl_name
    * @return   void
    */
    function createTemplate($path = null, $tpl_name = null)
    {
		if (is_null($path)){
			$conf = Config::getInstance();
			if(is_dir( $path = $conf->root_dir.$conf->vendors_dir."/widgets/templates/".get_class($this)));
			elseif(is_dir($path = $conf->root_dir."/includes/widgets/templates/".get_class($this)));
			else throw new ControllerException(' Template path for widget '.get_class($this).' not found');
		}
		
		if (is_null($tpl_name) && is_null($this->template_name)) $tpl_name = 'default.tpl';
		elseif(isset($this->template_name)&&(is_null($tpl_name)))
            $tpl_name = $this->template_name;

        $tpl_name = $tpl_name.(substr($tpl_name, -4) == '.tpl'?'':".tpl"); 
		return new Template($path,$tpl_name);
    }
    // }}}

    // {{{ getInsideRoll
    /**
    * Method description
    *
    * More detailed method description
    * @param    null
    * @return   string
    */
    function isInsideRoll()
    {
    	return $this->inside_roll;
    }
    // }}}

}
//}}}
?>
