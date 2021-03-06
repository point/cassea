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
 * This file contains class for managing custom events and 
 * behavior functions, which could be attached to the object.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id:$
 * @package system
 * @since 
 */

//{{{ EventBehavior
/**
 * This class adds great possibilities to extend core objects with various
 * userland methods or makes some user functions to be triggered when 
 * some events are raised inside core objects.
 *
 * Some examples.
 *
 * Due to Controller use this interface, we can make all these things with it:
 *
 * Example of custom event handlers.
 *
 * <pre><code>
 * Controller::getInstance()->onAfterTail = create_function('','echo time();');
 * Controller::getInstance()->onAfterTail = array('Article','processAfterTail');
 * Controller::getInstance()->onAfterTail = array($object,'processAfterTail');
 * </code></pre>
 * 
 * As seen from the example, it could be lambda function, or array with class/object and method name.
 * If object is passed, method will be called dynamically.
 *
 * "on" in the "onAfterTail" indicates, that we want to register event handler. 
 * 'AfterTail' event is triggered inside the Controller object. See docs for more 
 * information.
 *
 * There is pitfall of using events/behaviors. Only object parameters are passed to the handler by reference.
 * As of PHP 5.3 this will be fixed.
 *
 * Example of custom behaviors:
 *
 * <pre><code>
 * Controller::getInstance()->customMethod = create_function('','echo time();');
 * Controller::getInstance()->customMethod = array('Article','doCustomMethod');
 * Controller::getInstance()->customMethod = array($object,'doCustomMethod');
 * Controller::getInstance()->customMethod = new Behavior(create_function('','echo time();'));
 * </code></pre>
 *
 * Using last syntax allowing to enable/disable this behavior by 
 * <pre><code>
 * Controller::getInstance()->disableBehavior('customMethod');
 * </code></pre>
 */
class EventBehavior implements iEventable, iBehaviorable
{
	protected static $__to_delegate = array();

	protected 
		/**
		 * Array of registered events.
		 *
		 * @var array
		 */
		$__events = array(),
		/**
		 * Array of registered behaviors.
		 *
		 * @var array
		 */
		$__behaviors = array(),
		
		$__injected_properties = array();

	
	private static function get_called_class()
	{
		if(function_exists('get_called_class'))
			return get_called_class();

		$bt = debug_backtrace(); 
		$lines = file($bt[1]['file']); 
		preg_match('/([a-zA-Z0-9\_]+)::'.$bt[1]['function'].'/', 
			$lines[$bt[1]['line']-1], 
			$matches); 
		return $matches[1]; 
	}
	static function delegate($name, $value)
	{
		$classname = self::get_called_class();
		self::$__to_delegate[$classname][] = array($name,$value);
	}

	static function undelegate($name)
	{
		$classname = self::get_called_class();
		if(self::$__to_delegate[$classname])
			foreach(self::$__to_delegate[$classname] as &$v)
				if($v[0] == $name) $v = null;
		self::$__to_delegate = array_values(self::$__to_delegate);
	}

	function __construct()
	{
		if(array_key_exists(($classname = get_class($this)),self::$__to_delegate))
			foreach(self::$__to_delegate[$classname] as $v)
			{
				list($name, $value) = $v;
				$this->$name = $value;
			}
		$this->trigger('EventBehaviorConstruct');
	}
	//{{{ __set
	/**
	 * Adding event or behavior to the current object.
	 * 
	 * If method name begins with 'on', it considered to be event. 
	 * Or behavior otherwise.
	 *
	 * @param string the name of the event/behavior
	 * @param mixed It might be callable (in case of lambda-function); array of class/object and  method name;
	 * or instance of Behavior class
	 * @return null
	 * @throws EventException in case of error
	 * @throws BehaviorException in case of error
	 */
	function __set($name, $value)
	{
		if(empty($name) || !is_string($name))
			throw new EventException("Wrong parameter");

		$name = strtolower($name);

		if(method_exists($this, $name) || property_exists_safe(get_class($this),$name))
			throw new EventException("Method or property $name in class ".get_class($this)." already exists");


		if(stripos($name,'on') === 0)
			// event
			if(!is_callable($value) && !is_string($value) && (!is_array($value) || count($value) != 2))
				throw new EventException("Wrong callback function in event $name");
			else
				$this->__events[substr($name,2)][] =	$value;
		else
			// behavior
			if(!is_callable($value) && !$value instanceof Behavior && (!is_array($value) || count($value) != 2))
				$this->__injected_properties[$name] = $value;
			elseif(array_key_exists($name,$this->__behaviors))
				throw new BehaviorException("Behavior {$name} already exists");
			else
				$this->__behaviors[$name] = $value;

	}
	//}}}

	// retrieve injected properties
	function __get($name)
	{
		if(!array_key_exists($name,$this->__injected_properties))
			throw new BehaviorException("Injected property '$name' doesn't exist");
		return $this->__injected_properties[$name];
	}

	//{{{ __isset
	/**
	 * Checks whenever given event or behavior is attached to the object.
	 *
	 * As of {@link __set} method, if $name begins with 'on', it considered to be event
	 *
	 * @param string name of the event/behavior
	 * @return bool true if specified event/behavior registered.
	 */
	function __isset($name)
	{
		if(empty($name) || !is_string($name))
			throw new EventException("Wrong parameter");

		$name = strtolower($name);
		if(strpos($name, "on") === 0)
			$name = substr($name,2);

		return ((isset($this->__events[$name]) && count($this->__events[$name])) || isset($this->__behaviors[$name]));
	}
	//}}}

