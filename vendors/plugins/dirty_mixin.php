<?php

class DirtyMixin
{
	private $values = array();
	private $properties= array();
	private $mixer_object = null;
	private $__cached_values = array();

	function __construct($mixer_object, $exclude_properties = array())
	{
		if(!is_object($mixer_object))
			throw new CasseaException("Parameter, passed to the DirtyMixin class should be valid object");
		
		$this->mixer_object = $mixer_object;
		if(is_scalar($exclude_properties))
			$exclude_properties = array($exclude_properties);
		$this->exclude_properties = $exclude_properties;

		$this->values = $this->getPropertiesValues();
		
	}
	private function getPropertiesValues()
	{
		$values = array();

		foreach(t(new ReflectionObject($this->mixer_object))->getProperties() as $property) 
			if((!empty($this->exclude_properties) && in_array($property->name, $this->exclude_properties)) ||
				substr($property->name,0,2) == "__") continue;
			else 
			{
				$property->setAccessible(true);
				$value = $property->getValue($this->mixer_object);
				$values[$property->name] = is_object($value)?spl_object_hash($value):$value;
			}
			 
		return $values;
	}
	public function dirty()
	{
		return $this->values != ($this->__cached_values = $this->getPropertiesValues());
	}
	public function whatsDirty()
	{
		$ret = array();
		foreach($this->values as $k=>$v)
			if($v != $this->__cached_values[$k])
				$ret[] = $k;
		return $ret;
	}
	function __destruct() 
	{
		unset($this->mixer_object);
	}
}
