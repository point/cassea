<?php
// $Id: WText.php 1020 2008-03-19 17:24:58Z point $
//

// {{{ EventDispatcher
class EventDispatcher
{
	private
		$events,
		$subscribers,
		$log
	;
	
	function __construct()
	{
	}
	function addEvent($event_name)
	{
		if(!isset($event_name))
			return;
		$this->deleteEvent($event_name);
		$this->deleteSubscriber($event_name);
		$this->events[$event_name] = $event_name;
	}
	function addSubscriber($event, $widget_id)
	{
		if(!isset($event) && !isset($widget_id))
		{
			$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,
			"Parameters are empty"),LOG_LEVEL_WARNING);
			return;
		}
/*		if(!isset($this->events[$event]))
		{
			$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,
			"Event not found"),LOG_LEVEL_WARNING);
			return;
		}
*/			
		$this->subscribers[$event][] = $widget_id;
	}
	function deleteEvent($event_name)
	{
		if(!isset($event_name))
		{
			$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,
			"Parameter is empty"),LOG_LEVEL_WARNING);
			return;
		}
		if(isset($this->events[$event_name]))
			unset($this->events[$event_name]);
		else
			$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,
			"Event doesn't exists"),LOG_LEVEL_WARNING);
	}
	function deleteSubscriber($event,$widget_id = null)
	{
		if(!isset($event, $widget_id))
		{
			$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,
			"Parameters are empty"),LOG_LEVEL_WARNING);
			return;
		}
		$flag = 0;
		$count = count($this->subscribers[$event]);
		if(!empty($this->subscribers[$event]))
			if(!empty($widget_id))
			{
				for($i = 0; $i < $count; $i++)
					if($this->subscribers[$event][$i] == $widget_id)
					{
						unset($this->subscribers[$event][$i]);
						$flag = 1;
						//break;
					}
				$this->subscribers[$event]  = array_values($this->subscribers[$event]);
			}
			else
			{
				for($i = 0; $i < count($this->subscribers[$event]); $i++)
					unset($this->subscribers[$event][$i]);
				$flag = 1;
			}
		else $this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,
			"Event not found"),LOG_LEVEL_WARNING);
		if(!$flag) 
			$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,
			"Subscriber not found"),LOG_LEVEL_WARNING);
	}
	function notify($event_obj)
	{
		$controller = &CController::getInstance();
		$event = $event_obj->event_name;
		if(!isset($this->events[$event]))
		{
			$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,
			"Event not found"),LOG_LEVEL_WARNING);
			return;
		}
		if(!empty($this->subscribers[$event]))
			for($i = 0; $i < count($this->subscribers[$event]); $i++)
			{
				$id = $this->subscribers[$event][$i];
				if(isset($id)) $w = &$controller->getWidget($id);
				if(isset($w) && method_exists($w,"handleEvent"))
					$w->handleEvent($event_obj);
				elseif(($vc = $controller->getValueChecker($id)) && isset($vc) && method_exists($vc,"handleEvent"))
				{
					$vc->handleEvent($event_obj);
				}
				else
				{
					$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,
					"Widget or valuechecker not found"),LOG_LEVEL_DEBUG);
					return;
				}
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
