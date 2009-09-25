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

//{{{ PageHandlerObject
class PageHandlerObject extends DataObject
{
	protected
		/**
		* @var  string
		*/
		$handler_method = null,
		/**
		* @var  DataObjectParams
		*/
		$handler_params = null

	;

	function __construct($static = false)
	{
		parent::__construct($static);
	}
	function parseParams(SimpleXMLElement $elem)
	{
		parent::parseParams($elem);

		if(isset($elem->handler))
		{
			if(isset($elem->handler['method']))
				$this->handler_method = (string)$elem->handler['method'];
			else
				throw new DataObjectException("Handler method was not found");
		}
		$this->handler_params = new DataObjectParams($elem->handler);		
	}

	function handle()
	{
		if(!$this->is_static)
		{
			if(!isset($this->object) && !$this->createObject()) return null;
            
			if(isset($this->handler_method))
			{
				try{
					$r = new ReflectionObject($this->object);
					return $r->getMethod($this->handler_method)->invokeArgs($this->object,$this->handler_params->getParams());
                }catch(ReflectionException $e){ return ;}
			}
		}
		else
		{
			if(isset($this->handler_method))
				try
				{
					$r = new ReflectionClass($this->classname);
					if(!$r->getMethod($this->handler_method)->isAbstract())
						return call_user_func_array($this->classname."::".$this->handler_method,$this->handler_params->getParams());
				}
				catch(ReflectionException $e){return; }
		}
		return null;
	}
}
// }}}
