<?php
class Filter
{
	const NONE = 0;
	const INT = 1;
	const UINT = 2;
	const FLOAT = 3;
	const ARR = 4;
	const ARRAY_INT = 5;
	const ARRAY_STRING_QUOTE = 6;
	const ARRAY_STRING_ENCODE = 7;
	const ARRAY_STRING_QUOTE_ENCODE = 8;
	const STRING_QUOTE = 9;
	const STRING_ENCODE = 10;
	const STRING_QUOTE_ENCODE = 11;

	private static $quote_original = array('"','\'','`','\\');
	private static $quote_replacement = array('&quot;','&#039;','&#096;','&#092;');

	private static $encode_original = array('&','<','>');
	private static $encode_replacement = array('&amp;','&lt;','&gt;');

	function __construct(){}	
	static function getFilter($type)
	{
		$c = new ReflectionClass('Filter');
		$c = $c->getConstants();
		$type = strtoupper($type);
		if(isset($c[$type])) return $c[$type];
		return self::NONE;
	}
	static function filter($var = null,$type)
	{
		$ret = null;
		if(!isset($var)) return null;
		switch($type)
		{
			case self::NONE:
				$ret = $var;
				break;
			case self::INT:
				if(!is_int($var)) break;
				$ret = (int)$var;
				if($ret < -PHP_INT_MAX || $ret > PHP_INT_MAX)
					$ret = null;
				break;
			case self::UINT:
				if(!is_int($var)) break;
				$ret = (int)$var;
				if($ret < 0 || $ret > 2*PHP_INT_MAX)
					$ret = null;
				break;
			case self::FLOAT:
				$ret = (float)$var;
				break;
			case self::ARR:
				if(is_array($var) && !empty($var))
					$ret = $var;
				break;
			case self::ARRAY_INT:
				if(is_array($var) && !empty($var))
				{
					$ret = $var;
					foreach($ret as $k => $v)
						if($v !== self::filter($v,self::INT))
							unset($ret[$k]);
				}
				break;
			case self::ARRAY_STRING_QUOTE:
				if(!is_array($var) || empty($var)) break;
				$ret = $var;
				foreach($ret as $k => &$v)
					if(!is_string($v))
						unset($ret[$k]);
					else $v = self::quote($v);
				break;
			case self::ARRAY_STRING_ENCODE:
				if(!is_array($var) || empty($var)) break;
				$ret = $var;
				foreach($ret as $k => &$v)
					if(!is_string($v))
						unset($ret[$k]);
					else $v = self::encode($v);
				break;
			case self::ARRAY_STRING_QUOTE_ENCODE:
				if(!is_array($var) || empty($var)) break;
				$ret = $var;
				foreach($ret as $k => &$v)
					if(!is_string($v))
						unset($ret[$k]);
					else $v = self::quote(self::encode($v));
				break;
			case self::STRING_QUOTE:
				if(!is_string($var)) break;
				$ret = self::quote($var);
				break;
			case self::STRING_ENCODE:
				if(!is_string($var)) break;
				$ret = self::encode($var);
				break;
			case self::STRING_QUOTE_ENCODE:
				if(!is_string($var)) break;
				$ret = self::quote(self::encode($var));
				break;
		}
		return $ret;
	}
	static function quote($var)
	{
		return str_replace(self::$quote_original,self::$quote_replacement,trim($var));
	}
	static function encode($var)
	{
		return str_replace(self::$encode_original,self::$encode_replacement,trim($var));
	}
}

?>
