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

// $Id$
//
class TemplateException extends Exception{}

class Template
{
	private 
		$path = null,
		$filename = null,
		$params = null;
	function __construct($path,$filename)
	{
		$path = rtrim($path,"/");
		$filename = ltrim($filename,"/");
		if(!file_exists($path."/".$filename))
			throw(new TemplateException("template '$path/$filename' does not exists"));
		$this->path = $path;
		$this->filename = $filename;
		$this->params = new TemplateParams;
	}
	function setParams(TemplateParams $p)
	{
		$this->params->merge($p);
	}
	function setParamsArray($arr)
	{
		foreach($arr as $k => $v)
			$this->params->set($k,$v);
	}
	function flushVars()
	{
		$this->params = new TemplateParams;
	}
	function getHTML()
	{
		ob_start();
		$p = $this->params;
		include($this->path."/".$this->filename);
		$s = ob_get_contents();
		ob_end_clean();
		return $s;
	}
}
class TemplateParams
{
	private 
		$properties = array()
		;
	function __set($name = null,$val = null)
	{
		$this->set($name,$val);
	}
	function __get($name)
	{
		if(isset($this->properties[$name]))
			return $this->properties[$name];
		return null;
	}
	function __isset($name)
	{
		return isset($this->properties[$name]);
	}
	function set($name = null,$val = null)
	{
		if(!isset($val) || empty($name)) return $this;

		if(!isset($this->properties[$name]))
			$this->properties[$name] = new TemplateParam($val);
		else
			$this->properties[$name]->setProp($val);
		return $this;
	}
	function attr()
	{
		return $this->properties;
	}
	function merge(TemplateParams $t)
	{
		foreach($t->attr() as $k=>$v)
				$this->properties[$k] = $v;
	}
}
class TemplateParam implements IteratorAggregate,ArrayAccess
{
	private 
		$scalar = null,
		$array = null,

		$cur = 0

		;
	function __construct($param = null)
	{
		if(!isset($param) ) return;
		$this->setProp($param);
	}
	function setProp($param )
	{
		if(is_scalar($param))
			$this->scalar = $param;
		elseif(is_array($param))
			$this->array = new ArrayObject($param);
	}
	function getIterator()
	{
		return $this->array->getIterator();
	}
	function offsetExists($offset)
	{
		return $this->array->offsetExists($offset);
	}
	function offsetGet($offset)
	{
		return $this->array->offsetGet($offset);
	}
	function offsetSet($offset,$value)
	{
		return $this->array->offsetSet($offset,$value);
	}
	function offsetUnset($offset)
	{
		return $this->array->offsetUnset($offset);
	}
	function __toString()
	{
		return (string)$this->scalar;
	}
}
?>
