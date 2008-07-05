<?php
// $Id: $
//

// {{{ EventDispatcher
class EventDispatcher
{
	private
		$events = array(),
		$subscribers = array()
	;
	
	function addEvent($event_name  = null)
	{
		if(!isset($event_name) || !is_scalar($event_name))
			return;
		$this->deleteEvent($event_name);
		$this->deleteSubscriber($event_name);
		$this->events[$event_name] = $event_name;
	}
	function addSubscriber($event, $widget_id)
	{
		if(!isset($event) || !isset($widget_id))
			return;
		$this->subscribers[$event][] = $widget_id;
	}
	function deleteEvent($event_name)
	{
		if(!isset($event_name))
			return;
		if(isset($this->events[$event_name]))
			unset($this->events[$event_name]);
	}
	function deleteSubscriber($event,$widget_id = null)
	{
		if(!isset($event, $widget_id))
			return;
		$flag = 0;
		$count = count($this->subscribers[$event]);
		if(!empty($this->subscribers[$event]))
			if(!empty($widget_id))
			{
				for($i = 0; $i < $count; $i++)
					if($this->subscribers[$event][$i] == $widget_id)
						unset($this->subscribers[$event][$i]);
				$this->subscribers[$event]  = array_values($this->subscribers[$event]);
			}
			else
				for($i = 0; $i < count($this->subscribers[$event]); $i++)
					unset($this->subscribers[$event][$i]);
	}
	function notify(Event $event_obj)
	{
		if(empty($event_obj)) return;
		$controller = Controller::getInstance();
		$event = $event_obj->getName();
		if(!isset($this->events[$event]))
			return;
		if(!empty($this->subscribers[$event]))
			for($i = 0; $i < count($this->subscribers[$event]); $i++)
			{
				$id = $this->subscribers[$event][$i];
				if(isset($id)) $w = $controller->getWidget($id);
				if(isset($w) && method_exists($w,"handleEvent"))
					$w->handleEvent($event_obj);
				/*elseif(($vc = $controller->getValueChecker($id)) && isset($vc) && method_exists($vc,"handleEvent"))
					$vc->handleEvent($event_obj);*/
				else
					return;
			}
	}
}
// }}}

class EventException extends Exception {}

// {{{ Event
class Event
{
	protected
		$event_name = null,
		$src_id = null,
		$dst_ids = array(),
		$event_params = array()
		;
	function __construct($event_name,$src_id = null, $dst_ids = null,$event_params = null)
	{
		if(isset($event_name))
			$this->event_name = $event_name;
		else throw EventException("Event name must be specified");

		if(isset($src_id))
			$this->setSrc($src_id);
		if(isset($dst_ids))
			$this->setDst($dst_ids);
		if(isset($event_params))
			$this->setParams($event_params);
	}
	function getName()
	{
		return $this->event_name;
	}
	function setSrc($id)
	{
		$this->src_id = $id;
	}
	function getSrc()
	{
		return $this->src_id;
	}
	function setDst($dst)
	{
		if(is_string($dst))
			$this->dst_ids = array($dst);
		elseif(is_array($dst))
			$this->dst_ids = $dst;
	}
	function inDst($id)
	{
		return in_array($id,$this->dst_ids);
	}
	function setParams($params)
	{
		if(!is_array($params)) return;
		$k = key($params);
		$this->event_params[$k] = $params[$k];
	}
	function getParam($param_name)
	{
		return isset($this->event_params[$param_name])?
			$this->event_params[$param_name]:null;
	}
}
//}}}

?>
