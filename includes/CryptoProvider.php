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

//{{{ CryptoProvider

class CryptoProviderException extends CasseaException {}

class CryptoProvider extends EventBehaviour
{
	const SECRET_MIN_LENGHT = 30;

	function __construct()
	{
		parent::__construct();
		if(!function_exists("hash"))
			throw new CryptoProviderException("Function 'hash' could not be found");

		if(!function_exists('mcrypt_module_open'))
			throw new CryptoProviderException("Module mcrypt wasn't found");
	}
	
	//use CryptoProvider::delegate('CryptoProviderHash','CustomCryptoProvider::custom_hash');
	function hash($string, $method = null)
	{
		if($method === null || $method == ":default")
			$method = Config::getInstance()->crypto->hash;
		if(in_array($method,hash_algos()))
			return hash($method,$string);

		//using custom hash method
		$ret =  $this->trigger("CryptoProviderHash",array($string,$method));
		if(!is_string($ret))
			throw new CryptoProviderException("Return value of hash function must be a sting");
		if($ret == $string || $ret === null)
			throw new CryptoProviderException("No hash function was applied");
		return $ret;
	}

	private function checkMcrypt($algorithm, $secret)
	{
		if(!in_array($algorithm, mcrypt_list_algorithms()))
			throw new CryptoProviderException("Algorithm '$algorithm' doesn't support by mcrypt extension");
	
		if(strlen($secret) < self::SECRET_MIN_LENGHT)
			throw new CryptoProviderException("Secret passphrase must have more than {self::SECRET_MIN_LENGHT} characters. But '$secret' was given");

	}
	function encrypt($text, $algorithm = null, $secret = null)
	{
		$config = Config::getInstance();

		if(is_null($algorithm))
			$algorithm = $config->crypto->algo;
		if(is_null($secret))
			$secret = $config->crypto->secret;

		$this->checkMcrypt($algorithm,$secret);

		$td = mcrypt_module_open($algorithm, '', 'cfb', '');
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_URANDOM);
		mcrypt_generic_init($td, $secret, $iv);
		$crypt_text = mcrypt_generic($td, $text);
		mcrypt_generic_deinit($td);
		return base64_encode($iv)."--".base64_encode($crypt_text);
	}
	function decrypt($text,$algorithm = null, $secret = null)
	{

		$config = Config::getInstance();

		if(is_null($algorithm))
			$algorithm = $config->crypto->algo;
		if(is_null($secret))
			$secret = $config->crypto->secret;

		$this->checkMcrypt($algorithm,$secret);

		list($iv,$text) = explode("--",$text);

		if(empty($iv) || empty($text))
			throw new CryptoProviderException("Wrong encrypted text format");

		$iv = base64_decode($iv);
		$text = base64_decode($text);

		$td = mcrypt_module_open($algorithm, '', 'cfb', '');
		mcrypt_generic_init($td, $secret, $iv);
		$plain_text = mdecrypt_generic($td, $text);
		mcrypt_generic_deinit($td);

		return $plain_text;	  
	}
}
//}}}

