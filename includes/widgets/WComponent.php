<?php
// $Id: $
//
WidgetLoader::load("WObject");
//{{{ WComponent
abstract class WComponent extends WObject
{
		protected
        /**
        * @var      WStyle&
        */
        $style = null,
        /**
        * @var      string
        */
        $style_class = null,
        /**
        * @var      boolean
        */
        $state = 1,
        /**
        * @var      string
        */
        $template_path = "",
        /**
        * @var      string
        */
        $template_name = "",
        /**
        * @var      CTemplate&
        */
        $tpl = null,
                /**
        * @var      string
        */
        $title = null,
		/**
        * @var      string
        */
        $tooltip = null,
        /**
        * @var      WJavaScript&
        */
        $javascript = null,
        /**
        * @var      WDataSet&
        */
        $dataset = null,
        /**
        * @var      boolean
        */
		$visible = 1,
		/**
        * @var      string
        */
        $html_id = "",
		/**
        * @var      array
        */
		$class_vars = array(),
		/**
        * @var      string
        */
		$hide_if_empty_id = null,
		/**
        * @var      string
        */
		$hide_if_hidden_id = null;
		
		private static $w_counter = 0;
		private
		/**
        * @var      boolean
        */
        $inside_roll = 0  ,
		/**
        * @var      boolean
        */
        $do_increment = 0  ,
		/**
        * @var      integer
        */
		$add_html_id=0,
		/**
        * @var      array
        */
		$memento = array(),
		/**
        * @var      array
        */
		$memento_vars = array()
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
	
	// {{{ setID 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $id    
    * @return   void
    */
    function setID($id = null)
	{
		if(!isset($id))
		{
			$id = "__w".(self::$w_counter++);
			$controller = Controller::getInstance();
			$w = $controller->getWidget($id);
			if($w instanceof WComponent)
				$this->setId();
		}
		$this->id = $id;
    }
    // }}}

    // {{{ setEnabled 
    /**
    * Method description
    *
    * More detailed method description
    * @param    boolean $state    
    * @return   void
    */
    function setEnabled($state)
    {
		if(!isset($state)) 
			return;
		$this->state = ($state)?1:0;
    }
    // }}}
    
    // {{{ generateHTML 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function generateHTML()
    {
   		if(!$this->getState()) return "";
       	$this->assignVars();
		if($this->visible) return $this->tpl->getHTML();
		else return "";

	}
    // }}}
    
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
		if(empty($template_name)) 
		{
			$this->template_name = 'default.tpl';
			$this->template_path = Config::get('ROOT_DIR').'/includes/widgets/templates/'.get_class($this);
		}	
		/*elseif(strpos($template_name,"/") !== false)
		{
			preg_match("/(\S*)\/([^\/]{1,})$/",$template_name,$m);
			$this->template_path = $m[1];
			$this->template_name = $m[2];
			$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,
				'Setting custom template: '.$template_name),LOG_LEVEL_INFO);
		}*/
		else
		{
			$this->template_path = Config::get('ROOT_DIR').'/includes/widgets/templates/'.get_class($this);
			if(strpos($template_name,".tpl"))
				$this->template_name = $template_name;
			else $this->template_name = $template_name.".tpl";
		}
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
    function setStyle(WStyle $style)
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
    function setStyleClass($style_class)
    {
		if(!isset($style_class)) 
			return;
		$this->style_class = " ".$style_class;
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
    *
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
    function &getJavaScript()
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
		$setted = 0;
		foreach($vars as $k=>$v)
		{
			if($attribute == $k)
			{
				$this->$k = $value;
				$setted = 1;
				break;
			}
		}
		return $setted;
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
    * @return   WDataSet
    */
    function getDataSet()
    {
		return $this->dataset;
    }
    // }}}
    
    // {{{ setDataSet 
    /**
    * Method description
    *
    * More detailed method description
    * @param    WDataSet& $dataset    
    * @return   void
    */
    function setDataSet(WDataSet $dataset)
    {
		if(!isset($dataset) || !$dataset instanceof WDataSet) 
			return;
		$this->dataset = $dataset;		
    }
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
		if(isset($params['title'])) $this->setTitle($params['title']);
		if(isset($params['visible'])) $this->setVisible(0+$params['visible']);
		if(isset($params['html_id'])) $this->setHTMLId($params['html_id']);

        if(isset($params['class'])) 
            $this->setStyleClass($params['class']);
        $this->setTemplate(isset($params['template'])?$params['template']:null);
		if(isset($params['tooltip']))
			$this->setTooltip($params['tooltip']);

		if(isset($params['hide_if_empty']))
			$this->setHideIfEmpty($params['hide_if_empty']);

		if(isset($params['hide_if_hidden']))
			$this->setHideIfHidden($params['hide_if_hidden']);

		$controller = Controller::getInstance();
		$controller->getDispatcher()->addEvent("increment_id");	
		$controller->getDispatcher()->addSubscriber("roll_inside", $this->getId());
		$controller->getDispatcher()->addSubscriber("increment_id", $this->getId());
		
		$this->addToMemento(array("enabled","title","visible","html_id","style_class","tooltip","javascript","javascript_before","javascript_after"));

    }
    // }}}
    
