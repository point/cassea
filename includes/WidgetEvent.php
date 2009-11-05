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

//{{{ WidgetEvent
class WidgetEvent
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
		else throw WidgetEventException("Event name must be specified");

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
		// broadband message
		if(empty($this->dst_ids)) return true;

		return in_array($id,$this->dst_ids);
	}
	function setParams($params)
	{
		if(!is_array($params)) return;
		foreach($params as $k => $v)
			$this->event_params[$k] = $v;
	}
	function getParam($param_name)
	{
		return isset($this->event_params[$param_name])?
			$this->event_params[$param_name]:null;
	}
    //TODO: __get and fluent interfaces
}
//}}}

