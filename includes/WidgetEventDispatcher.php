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
 * This file contains class for sending messages between widgets.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */

//{{{ WidgetEventDispatcher
/**
 * This class intended to provide low coupling between widgets.
 * It's achieved by message-interchange dispatcher - WidgetEventDispatcher.
 *
 * Only one instance of WidgetEventDispatcher could be in the system.
 * Usually, controller creates this instance during the initialization.
 * This instance could be retrieved later by the Controller's
 * <code>getDispatcher()</code> method.
 *
 * In order to widget could receive notifications it should subscribe 
 * to certain set of events. If such event is fired, special method
 * <code>handleEvent(WidgetEvent $event)</code> will be executed by the dispatcher
 * and {@link WidgetEvent} instance will be passed as an argument.
 *
 * Example of subscribed party:
 * <pre><code>
		$controller = Controller::getInstance();
		$controller->getDispatcher()->
			addSubscriber("some_event",$this->getId());
 * </code></pre>
 *
 * And event "some_event" could be caught in widget with:
 * <pre><code>
 * function handleEvent(WidgetEvent $event)
 * {
 * 		if($event->getName() == "some_event")
 *			echo "widget id=".$event->getSrc()." greeting you!";
 * 		parent::handleEvent($event);
 * 	}
 * </code></pre>
 *
 * <code>parent::handleEvent($event)</code> must exist to process events
 * by the parents' classes.
 *
 * The sender party in order to emit event need to define it by
 * <code>Controller::getInstance()->getDispatcher()->addEvent("some_event");</code> 
 * method.
 *
 * When event name is registered, widget could throw event object by
 * <code>notify(WidgetEvent $event_obj)</code> method.
 *
 * For example:
 * <pre><code>
 * $controller = Controller::getInstance();
 * $controller->getDispatcher()->addEvent("some_event");	
 * $event = new WidgetEvent("some_event",$this->getId());
 * $event->setParams(array("key"=>"value"));
 * $controller->getDispatcher()->notify($event);
 * </code></pre>
 *
 * All widgets, which are subscribed to this event will receive it in
 * <code>handleEvent</code> method.
 */
class WidgetEventDispatcher
{
	private
		/**
		 * Array of registered events
		 * @var array
		 */
		$events = array(),
		/**
		 * Multidimensional array of registered 
		 * subscribers.
		 * @var array
		 */
		$subscribers = array()
		;

	//{{{ addEvent
	/**
	 * Register event to be a valid in time of notify()
	 * execution. Not registered event won't be processed.
	 *
	 * @param string name of the event to register
	 * @return null
	 */
	function addEvent($event_name  = null)
	{
		if(!isset($event_name) || !is_scalar($event_name))
			return;
		$this->events[$event_name] = $event_name;
	}
	//}}}

	//{{{ addSubscriber
	/**
	 * Adds widget, defined by $widget_id 
	 * as a subscriber for the event.
	 *
	 * @param string name of the event
	 * @param string id of the widget to register as a subscriber
	 * @return null
	 */
	function addSubscriber($event_name, $widget_id)
	{
		if(!isset($event_name) || !isset($widget_id)
			|| !is_string($event_name))
			return;
		$this->subscribers[$event_name][] = $widget_id;
	}
	//}}}

	//{{{ deleteEvent
	/**
	 * Deletes event from the list
	 *
	 * @param string name of the event
	 * @return null
	 */
	function deleteEvent($event_name)
	{
		if(!isset($event_name) || !is_string($event_name))
			return;
		if(isset($this->events[$event_name]))
			unset($this->events[$event_name]);
	}
	//}}}

	//{{{ deleteSubscriber
	/**
	 * Deletes one or all subscribers for the given event name.
	 * If the second parameter is defined, all registered subscribers will be removed.
	 *
	 * @param string name of the event
	 * @param mixed it could be either string or null. If null all widgets,
	 * registered for the given event will be removed from the 
	 * subscribers list
	 * @return null
	 */
	function deleteSubscriber($event_name,$widget_id = null)
	{
		if(!isset($event_name, $this->subscribers[$event_name]))
			return;
		$flag = 0;
		$count = count($this->subscribers[$event_name]);
		if(!empty($this->subscribers[$event_name]))
			if(isset($widget_id))
			{
				for($i = 0; $i < $count; $i++)
					if($this->subscribers[$event_name][$i] == $widget_id)
                    {
                        unset($this->subscribers[$event_name][$i]);
				        $this->subscribers[$event_name]  = array_values($this->subscribers[$event_name]);
                        break;
                    }
			}
			else
				for($i = 0; $i < count($this->subscribers[$event_name]); $i++)
					unset($this->subscribers[$event_name][$i]);
	}
	//}}}

	//{{{ notify
	/**
	 * Sends message to the all subscribed widgets.
	 * Name of the event is stored int the $event_obj object 
	 * and should be specified.
	 *
	 * Additionally, destination widget id will be checked.
	 * 
	 * @param WidgetEvent object to be send.
	 * @return null
	 */
	function notify(WidgetEvent $event_obj)
	{
		$controller = Controller::getInstance();
		$event = $event_obj->getName();
		if(!isset($this->events[$event]))
			return;
		if(!empty($this->subscribers[$event]))
			for($i = 0, $c = count($this->subscribers[$event]); $i <$c; $i++)
			{
				$id = $this->subscribers[$event][$i];
				if(!isset($id)) continue;
				if(!$event_obj->inDst($id) || (($src_id = $event_obj->getSrc()) && $src_id == $id)) continue;
				$controller->getWidget($id)->handleEvent($event_obj);
			}
	}
	//}}}
}
// }}}

