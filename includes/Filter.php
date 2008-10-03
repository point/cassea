<?php
/*- vim:expandtab:shiftwidth=4:tabstop=4: 
{{{ LICENSE  
* Copyright (c) 2008, Cassea Project
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
		if(!is_int($type))
			$type = self::getFilter($type);
		switch($type)
		{
			case self::NONE:
				$ret = $var;
				break;
			case self::INT:
				if(!is_numeric($var)) break;
				$ret = (int)$var;
				if($ret < -PHP_INT_MAX || $ret > PHP_INT_MAX)
					$ret = null;
				break;
			case self::UINT:
				if(!is_numeric($var)) break;
				$ret = (int)$var;
				if($ret < 0)
					$ret = null;
				break;
			case self::FLOAT:
				if(!is_numeric($var)) break;
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
					foreach($ret as $k => &$v)
						$v = self::filter($v,self::INT);
				}
				break;
			case self::ARRAY_STRING_QUOTE:
				if(!is_array($var) || empty($var)) break;
				$ret = $var;
				foreach($ret as $k => &$v)
					 $v = self::quote($v);
				break;
			case self::ARRAY_STRING_ENCODE:
				if(!is_array($var) || empty($var)) break;
				$ret = $var;
				foreach($ret as $k => &$v)
					 $v = self::encode($v);
				break;
			case self::ARRAY_STRING_QUOTE_ENCODE:
				if(!is_array($var) || empty($var)) break;
				$ret = $var;
				foreach($ret as $k => &$v)
					 $v = self::quote(self::encode($v));
				break;
			case self::STRING_QUOTE:
				if(!is_string((string)$var)) break;
				$ret = self::quote((string)$var);
				break;
			case self::STRING_ENCODE:
				if(!is_string((string)$var)) break;
				$ret = self::encode((string)$var);
				break;
			case self::STRING_QUOTE_ENCODE:
				if(!is_string((string)$var)) break;
				$ret = self::quote(self::encode((string)$var));
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
