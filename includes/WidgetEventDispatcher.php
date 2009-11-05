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

//{{{ WidgetEventDispatcher
class WidgetEventDispatcher
{
	private
		$events = array(),
		$subscribers = array()
	;
	
	function addEvent($event_name  = null)
	{
		if(!isset($event_name) || !is_scalar($event_name))
			return;
		/*$this->deleteEvent($event_name);
        $this->deleteSubscriber($event_name);*/
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
		if(!isset($event_name) || !is_string($event_name))
			return;
		if(isset($this->events[$event_name]))
			unset($this->events[$event_name]);
	}
	function deleteSubscriber($event,$widget_id = null)
	{
		if(!isset($event, $this->subscribers[$event]))
			return;
		$flag = 0;
		$count = count($this->subscribers[$event]);
		if(!empty($this->subscribers[$event]))
			if(isset($widget_id))
			{
				for($i = 0; $i < $count; $i++)
					if($this->subscribers[$event][$i] == $widget_id)
                    {
                        unset($this->subscribers[$event][$i]);
				        $this->subscribers[$event]  = array_values($this->subscribers[$event]);
                        break;
                    }
			}
			else
				for($i = 0; $i < count($this->subscribers[$event]); $i++)
					unset($this->subscribers[$event][$i]);
	}
	function notify(WidgetEvent $event_obj)
	{
		if(empty($event_obj)) return;
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
}
// }}}

