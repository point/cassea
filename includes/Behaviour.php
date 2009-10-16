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
 * This file contains class for managing behaviours.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */


//{{{ Behaviour
/**
 * Class Behaviour defines behaviour, that could be disabled/enabled. 
 * If you need just simple behaviour action, see {@link EventBehaviour} instead.
 */
class Behaviour
{
	private 
		/**
		 * @var bool state of current behaviour
		 */
		$enabled = 1,
		/**
		 * @var mixed callback to be called when behavoiur is used. Could be either array, string, callable.
		 */
		$callback  = null
		;

	//{{{ __construct
	/**
	 * Constructor of behavoiur. initialize object variables
	 *
	 * @param mixed callback to be called when behavoiur is used. Could be iether array, string or callable.
	 * @param bool defines should be newly created behaviour enabled or not. Default is true.
	 * @return null
	 * @throws BehaviourException on wrong callback parameters.
	 * @see EventBehaviour::__set
	 */
	function __construct($callback,$enabled = 1)
	{
		if(!is_callable($callback) && !is_string($callback) && (!is_array($callback) || count($callback) != 2))
			throw new BehaviourException("Wrong callback parameter");

		$this->callback = $callback;
		$this->enabled = (bool)$enabled;
	}
	//}}}

	//{{{ getEnabled
	/**
	 * Return enabled state of current behaviour.
	 *
	 * @param null
	 * @return bool state of current behaviour.
	 */
	function getEnabled()
	{
		return (bool)$this->enabled;
	}
	//}}}

	//{{{ setEnabled
	/** 
	 * Sets state of current behaviour.
	 *
	 * @param bool new state of behaviour
	 * @retrun null
	 */
	function setEnabled($enabled = 1)
	{
		$this->enabled = (bool)$enabled;
	}
	//}}}

	//{{{ getCallback
	/**
	 * Returns callback of current behaviour
	 *
	 * Mostly used for internal puproses.
	 *
	 * @param null
	 * @retrun mixed see {@link __construct} for return data format
	 */
	function getCallback()
	{
		return $this->callback;
	}
	//}}}
}
//}}}
