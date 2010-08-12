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
 * This file contains class for managing and sending cookie data.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id:$
 * @package system
 * @since 
 */

// cookie['name'] = value; //all default
// cookie->encrypted['name'] = array("value"=> )
// echo cookie->encrypted['name']
// cookie->encrypted->permanent['name'] =  "qwe";

class HTTPCookieException extends CasseaException {}

//{{{ HTTPCookieHolder 
class HTTPCookieHolder extends HTTPParamHolder implements ArrayAccess
{
	const MAX_COOKIE_SIZE = 4096; // 4K

	protected $to_send = array();
	protected $signed = null;
	protected $encrypted = null;
	protected $permanent = null;

	private $default_cookie_params = array();

	function __construct(array $vars,$allow_array = false)
	{
		parent::__construct($vars,$allow_array);

		$config = Config::getInstance();
		$this->default_cookie_params = array(
			"value"=>null,
			"expire"=>($config->session->cookie->length == 0 && !$config->session->remember_me)?0: 
				(time() + $config->session->cookie->length + ($config->session->remember_me?$config->session->remember_me_for:0)),
			
			"path"=>$config->cookie_path,
			"domain"=>null,
			"secure"=>false,
			"httponly"=>false);
	}
	public function offsetSet($offset, $value)
	{
		if(!is_array($value) && !is_scalar($value))
			throw new HTTPCookieException("Value should be either array or scalar value");

		if(!is_array($value))
			$value = array("value"=>$value); 
		
		$cookie_array = $value + $this->default_cookie_params;

		if(!is_scalar($cookie_array['value']))
			$cookie_array['value'] = serialize($cookie_array['value']); 
		if(strlen($cookie_array['value']) > self::MAX_COOKIE_SIZE)
			throw new HTTPCookieException("Exceeded maximum allowed size of cookie");

		$this->to_send[$offset] = $cookie_array;
	}
	public function offsetUnset($offset)
	{
		if($this->to_send[$offset])
			unset($this->to_send[$offset]);
		parent::__unset($offset);
	}

	function __get($var_name)
	{
		if($var_name == "permanent")
			return $this->permanent  = $this->permanent?$this->permanent:new PermanentCookieContainer($this);
		if($var_name == "encrypted")
			return $this->encrypted = $this->encrypted?$this->encrypted:new EncryptedCookieContainer($this);
		
		return parent::__get($var_name);
	}

	function send()
	{
		foreach($this->to_send as $v)
			setcookie($v['name'],$v['value'],$v['expire'],$v['path'],$v['domain'],$v['secure'],$v['httponly']);
	}

}
//}}}

abstract class CookieContainer extends HTTPCookieHolder
{
	protected $cookie_holder = null;
	function __construct(HTTPCookieHolder $cookie_holder)
	{
		$this->cookie_holder = $cookie_holder;
		parent::__construct(array());
	}
	function __get($var_name) { return $this->cookie_holder->$var_name; }
	public function offsetUnset($offset) { return $this->cookie_holder->offsetUnset($offset);}
}
class PermanentCookieContainer extends CookieContainer
{
	const TEN_YEARS = 315360000; // 10 years in seconds

	public function offsetSet($offset, $value)
	{
		if(!is_array($value) && !is_scalar($value))
			throw new HTTPCookieException("Value should be either array or scalar value");

		if(!is_array($value))
			$value = array("value"=>$value); 
		
		$value['expire'] = time() + self::TEN_YEARS;
		parent::offsetSet($offset,$value);
	}
}
class EncryptedCookieContainer extends CookieContainer
{
	public function offsetSet($offset, $value)
	{
		if(!is_array($value) && !is_scalar($value))
			throw new HTTPCookieException("Value should be either array or scalar value");

		if(!is_array($value))
			$value = array("value"=>$value); 
		if(!is_scalar($value['value']))
			$value['value'] = serialize($value['value']); 

		$cp = new CryptoProiver();
		$value['value'] = base64_encode($cp->encrypt($val));
		parent::offsetSet($offset,$value);
	}

	public function offsetGet($offset)
	{
		$raw = parent::__get($offset);

		$cp = new CryptoProiver();
		return $cp->decrypt(base64_decode($raw));
	}
}
