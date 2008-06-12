<?php
// $Id: WText.php 1020 2008-03-19 17:24:58Z point $
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
	function set($name = null,$val = null)
	{
		if(!isset($val) || empty($name)) return;

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
