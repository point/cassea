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
 * This file contains class which incapsulates all data
 * for message interchanging between widgets.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */

//{{{ WidgetEvent
/**
 * This class aggregates all need information for passing messages 
 * between widgets.
 * Typical use-case :
 * <pre><code>
 * $event = new WidgetEvent("event_name",$id_of_widget);
 * $event->setParams(array("parameter_name1"=>$parameter_value1));
 * </code></pre>
 *
 * Newly created object could be passed as an argument to the 
 * <code>notify()</code> method of {@link WidgetEventDispatcher}.
 *
 * Besides, it also possible to create event, addressed to all widgets,
 * registered in the system (broadcast message).
 */
class WidgetEvent
{
	protected
		/**
		 * Name of the event.
		 * @var string
		 */
		$event_name = null,
		/**
		 * Id of the source widget
		 * @var string
		 */
		$src_id = null,
		/**
		 * Destination id. 
		 * @var mixed. Could be either string or null for
		 * broadcast messaging
		 */
		$dst_ids = array(),
		/**
		 * Parameters, that should be stored in the event.
		 * @var array
		 */
		$event_params = array()
		;

	//{{{ __construct
	/**
	 * Creates new instance of the event.
	 *
	 * @param string name of the event
	 * @param mixed id of the widget, the source of the event. 
	 * It could be string or null. In last case, receiver-party could not
	 * determine source of the event
	 * @param mixed id (or array of ids) of the destination widget. It could be null in order to
	 * create broadcast message.
	 * @param array event params. Could be specified later with {@link setParams} method.
	 */
	function __construct($event_name,$src_id = null, $dst_ids = null,$event_params = null)
	{
		if(isset($event_name))
			$this->event_name = $event_name;
		else throw WidgetEventException("Event name must be specified");

		if(isset($src_id))
			$this->setSrc($src_id);
		if(isset($dst_ids))
			$this->setDst($dst_ids);
		if(isset($event_params))
			$this->setParams($event_params);
	}
	//}}}

	//{{{ getName
	/**
	 * @param null
	 * @return string the name of the current event
	 */
	function getName()
	{
		return $this->event_name;
	}
	//}}}

	//{{{ setSrc
	/**
	 * Defines source widget id.
	 * @param string id of the source widget
	 * @return null
	 */
	function setSrc($id)
	{
		$this->src_id = $id;
	}
	//}}}

	//{{{ getSrc
	/**
	 * @param null
	 * @return string id of the source widget
	 */
	function getSrc()
	{
		return $this->src_id;
	}
	//}}}

	//{{{ setDst
	/**
	 * @param mixed id of the destination widget. It could be string (single id)
	 * or array (list of ids).
	 * @return null
	 */
	function setDst($dst)
	{
		if(is_string($dst))
			$this->dst_ids = array($dst);
		elseif(is_array($dst))
			$this->dst_ids = $dst;
	}
	//}}}

	//{{{ idDst
	/**
	 * Checks, whenever given id is in the list of recipients.
	 * @param string id to check
	 * @return bool
	 */
	function inDst($id)
	{
		// broadcast message
		if(empty($this->dst_ids)) return true;

		return in_array($id,$this->dst_ids);
	}
	//}}}

	//{{{ setParams
	/**
	 * Merges current params with the passed array.
	 *
	 * @param array of key=>value
	 * @return null
	 */
	function setParams($params)
	{
		if(!is_array($params)) return;
		foreach($params as $k => $v)
			$this->event_params[$k] = $v;
	}
	//}}}

	//{{{ getParam
	/**
	 * Retrieves parameter by its name.
	 *
	 * @param string name of the parameter
	 * @return mixed value, stored by {@link setParams}
	 */
	function getParam($param_name)
	{
		return isset($this->event_params[$param_name])?
			$this->event_params[$param_name]:null;
	}
	//}}}
    //TODO: __get and fluent interfaces
}
//}}}