    // {{{ replaceWithLangConst
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $value  
    * @return   string
    */
    function replaceWithLangConst($value)
    {
		return $value;//Language::encode_admin_pair($value);
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
		if(empty($html_id) || !is_scalar($html_id)) 
			return;

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
		if(!empty($this->html_id))
			$final_html_id = $this->getHTMLId();
		elseif($this->inside_roll || $this->do_increment)
		{
			$final_html_id = $this->id."_".$this->add_html_id;
			$this->add_html_id++;
			$this->setHTMLId($final_html_id);
		}
		else 
		{
			$final_html_id = $this->id;
			$this->setHTMLId($final_html_id);
		}
		if(isset($this->tooltip))
		{
			$html_id = $this->getHTMLId();
			$js = <<<EOD
$(document).ready(function(){
	$('#{$html_id}').tooltip({track: true,delay: 0,showURL: false,showBody: " - ",opacity: 0.85 });
});
EOD;
			$this->javascript->addBeforeWidget($js);
			$this->setTitle($this->getTooltip());
		}

		$this->tpl->setParamsArray(array("title"=>isset($this->title)?" title=\"$this->getTitle()\" ":"","id"=>$this->getHTMLId()));
		if(!empty($this->style_class)) 
			$this->tpl->setParamsArray(array("class"=>" class=\"".$this->getStyleClass()."\" "));
		if(isset($this->style) && !$this->style->isEmpty()) 
			$this->tpl->setParamsArray(array("style"=>" style=\"".$this->style->generateStyle()."\" "));
		if(!empty($this->javascript)) 
			$this->tpl->setParamsArray(array("javascript"=>$this->javascript->generateJS(),
				"javascript_before"=>$this->javascript->getBeforeWidget(),
				"javascript_after"=>$this->javascript->getAfterWidget()));
    }
	// }}}
    
	// {{{ handleEvent
	/**
    * method description
    *
    * more detailed method description
    * @param    Event $event
    * @return   void
    */
    function handleEvent($event)
    {
		if($event->event_name == "roll_inside" )
			$this->inside_roll = 0 + $event->event_params['inside'];
		if($event->event_name == "increment_id")
			$this->do_increment = 0 + $event->event_params['do_increment'];
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
				$t_vars[$v] = $this->$v;
		$this->memento = $t_vars;
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
		{
			foreach($this as $k=>$v)
				$this->class_vars[] = $k;
		}
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
		foreach($this->class_vars as $p)
			if($this->$p instanceof WidgetCollection)
				$this->$p->preRender();

		if(!empty($this->tpl))
			$this->tpl->flushVars();

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
				if(isset($w->items) && $w instanceof WidgetCollection && empty($w->items))
					$this->setVisible(0);
			}
		}
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
		foreach($this->class_vars as $p)
			if($this->$p instanceof WidgetCollection)
				$this->$p->postRender();

		$controller = Controller::getInstance();
		$controller->getDispatcher()->deleteSubscriber("roll_inside", $this->id);
		$controller->getDispatcher()->deleteSubscriber("increment_id", $this->id);
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
		{
			$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,
				"Enable parameter is empty"),LOG_LEVEL_WARNING);
			return;
		}	
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
			$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,
				"Enable parameter is empty"),LOG_LEVEL_WARNING);
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
		if(!isset($path))
			$path = $this->template_path;
		if(!isset($tpl_name))
			$tpl_name = $this->template_name;

		return new Template($path,$tpl_name);
    }
    // }}}

}
//}}}
?>
