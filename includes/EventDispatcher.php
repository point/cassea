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
		$event = $event_obj->event_name;
		if(!isset($this->events[$event]))
			return;
		if(!empty($this->subscribers[$event]))
			for($i = 0; $i < count($this->subscribers[$event]); $i++)
			{
				$id = $this->subscribers[$event][$i];
				if(isset($id)) $w = $controller->getWidget($id);
				if(isset($w) && method_exists($w,"handleEvent"))
					$w->handleEvent($event_obj);
				elseif(($vc = $controller->getValueChecker($id)) && isset($vc) && method_exists($vc,"handleEvent"))
					$vc->handleEvent($event_obj);
				else
					return;
			}
	}
}
// }}}

// {{{ Event
class Event
{
	public
		$event_name,
		$notifywidget_id,
		$event_params;
}
//}}}

?>
