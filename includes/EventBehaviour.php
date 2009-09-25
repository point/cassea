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

// $Id:$

class EventBehaviour implements iEventable, iBehaviourable
{
	protected 
		$events = array(),
		$behaviours = array();

	function __set($name, $value)
	{
		if(empty($name) || !is_string($name))
			throw new EventException("Wrong parameter");

		$name = strtolower($name);

		if(method_exists($this, $name) || property_exists(get_class($this),$name))
			throw new EventException("Method or property $name in class ".get_class($this)." already exists");


		if(stripos($name,'on') === 0)
			// event
			if(!is_callable($value) && !is_string($value) && (!is_array($value) || count($value) != 2))
				throw new EventException("Wrong callback function in event $name");
			else
				$this->events[substr($name,2)][] =	$value;
		else
			// behaviour
			if(!is_callable($value) && !is_string($value) && !$value instanceof Behaviour && (!is_array($value) || count($value) != 2))
				throw new BehaviourException("Wrong callback function in behaviour $name");
			else
				$this->behaviours[$name] = $value;

	}
	function __isset($name)
	{
		if(empty($name) || !is_string($name))
			throw new EventException("Wrong parameter");

		$name = strtolower($name);
		if(strpos($name, "on") === 0)
			$name = substr($name,2);

		return ((isset($this->events[$name]) && count($this->events[$name])) || isset($this->behaviours[$name]));
	}
	function __unset($name)
	{
		if(empty($name) || !is_string($name))
			throw new EventException("Wrong parameter");

		$name = strtolower($name);
		if(strpos($name, "on") === 0)
			$name = substr($name,2);

		if(isset($this->events[$name]))
			unset($this->events[$name]);
		elseif(isset($this->behaviours[$name]))
			unset($this->behaviours[$name]);

	}
	function trigger($event_name, $data = array())
	{
		if(empty($event_name) || !is_string($event_name))
			throw new EventException("Wrong triggered event name");
		$event_name = strtolower($event_name);

		if(!isset($this->events[$event_name])) return;
		
		if(!is_array($data))
			$data = array($data);
		foreach($this->events[$event_name] as $callback)
			return call_user_func_array($callback, $event_name, $data);
	}
	function __call($name, $arguments = array())
	{
		if(empty($name) || !is_string($name))
			throw new BehaviourException("Wrong behaviour method name");

		$name = strtolower($name);
		if(!isset($this->behaviours[$name])) 
			throw new CasseaException("Class ".get_class($this)." doesn't have method $name");

		if(!is_array($arguments))
			$arguments = array($arguments);

		$t = $this->behaviours[$name] ;
		if($t instanceof Behaviour)
			if($t->getEnabled())
				return call_user_func_array($t->getCallback(),$arguments);
			else return null;
		else
			return call_user_func_array($t,$arguments);
	}
	function disableBehaviour($name)
	{
		if(empty($name) || !is_string($name))
			throw new BehaviourException("Wrong behaviour method name");

		$name = strtolower($name);

		if(!isset($this->behaviours[$name]))
			throw new BehaviourException("Behaviour $name doesn't exists");
		if(!$this->behaviours[$name] instanceof Behaviour)
			throw new BehaviourException("Behaviour $name could not be disabled. Should be instance of Behaviour class");
		
		$this->behaviours[$name]->setEnabled(0);
		
	}
	function enableBehaviour($name)
	{
		if(empty($name) || !is_string($name))
			throw new BehaviourException("Wrong behaviour method name");

		$name = strtolower($name);

		if(!isset($this->behaviours[$name]))
			throw new BehaviourException("Behaviour $name doesn't exists");
		if(!$this->behaviours[$name] instanceof Behaviour)
			throw new BehaviourException("Behaviour $name could not be disabled. Should be instance of Behaviour class");
		
		$this->behaviours[$name]->setEnabled(1);
	}
}
