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

/* Some code parts based on CodeIgnitier 
 * author	ExpressionEngine Dev Team
 * link		http://codeigniter.com/user_guide/libraries/input.html
*/

class FilterException extends Exception {}

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
	const STRING_QUOTE_ENCODE_PARTIALY = 12;
	const DOUBLE = 13;
	const ARRAY_DOUBLE = 14;
	const ARRAY_FLOAT = 15;
	const ARRAY_INT_KEYS = 16;

	private static $quote_original = array('"','\'','`','\\');
	private static $quote_replacement = array('&quot;','&#039;','&#096;','&#092;');

	private static $encode_original = array('&','<','>');
    private static $encode_replacement = array('&amp;','&lt;','&gt;');

    private static $allowed_tags = array('br', 'i', 'b', 'h1');

	function __construct(){}	
	static function getFilter($type)
	{
        if(is_int($type)) return $type;
		$c = new ReflectionClass('Filter');
		$c = $c->getConstants();
		$type = strtoupper($type);
		if(isset($c[$type])) return $c[$type];
        throw new FilterException('Filter '.$type.' doesn\'t exists');
		//return self::NONE;
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
			case self::DOUBLE:
				if(!is_numeric($var)) break;
				$ret = (double)$var;
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
                    $ret = array_filter($ret,create_function('$v','return $v !== null;'));
				}
				break;
			case self::ARRAY_FLOAT:
				if(is_array($var) && !empty($var))
				{
					$ret = $var;
					foreach($ret as $k => &$v)
						$v = self::filter($v,self::FLOAT);
                    $ret = array_filter($ret,create_function('$v','return $v !== null;'));
				}
				break;
			case self::ARRAY_DOUBLE:
				if(is_array($var) && !empty($var))
				{
					$ret = $var;
					foreach($ret as $k => &$v)
						$v = self::filter($v,self::DOUBLE);
                    $ret = array_filter($ret,create_function('$v','return $v !== null;'));
				}
				break;
			case self::ARRAY_STRING_QUOTE:
				if(!is_array($var) || empty($var)) break;
				$ret = $var;
				foreach($ret as $k => &$v)
					 $v = self::quote($v);
                $ret = array_filter($ret,create_function('$v','return $v !== null;'));
				break;
			case self::ARRAY_STRING_ENCODE:
				if(!is_array($var) || empty($var)) break;
				$ret = $var;
				foreach($ret as $k => &$v)
					 $v = self::encode($v);
                $ret = array_filter($ret,create_function('$v','return $v !== null;'));
				break;
			case self::ARRAY_STRING_QUOTE_ENCODE:
				if(!is_array($var) || empty($var)) break;
				$ret = $var;
				foreach($ret as $k => &$v)
					 $v = self::quote(self::encode($v));
                $ret = array_filter($ret,create_function('$v','return $v !== null;'));
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
			case self::STRING_QUOTE_ENCODE_PARTIALY:
				if(!is_string((string)$var)) break;
                $ret = self::quote(self::encode((string)$var));

                for ($i = 0, $c = count(self::$allowed_tags); $i < $c ; $i++){
                    $tag = self::$allowed_tags[$i];
                    $ret = preg_replace('#&lt;(/?)'.self::$allowed_tags[$i].'(/?)&gt;#','<$1'.self::$allowed_tags[$i].'$2>', $ret );
                    //str_replace('&lt;'.self::$allowed_tags[$i].'&gt', '<'.self::$allowed_tags[$i].'>', $res);

				}
				break;
			case self::ARRAY_INT_KEYS:
				if(!is_array($var)) return;
				$ret0 = array();
				foreach(self::filter(array_keys($var), self::ARRAY_INT) as $k)
					$ret0[$k] = $var[$k];
				if(!empty($ret0)) $ret = $ret0;
				unset($ret0);
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
    static function sanitizeVars($str)
    {
		/*
		* Remove Invisible Characters
		*/
        static $non_displayables = array(
            '/%0[0-8bcef]/',			// url encoded 00-08, 11, 12, 14, 15
            '/%1[0-9a-f]/',				// url encoded 16-31
            '/[\x00-\x08]/',			// 00-08
            '/\x0b/', '/\x0c/',			// 11, 12
            '/[\x0e-\x1f]/'				// 14-31
        );

        $str = preg_replace($non_displayables,array_fill(0,count($non_displayables),''),$str);

		/*
		* Validate standard character entities
		*
		* Add a semicolon if missing.  We do this to enable
		* the conversion of entities to ASCII later.
		*
		*/
		$str = preg_replace('#(&\#?[0-9a-z]{2,})([\x00-\x20])*;?#Ui', "\\1;\\2", $str);
        /*
         * Furthermore numeric entities don't need a trailing semicolon (very stupid, IMHO) 
         * to be recognized by browsers. 
        */
		$str = preg_replace('#(&\#x?)([0-9A-F]+);?#Ui',"\\1\\2;",$str);
        
		/*
		* URL Decode
		*
		* Just in case stuff like this is submitted:
		*
		* <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
		*
		* Note: Use rawurldecode() so it does not remove plus signs
		*
		*/
		//$str = rawurldecode($str);


        //NOTE: no html_entity_decode was made !
        

		/*
		* Not Allowed Under Any Conditions
		*/
        static $never_allowed_str = array(
                                        'document.cookie'	,
                                        'document.write'	,
                                        '.parentnode'		,
                                        '.innerhtml'		,
                                        'window.location'	,
                                        '-moz-binding'		
                                        );
        static $never_allowed_regex = array(
                                            "/javascript\s*:/i"			,
                                            "/expression\s*(\(|&\#40;)/i"	, // css and ie
                                            "/vbscript\s*:/i"				, // ie, surprise!
											"/redirect\s+302/i",
											"/<script[^>]*>/",
											"/<\/script[^>]*>/"
                                        );
        
        $str = str_ireplace($never_allowed_str,'[removed]',$str);
        $str = preg_replace($never_allowed_regex,array_fill(0,count($never_allowed_regex),'[removed]'),$str);

		/*
		* Compact any exploded words
		*
		* This corrects words like:  j a v a s c r i p t
		* These words are compacted back to their correct state.
		*
		*/

        $words = array('/(j)\s*(a)\s*(v)\s*(a)\s*(s)\s*(c)\s*(r)\s*(i)\s*(p)\s*(t)/is'
            ,'/(e)\s*(x)\s*(p)\s*(r)\s*(e)\s*(s)\s*(s)\s*(i)\s*(o)\s*(n)/is'
            ,'/(v)\s*(b)\s*(s)\s*(c)\s*(r)\s*(i)\s*(p)\s*(t)/is'
            ,'/(s)\s*(c)\s*(r)\s*(i)\s*(p)\s*(t)/is'
            ,'/(a)\s*(p)\s*(p)\s*(l)\s*(e)\s*(t)/is'
            ,'/(a)\s*(l)\s*(e)\s*(r)\s*(t)/is'
            ,'/(d)\s*(o)\s*(c)\s*(u)\s*(m)\s*(e)\s*(n)\s*(t)/is'
            ,'/(w)\s*(r)\s*(i)\s*(t)\s*(e)/is'
            ,'/(c)\s*(o)\s*(o)\s*(k)\s*(i)\s*(e)/is'
            ,'/(w)\s*(i)\s*(n)\s*(d)\s*(o)\s*(w)/is'
        );

        $str = preg_replace_callback($words,
            create_function('$matches','return implode("",array_slice($matches,1));'),$str);

		/*
		* Remove disallowed Javascript in links or img tags
        */

        if(stripos($str,"<a") !== false)
            $str = preg_replace_callback("/(<a\s+href=)([^>]*)(>)/si", 
				create_function('$matches','
return $matches[1].str_ireplace(
                    array(
                        "alert(",
                        "alert&\#40;",
                        "javascript:",
                        "charset=",
                        "window.",
                        "document.",
                        ".cookie",
                        "<script",
                        "<xss",
						"base64"),
						"",$matches[2]).$matches[3];
				'),$str);

        if(stripos($str,"<img") !== false)
            $str = preg_replace_callback("/(<img\s+src=)([^>]*)(>)/si", 
				create_function('$matches','
return $matches[1].str_ireplace(
                    array(
                        "alert(",
                        "alert&\#40;",
                        "javascript:",
                        "charset=",
                        "window.",
                        "document.",
                        ".cookie",
                        "<script",
                        "<xss",
						"base64"),
						"",$matches[2]).$matches[3];
				'),$str);

		/*
		* Remove JavaScript Event Handlers
		*
		* Note: This code is a little blunt.  It removes
		* the event handler and anything up to the closing >,
		* but it's unlikely to be a problem.
		*
		*/
		$str = preg_replace("#([^><]+?)([^a-z_\-]on\w*|xmlns)(\s*=\s*[^><]*)([><]*)#i", "<\\1\\4", $str);


		// Standardize newlines
		if (strpos($str, "\r") !== false)
			$str = str_replace(array("\r\n", "\r"), "\n", $str);
		return $str;
    }
}


