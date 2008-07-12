<?php
class HTTPParamHolder
{
	private $vars = array(),
		$checked_vars = array();
	function __construct(array $vars,$allow_array = true)
	{
		if(!empty($this->vars) || !empty($this->checked_vars)) return;
		if(!empty($vars))
			$this->vars = $vars;
		foreach($vars as $k=>&$v)
			if((is_scalar($v) && mb_check_encoding($k,"UTF8") && mb_check_encoding($v,"UTF8"))||
				$allow_array &&!is_scalar($v) && mb_check_encoding($k,"UTF8"))
				$this->checked_vars[$k] = &$v;
	}
	function getAll()
	{
		return $this->vars;
	}
	function getAllChecked()
	{
		return $this->checked_vars;
	}
	function __get($var_name)
	{
		if(isset($this->checked_vars[$var_name]))
			return $this->checked_vars[$var_name];
		return null;
	}
	function __isset($var_name)
	{
		return isset($this->checked_vars[$var_name]);
	}
	function bindFilter($var_name,$type)
	{
		if(!isset($this->checked_vars[$var_name])) return;
		$this->checked_vars[$var_name] = Filter::filter($this->checked_vars[$var_name],Filter::getFilter($type));
	}
	function isEmpty()
	{
		return empty($this->checked_vars);
	}
}
?>
