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
		$style_class = array(),
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
		$this->setHTMLId($this->getId());
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
    
	//{{{ setTemplate 
	/**
	 * Sets template name to render. By default, the "default.tpl" will be rendered.
	 * This could be set using the template attribute in any widget.
	 *
	 * @param    string name of the template file to render
	 * @return   void
	 */
	function setTemplate($template_name = null)
	{
		if(!isset($template_name)) return;
		$this->template_name = $template_name;
	}
	//}}}
    
	//{{{ getStyleClasses
	/**
	 * Returns the array of style classes which are defined for the 
	 * particular widget. 
	 *
	 * @param    void
	 * @return   array
	 */
	function getStyleClasses()
	{
		return $this->style_class;
	}
	//}}}
    
	//{{{ addStyleClass
	/**
	 * Adds style class (or style classes) to the current list.
	 *
	 * They should be specified using the "class" attribute in widget declaration.
	 * 
	 * The parameter could have several classes to set devided with the spaces. 
	 * This case will be handled properly.
	 *
	 * @param    string name (or name) of classes to add
	 * @return   void
	 */
	function addStyleClass($style_class = null)
	{
		if(!isset($style_class) || !is_string($style_class)) 
			return;
		$this->style_class = array_unique(array_merge($this->style_class, 
			explode(" ", $style_class)));
	}
	//}}}

	//{{{ removeStyleClass
	/**
	 * Removes specified class(es) from the list.
	 *
	 * The supported parameter formats:
	 * - single word string
	 * - several words separated with the whitespace
	 * - array of single strings
	 *
	 * @param    string|array name (or name) of classes to remove
	 * @return   void
	 */
	function removeStyleClass($style_class = null)
	{
		if(!isset($style_class)) 
			return;

		if(!is_array($style_class))
			$style_class = array_filter(explode(" ", $style_class));

		$flipped = array_flip($this->style_class);
		foreach($style_class as $value)
			if($flipped[$value])
				unset($this->style_class[$flipped[$value]]);
		
		$this->style_class = array_values($this->style_class);
	}
	//}}}

	//{{{ toggleStyleClass
	/**
	 * Toggles the presence of given class(es)
	 *
	 * The supported parameter formats:
	 * - single word string
	 * - several words separated with the whitespace
	 * - array of single strings
	 *
	 * @param    string|array name (or name) of classes to toggle
	 * @return   void
	 */
	function toggleStyleClass($style_class = null)
	{
		if(!isset($style_class)) 
			return;

		if(!is_array($style_class))
			$style_class = array_filter(explode(" ", $style_class));

		foreach($style_class as $class)
			if(in_array($class, $this->style_class))
				$this->removeStyleClass($class);
			else
				$this->addStyleClass($class);
	}
	//}}}
    
	//{{{ setVisible 
	/**
	 * Sets visibility of the widget. The hidden widget (with visibility == false)
	 * will pass all the levels of rendering, template parsing and assigning vars.
	 * But at the last stage instead of output, the empty string is returned.
	 *
	 * It could be set using widget's "visible" attribute.
	 *
	 * @param    boolean when to show or not the widget
	 * @return   void
	 */
	function setVisible($visible)
	{
		$this->visible = (bool)$visible;
	}
	//}}}
    
	// {{{ getVisible 
	/**
	 * Returns the visibility state of the widget
	 *
	 * @param    null
	 * @return   boolean
	 */
	function getVisible()
	{
		return $this->visible;
	}
	// }}}

	//{{{ setState
	/**
	 * Defines the state of the widget. It could be enabled/disabled.
	 * So the state value are true/false respectively.
	 *
	 * The disabled widgets are almost as hidden except the are
	 * not passing the "assign vars" and "generate html" stages/
	 *
	 * It could be set using widget's "enabled" attribute.
	 * 
	 * @param    boolean to enable or disabled the widget
	 * @return   void
	 */
	function setState($state)
	{
		$this->state = (bool)$state;
	}
	//}}}

	//{{{ getState
	/**
	 * Returns current enabled/disabled state.
	 *
	 * @param    null
	 * @return   boolean
	 */
	function getState()
	{
		return $this->state;
	}
	//}}}
	
    //{{{ setTitle 
    /**
	 * Defines the HTML "title" attribute. It could be set using
	 * widget's "title" attribute.
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
    //}}}
    
	//{{{ getTitle 
	/**
	 * Returns title the widget
	 *
	 * @param    void
	 * @return   string
	 */
	function getTitle()
	{
		return $this->title;
	}
	//}}}

	//{{{ setAttribute 
	/**
	 * Generic method for setting any type of attributes.
	 * The system properties (which begin with "__")
	 * will be omitted.
	 *
	 * @param    string attribute name
	 * @param    mixed attribute value
	 * @return   void
	 */
	function setAttribute($attribute, $value)
	{
		if(!isset($attribute) || !isset($value)) 
			return false;

		$vars = get_object_vars($this);
		$set = 0;
		foreach($vars as $k=>$v)
			if(substr($k, 0, 2) !== "__" && $attribute == $k)
			{
				$this->$k = $value;
				$set = 1;
				break;
			}

		return $set;
	}
	//}}}
    
    //{{{ getAttribute 
    /**
    * Returns the value of the specified attribute
    *
    * @param    string attribute value to get
    * @return   mixed
    */
    function getAttribute($attribute)
    {
		if(!isset($attribute)) 
			return null;

		$vars = get_object_vars($this);
		foreach($vars as $k=>$v)
			if(substr($k, 0, 2) !== "__" && $attribute == $k)
				return $v;

		return null;
    }
    // }}}
    
    //{{{ getDataSet
	/**
	 * Retrieves the flag which shows whether data
	 * is set to the widget.
	 *
	 * @param    void
	 * @return   bool
	 */
	function getDataSet()
	{
		return $this->data_set;
	}
	//}}}
    
    //{{{ setDataSet
	/**
	 * Assigns the flag which shows whether data
	 * is set to the widget.
	 *
	 * @param    bool the flag to set
	 * @return   void
	 */
    function setDataSet($set)
    {
        $this->data_set = (bool)$set;
    }
    //}}}

	//{{{ checkAndSetData
	/**
	 * Checks if the data could be set for the widget. And 
	 * if everything is ok the data is assigned for the 
	 * particular object, using the DataRetriever helper class.
	 *
	 * It's used generaly by the system and shouldn't be called
	 * from the client's code.
	 *
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
	//}}}

	//{{{ getDataSetterMethod
	/**
	 * Returns name of the method which dispatches the 
	 * data, came from the model. 
	 *
	 * It's used generaly by the system and shouldn't be called
	 * from the client's code.
	 *
	 * @param    void
	 * @return   string
	 */
	function getDataSetterMethod()
	{
		return "setData";
	}
	//}}}
    
	//{{{ parseParams 
	/**
	 * The number two method which is called to initialize all internal
	 * data structures basing on the information, specified in XML.
	 *
	 * It's called by the Controller's buildWidget method and pass
	 * the parsed XML element  with the only widget definition.
	 *
	 * It also initializes events and creates memento snapshot of the 
	 * current object state. It will be need when the object will has to
	 * restore the initial state inside the roll iteration.
	 *
	 * @param    SimpleXMLElement
	 * @return   void
	 */
    function parseParams(SimpleXMLElement $params)
	{
		if(isset($params['enabled'])) 
			$this->setState(0+$params['enabled']);

        $a = $d = null;
        if(isset($params['allow']))
            $a = (string)$params['allow'];
        if(isset($params['deny']))
            $d = (string)$params['deny'];
        if(!ACL::check($a,$d))
            $this->setState(0);

		if(isset($params['title'])) $this->setTitle((string)$params['title']);
		if(isset($params['visible'])) $this->setVisible(0+$params['visible']);

        if(isset($params['class'])) 
            $this->addStyleClass((string)$params['class']);

		if(isset($params['template']))
			$this->setTemplate((string)$params['template']);

        if(isset($params['process']))
            $this->setStringProcess((string)$params['process']);

		$controller = Controller::getInstance();
		$controller->getDispatcher()->addEvent("increment_id");	
		$controller->getDispatcher()->addEvent("all_build_complete");	
		$controller->getDispatcher()->addSubscriber("all_build_complete", $this->getId());;
		
        $this->addToMemento(array("enabled","title","visible","html_id","style_class"));
    }
    //}}}
    
	//{{{ setData
	/**
	 * This method is called during the phase of setting data in
	 * the models' code. So, when all widgets are built, the 
	 * information from all the models are gathered, the
	 * setData method is called over all widgets. 
	 * This information store in {@link WidgetResultSet} object,
	 * passing as a parameter. It's a simply value object, which 
	 * holds data, addressed with the selector.
	 *
	 * It sets the basic info, so the custom widgets should take 
	 * about calling <code>parent::setData()</code> in overloading
	 * <code>setData</code> method.
	 *
	 * @param    WidgetResultSet data object with data to set
	 * @return   void
	 */
    function setData(WidgetResultSet $data)
	{
		$controller = Controller::getInstance();

		if(isset($data->visible))
			$this->setVisible($data->get('visible'));

		if(isset($data->enabled))
			$this->setState($data->get('enabled'));

		if(isset($data->title))
			$this->setTitle($data->get('title'));

		if(isset($data->html_id))
			$this->setHTMLId($data->html_id);

		if(isset($data->addClass))
			$this->addStyleClass($data->get('addClass'));

		if(isset($data->toggleClass))
			$this->toggleStyleClass($data->get('toggleClass'));

		if(isset($data->removeClass))
			$this->removeStyleClass($data->get('removeClass'));

        $this->setDataSet(true);
    }
	//}}}
    
	//{{{ setHTMLId
	/**
	 * Sets id, that will be outputted to the "id" tag attribute. 
	 * It may differs from the object's id property. The additional 
	 * incremental value is adding to it while staying inside the WRoll.
	 *
	 * It's mostly for internal use, but there ara possibilities to change
	 * it from the successor's methods or even by the dataset (but it's not recommended).
	 *
	 * The <code>html_id</code> property is always defined because it initializes
	 * in the constructor of the object.
	 *
	 * @param    string id to set to the HTML tag
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
	//}}}
    
	//{{{ getHTMLId
	/**
	 * Returns value of the <code>html_id</code> property.
	 *
	 * @return   string
	 */
    function getHTMLId()
    {
		return $this->html_id;
    }
	//}}}

	//{{{ assignVars
	/**
	 * This method is called inside the {@link generateHTML} method and 
	 * it sets the information which will be passed to the template.
	 *
	 * It adds only the basic info, so every widget overwrites it to 
	 * add passing data about their own special attributes to the templates.
	 * Do not forget to call <code>parent::assignVars()</code> in this case.
	 *
	 * @param    void
	 * @return   void
	 */
    function assignVars()
    {

		if(!$this->getState()) return;
		if($this->inside_roll || $this->do_increment)
			$this->setHTMLId($this->html_id."_".$this->add_html_id);

        if(!isset($this->tpl)) return;

		$this->tpl->setLanguageAttributeOrEmpty("title",  $this->getTitle());
		$this->tpl->setParamsArray(array("id"=>$this->getHTMLId()));
		$this->tpl->setLanguageAttributeOrEmpty("class",  $this->getTitle());
		if(!empty($this->style_class)) 
			$this->tpl->setLanguageAttributeOrEmpty("class", implode(" ", $this->getStyleClasses()));
    }
	// }}}
    
	//{{{ handleEvent
	/**
	 * This method is called over all widgets when somebody triggers message handling.
	 *
	 * Custom widget class could override this method to add some special event processing, 
	 * but do not forget about calling parent's method.
	 *
	 * All information about the parameters of generated event (such as name, parameters, etc)
	 * could be found in {@link WidgetEvent} object.
	 *
	 * To be the subscriber of the events, method "addSubscriver" of WidgetEventDispatcher object should be called:
	 * <pre><code>
	 *	Controller::getInstance()->getDispatcher()->addSubscriber("__event_name__", "__widget_id__");
	 * </code></pre>
	 * The <code>__widget_id__</code> is usually the <code>$this->getId()</code> call.
	 *
	 * To trigger handling of "__event_name__" eventthere should be such method call:
	 * <pre><code>
	 * Controller::getInstance()->getDispatcher()->notify(new WidgetEvent("__event_name__"));
	 * </pre></code>
	 *
	 * To cancel widget subscription to any event, use such call:
	 * <pre><code>
	 * Controller::getInstance()->getDispatcher()->deleteSubscriber("__event_name__", "__widget_id__");
	 * </code></pre>
	 * 
	 * @param    WidgetEvent $event
	 * @return   void
	 */
    function handleEvent(WidgetEvent $event)
    {
		if($event->getName() == "all_build_complete")
		{
			$controller = Controller::getInstance();
			$_w2 = $this;
			$has_roll = false;
			while($_w2 && ($_p = $controller->getAdjacencyList()->getParentForId($_w2->getId())) !== null)
				if($controller->getWidget($_p) instanceof WRoll) {$has_roll = true;break;}
				else  $_w2 = $controller->getWidget($_p);
			if($has_roll)
			{
				$this->inside_roll = 1;
				$this->do_increment = 1;
			}
		}
    }
	//}}}
    
	//{{{ createMemento
	/**
	 * Memento holds copy of the internal data to recover and thus to
	 * re-create initial state of the widget. It's heavily used inside
	 * the WRoll to revert state and apply info, passed via setData as if
	 * it was untouched widget.
	 *
	 * Not all properties will be reverted, but only those, which was pointed in 
	 * <code>addToMemento</code> method.
	 *
	 * The {@link restoreMemento} method may be called to restore data.
	 *
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
	 * Adds to list properties which should be store in memento while
	 * {@link createMemento} method call.
	 *
	 * @param    array|string the set of string with tne names of properties to store
	 * @return   void
	 */
	function addToMemento($vars)
	{
		if(!is_array($vars) && !is_string($vars)) return;
		if(is_string($vars))
			$vars = array($vars);

		$this->memento_vars = array_merge($this->memento_vars,$vars);
	}
	//}}}
    
	//{{{ restoreMemento
	/**
	 * Restores object state (only that object properties which has been pointed in {@link addToMemento} method) 
	 * with the saved values.
	 *
	 * @param    void
	 * @return   void
	 */
	function restoreMtemento()
	{
		foreach($this->memento as $k => $v)
			$this->$k = $this->memento[$k];
	}
	//}}}	

	//{{{ buildComplete
	/**
	 * This method is called by the Controller class when all actions were made by controller and
	 * widget is ready for use.
	 *
	 * This method could be orverrided to make any setup. The <code>parent::buildComplete</code>
	 * call is required.
	 *
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

	//{{{ preRender
	/**
	 * This is callback right before <code>generateHTML()</code> method invokation.
	 * At this stage all the info is passed from the models, calculated, all event 
	 * handler were called and everything is ready to render a template.
	 *
	 * Inside, this method additionally checks if data was set. The place of invokation of 
	 * <code>checkAndSetData</code> is not strongly limited. In trivial cases, it will be called
	 * here, in <code>WComponent::preRender()</code> method.
	 *
	 * Take in consideration, that if widget is rendering inside the {@link WRoll}, the 
	 * <code>preRender()</code>, <code>generateHTML</code>, <code>postRender</code> methods
	 * will be called on each iteration step.
	 *
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

	//{{{ postRender
	/**
	 * This method is called by the Controller when HTML was generated.
	 * Usually, it's used for cleaning data, event unsubscribing, etc.
	 *
	 * more detailed method description
	 * @param    void
	 * @return   void
	 */
	function postRender()
	{
		$controller = Controller::getInstance();
		$controller->getDispatcher()->deleteSubscriber("roll_inside", $this->id);
	}
	//}}}	
    
	//{{{ messageInterchange
	/**
	 * This method is called by the controller after
	 * <code>buildComplete</code> but before <code>preRender</code>
	 * to arrange communication between widgets using Controller's 
	 * WidgetEventDispatcher object (retrived by the <code>$controller->getDispatcher() call).
	 *
	 * Beware, that data from the models hasn't been assigned yet.
	 *
	 * @param    void
	 * @return   void
	 */
	function messageInterchange()
	{
	}
	//}}}	
    
	// {{{ setStringProcess
    /**
	 * Sets the instructions for string processor, which additionally
	 * modifies the output of the widget from the user's point of view.
	 * For example, if string "capitalize" is passed and the current widget
	 * is {@link WText}:
	 * <pre><code>
	 *	<WText>some text</WText>
	 * </code></pre>
	 * then the HTML value of this tag will be capitalized and
	 * browser will render "<p>SOME TEXT</p>".
    *
    * @param    string string processor intstructions
    * @return   void
	* @see StringProcessor
    */
    function setStringProcess($str)
    {
		if(empty($str) || !is_string($str)) return;
		$this->string_process = "".$str;
    }
    // }}}
    
    // {{{ getStringProcess
    /**
    * Returns string processor instruction string.
    *
    * @param    null
    * @return   string
    */
    function getStringProcess()
    {
    	return $this->string_process;
    }
    // }}}
	
	//{{{ createTemplate
	/**
	 * This method tries to find, create and return the template to render
	 * particular widget.
	 *
	 * If given $path is null the places to search templates are:
	 * 1) /vendors/widgets/templates/__class_name__ (this made to easily override default templates, stored in (2))
	 * 2) /includes/widgets/templates/__class_name__ 
	 *
	 * If the path was found, and no $tpl_name is given, the <code>$this->template_name</code> file is searching.
	 * <code>$tpl_name</code> may optionally ends with ".tpl" extenstion.
	 *
	 * @param    string 
	 * @param    string $tpl_name
	 * @return   void
	 * @throws   WidgetException
	 */
    function createTemplate($path = null, $tpl_name = null)
    {
		if (is_null($path)){
			$conf = Config::getInstance();
			if(is_dir( $path = $conf->root_dir.$conf->vendors_dir."/widgets/templates/".get_class($this)));
			elseif(is_dir($path = $conf->root_dir."/includes/widgets/templates/".get_class($this)));
			else throw new WidgetException(' Template path for widget '.get_class($this).' not found');
		}
		
		if (is_null($tpl_name) && is_null($this->template_name)) $tpl_name = 'default.tpl';
		elseif(isset($this->template_name)&&(is_null($tpl_name)))
            $tpl_name = $this->template_name;

        $tpl_name = $tpl_name.(substr($tpl_name, -4) == '.tpl'?'':".tpl"); 
		return new Template($path,$tpl_name);
    }
    // }}}

	//{{{ getInsideRoll
	/**
	 * Returns boolean flag if current widget is inside {@link WRoll}.
	 *
	 * @param    null
	 * @return   bool
	 */
    function isInsideRoll()
    {
    	return $this->inside_roll;
    }
    // }}}
}
//}}}
?>
