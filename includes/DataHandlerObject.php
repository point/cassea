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

//{{{ DataHandlerObject
class DataHandlerObject extends DataObject
{
	protected
		/**
		* @var  string
		*/
		$handler_methods = array(),
		/**
		* @var  DataObjectParams
		*/
		$handler_params = array(),
		/**
		* @var  string
		*/
		$checker_methods = array(),
		/**
		* @var  DataObjectParams
		*/
		$checker_params = array()

	;

	function __construct($static = false)
	{
		parent::__construct($static);
	}
	function parseParams(SimpleXMLElement $elem)
	{
		parent::parseParams($elem);

        foreach($elem->handler as $handler)
		{
			if(isset($handler['method']))
				$this->handler_methods[] = (string)$handler['method'];
			else
				throw new DataObjectException("Handler method was not found");

		    $this->handler_params[] = new DataObjectParams($handler);		
		}
        
        foreach($elem->checker as $checker)
		{
			if(isset($checker['method']))
				$this->checker_methods[] = (string)$checker['method'];
			else
				throw new DataObjectException("Checker method was not found");

			$this->checker_params[] = new DataObjectParams($checker);		
		}
	}

	function check(HTTPParamHolder $post)
	{
		if(!$this->is_static)
		{
			if(!isset($this->object) && !$this->createObject()) return null;
			foreach($this->checker_methods as $ind => $checker)
			{
				try{
					$r = new ReflectionObject($this->object);
					$arr = $this->checker_params[$ind]->getParams();
					array_unshift($arr,$post);
					$r->getMethod($checker)->invokeArgs($this->object,$arr);
				}catch(ReflectionException $e){ return ;}
			}
		}
		else
        {
            $this->requireClasses();
            foreach($this->checker_methods as $ind => $checker)
            {
                try
                {
                    $r = new ReflectionClass($this->classname);
                    $arr = $this->checker_params[$ind]->getParams();
                    array_unshift($arr,$post);
                    if(!$r->getMethod($checker)->isAbstract())
                        call_user_func_array($this->classname."::".$checker,$arr);
                }
                catch(ReflectionException $e){return; }
            }
		}
		return ;
	}

	function handle($post)
	{
		if(!$this->is_static)
		{
			if(!isset($this->object) && !$this->createObject()) return null;
            if(!empty($this->handler_methods))
                foreach($this->handler_methods as $ind => $handler)
                    try{
                        $r = new ReflectionObject($this->object);
                        $arr = $this->handler_params[$ind]->getParams();
                        array_unshift($arr,$post);
                        $r->getMethod($handler)->invokeArgs($this->object,$arr);
                    }catch(ReflectionException $e){ return ;}
			elseif(!empty($post))
				foreach($post as $name=>$value)
					$this->handleInObject($name,$value);
		}
		else
		{
            $this->requireClasses();
            if(!empty($this->handler_methods))
                foreach($this->handler_methods as $ind => $handler)
                    try
                    {
                        $r = new ReflectionClass($this->classname);
                            $arr = $this->handler_params[$ind]->getParams();
                            array_unshift($arr,$post);
                        if(!$r->getMethod($handler)->isAbstract())
                            call_user_func_array($this->classname."::".$handler,$arr);
                    }
                    catch(ReflectionException $e){return; }
			elseif(!empty($post))
				foreach($post as $name=>$value)
					$this->handleInStatic($name,$value);
		}
		return null;
	}

	protected function handleInObject($name,$value)
	{
		if(!isset($name) || !is_object($this->object)) return;

		if(method_exists($this->object,"set".ucfirst(strtolower($name))))
			return call_user_func(array($this->object,"set".ucfirst(strtolower($name))),$value);
		if(method_exists($this->object,"set_".strtolower($name)))
			return call_user_func(array($this->object,"set_".strtolower($name)),$value);
	}
	protected function handleInStatic($name,$value)
	{
		if(!isset($name) || !$this->is_static) return;

		if(method_exists($this->classname,"set".ucfirst(strtolower($name))))
			return call_user_func($this->classname."::set".ucfirst(strtolower($name)),$value);
		if(method_exists($this->classname,"set_".strtolower($name)))
			return call_user_func($this->classname,"::set_".strtolower($name),$value);
		return;

	}
}
// }}}