	//{{{ __unset
	/**
	 * Detaches event or behaviors from the object.
	 *
	 * As of {@link __set} method, if $name begins with 'on', it considered to be event and
	 * all event handlers for this name will be detached.
	 *
	 * @param string name of the event/behavior
	 * @return null
	 */
	function __unset($name)
	{
		if(empty($name) || !is_string($name))
			throw new EventException("Wrong parameter");

		$name = strtolower($name);
		if(strpos($name, "on") === 0)
		{
			$name = substr($name,2);
			if(isset($this->__events[$name]))
				unset($this->__events[$name]);
		}
		elseif(isset($this->__behaviors[$name]))
			unset($this->__behaviors[$name]);

	}
	//}}}

	//{{{ trigger
	/**
	 * Will cause event propagation.
	 *
	 * It usually called by the internal classes, and all 
	 * registered event handlers for this event name is called.
	 * 
	 * @param string name of the raising event
	 * @param mixed it could be an array if it's necessary to make a call with more
	 * than one parameter. Or plain value, if it's enough.
	 * @throws EventException in case of error
	 */
	function trigger($event_name, $data = array())
	{
		if(empty($event_name) || !is_string($event_name))
			throw new EventException("Wrong triggered event name");
		$event_name = strtolower($event_name);

		if(!isset($this->__events[$event_name])) return $data;

		$ret = null;
		$data = is_array($data)?$data:array($data);
		foreach($this->__events[$event_name] as &$callback)
			$ret = call_user_func_array($callback, $data);
		return $ret;
	}
	//}}}

	//{{{ __call
	/**
	 * This function is called  when user trying to make a call of 
	 * non-existent method. It will look up given name upon the registered 
	 * behaviors and if such behavior was found it will be called.
	 *
	 * Otherwise, CasseaException exception will be raised.
	 *
	 * @param string name of the method, trying to call
	 * @param mixed parameters to pass to the desired behavior. 
	 * It could be array or single value.
	 * @return mixed the output of the aggregated method or callable object
	 * @throws CasseaException if there is no such behavior
	 * @throws BehaviorException if other error occurred.
	 */
	function __call($name, $arguments = array())
	{
		if(empty($name) || !is_string($name))
			throw new BehaviorException("Wrong behavior method name");

		$name = strtolower($name);
		if(!isset($this->__behaviors[$name])) 
			throw new CasseaException("Class ".get_class($this)." doesn't have method $name");

		if(!is_array($arguments) || empty($arguments))
			$arguments = array($arguments);

		if($arguments[0] && $arguments[0] !== $this)
			array_unshift($arguments, $this);

		$t = $this->__behaviors[$name] ;
		if($t instanceof Behavior)
			if($t->getEnabled())
				return call_user_func_array($t->getCallback(),$arguments);
			else 
				throw new BehaviorException("Behavior $name temporary disabled");
		else
			return call_user_func_array($t,$arguments);
	}
	//}}}

	//{{{ disableBehavior
	/**
	 * Temporary disable given behavior. This function might be used only if 
	 * desired behavior was registered via {@link Behavior} class.
	 * Otherwise exception will be raised.
	 *
	 * For example:
	 * <pre><code>
	 * Controller::getInstance()->customMethod = new Behavior(create_function('','echo time();'));
	 * Controller::getInstance()->disableBehavior('customMethod');
	 * </code></pre>
	 *
	 * @param string name of the behavior to be disabled
	 * @return null
	 * @throws BehaviorException in case of errors
	 * @see enableBehavior
	 */
	function disableBehavior($name)
	{
		if(empty($name) || !is_string($name))
			throw new BehaviorException("Wrong behavior method name");

		$name = strtolower($name);

		if(!isset($this->__behaviors[$name]))
			throw new BehaviorException("Behavior $name doesn't exists");
		if(!$this->__behaviors[$name] instanceof Behavior)
			throw new BehaviorException("Behavior $name could not be disabled. Should be instance of Behavior class");
		
		$this->__behaviors[$name]->setEnabled(0);
		
	}
	//}}} 

	//{{{ enable
	/**
	 * Enables temporary disabled behavior with given name. 
	 * This function might be used only if 
	 * desired behavior was registered via {@link Behavior} class.
	 * Otherwise exception will be raised.
	 *
	 * For example:
	 * <pre><code>
	 * Controller::getInstance()->customMethod = new Behavior(create_function('','echo time();'));
	 * Controller::getInstance()->disableBehavior('customMethod');
	 * Controller::getInstance()->enableBehavior('customMethod');
	 * </code></pre>
	 *
	 * @param string name of the behavior to be enabled
	 * @return null
	 * @throws BehaviorException in case of errors
	 * @see enableBehavior
	 */
	function enableBehavior($name)
	{
		if(empty($name) || !is_string($name))
			throw new BehaviorException("Wrong behavior method name");

		$name = strtolower($name);

		if(!isset($this->__behaviors[$name]))
			throw new BehaviorException("Behavior $name doesn't exists");
		if(!$this->__behaviors[$name] instanceof Behavior)
			throw new BehaviorException("Behavior $name could not be disabled. Should be instance of Behavior class");
		
		$this->__behaviors[$name]->setEnabled(1);
	}
	//}}}

	//$object = class or object
	function mix($object)
	{
		$class_name = is_object($object)?get_class($object):$object;

		foreach(t(new ReflectionClass($object))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) 
			if(substr($method->name, 0, 2) == "__") continue;
			else
				$this->{$method->name} = $method->isStatic()?
					array($class_name, $method->name):array($object, $method->name);
	}
}
//}}}
