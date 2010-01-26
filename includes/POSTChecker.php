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
 * This file contains class for checking incooming POST data upon given rules
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id:$
 * @package system
 * @since 
 */

//{{{ POSTChecker
/**
 * Checks POST data with the passed rules and sets error message, if this data
 * doesn't validate.
 */
class POSTChecker
{
	/**
	 * Regexp for checking email
	 * @var string
	 */
	static $email_regexp = "/[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+(?:[A-Z]{2}|com|org|net|gov|mil|biz|info|mobi|name|aero|jobs|museum)/i";
	/**
	 * Regexp for checking URL
	 * @var string
	 */
	static $url_regexp = "/^(((https?):\/\/)?(?:([a-zA-Z\d\-_]+)@?([a-zA-Z\d\-_]+)\:)?((?:(?:(?:(?:[a-zA-Z\d](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?)\.)*([a-zA-Z](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?))|(?:(?:\d+)(?:\.(?:\d+)){3}))(?::(\d+))?)(?:\/((?:(?:(?:[a-zA-Z\d$\-_.+!*'(),~]|(?:%[a-fA-F\d]{2}))|[;:@&=])*)(?:\/(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),~]|(?:%[a-fA-F\d]{2}))|[;:@&=])*))*)(\?(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),~]|(?:%[a-fA-F\d]{2}))|[;:@&=])*))?)?)$/i";
	/**
	 * Regexp for checking date in ISO representation
	 * @var string
	 */
	static $date_iso = "/^\d{4}[\/-]\d{1,2}[\/-]\d{1,2}$/";
	/**
	 * Regexp for checking for a digits
	 * @var string
	 */
	static $digits = "/^\d+$/";

	//{{{ checkByRules
	/**
	 * Performs verification of specified post data. 
	 * Usually calls from Controller, when POST data arrives.
	 *
	 * There is such rules:
	 * <ul>
	 * <li>required. Fires, that this filed must be filled.</li>
	 * <li>minlength. Sets minimum length of the string for this field.</li>
	 * <li>maxlength. Sets maximum length of the string for this field.</li>
	 * <li>rangelength. Sets minimum and maximum length of the string for this field (in [4,6] format).</li>
	 * <li>min. Sets minimum value of the field.</li>
	 * <li>max. Sets maximum value of the field.</li>
	 * <li>range. Sets minimum and maximum value of the field (in [10,20] format).</li>
	 * <li>email. Fires that this field must be a valid email. $email_regexp is used.</li>
	 * <li>url. Fires that this filed must be a valid URL. $url_regexp is used.</li>
	 * <li>date. Fires that this filed must be a valid date to be parsed by strtotime().</li>
	 * <li>dateISO. Fires that this filed must be a valid date in ISO format.$date_iso regexp is used.</li>
	 * <li>number. Fires that this filed must be a valid number (float, integer).</li>
	 * <li>digits. Fires that this filed must consists only of the digits.No other symbols are allowed.</li>
	 * </ul>
	 *
	 * These rules are compatible with jQuery validation plugin, which is used for client-side form 
	 * validation.
	 *
	 * If no custom messages defined, built-in are used.
	 *
	 * @param HTTPParamHolder object with post data to check
	 * @param string signature of the form that comes 
	 * @param array array of rules
	 * @param array optional array of custom messages
	 * @return null
	 */
	static function checkByRules(HTTPParamHolder $post, $formid_name, $rules, $messages=array())
	{
		if(empty($rules) || empty($rules[$formid_name])) return;
        $rules = $rules[$formid_name];
		if(isset($messages[$formid_name]))
			POSTErrors::setCustomMessages($messages[$formid_name]);
		foreach($rules as $name => $cr)
		{
			if(isset($cr['filter']))
				$post->bindFilter($name,$cr['filter']);
			$p_val = $post->{$name};
            if($p_val === "") $p_val = null;

			foreach($cr as $rule=>$rule_value)
            {
				if($rule === 'required' && $rule_value === 'true')
					if(!isset($p_val) || $p_val === null || $p_val === "")
						POSTErrors::addError($name,null,Language::message('checkers','REQUIRED'));
					elseif(is_array($p_val))
						foreach($p_val as $add_id => $p_val2)
							if(is_string($p_val2) && $p_val2 == "" || $p_val2 === null)
								POSTErrors::addError($name,$add_id,Language::message('checkers','REQUIRED'));
							elseif(is_array($p_val2) && count($p_val2) == 1 && ($p_val2[0] === null || $p_val2[0] === ""))
                                POSTErrors::addError($name,$add_id,Language::message('checkers','REQUIRED'));
					elseif(!t(new UploadedFiles($name))->isUploaded($name))
						POSTErrors::addError($name,$add_id,Language::message('checkers','REQUIRED'));

				if($rule === 'minlength' && is_numeric($rule_value) && isset($p_val))
					if(is_string($p_val) && strlen(htmlspecialchars_decode($p_val,ENT_QUOTES)) < 0+$rule_value)
						POSTErrors::addError($name,null,Language::message('checkers','MINLENGTH',$rule_value));
					elseif(is_array($p_val))
						foreach($p_val as $add_id => $p_val2)
							if(is_string($p_val2) && strlen(htmlspecialchars_decode($p_val2,ENT_QUOTES)) < 0+$rule_value || !isset($p_val2))
								POSTErrors::addError($name,$add_id,Language::message('checkers','MINLENGTH',$rule_value));
							elseif(is_array($p_val2) && count($p_val2) == 1 && strlen(htmlspecialchars_decode($p_val2[0],ENT_QUOTES)) < 0+$rule_value)
								POSTErrors::addError($name,$add_id,Language::message('checkers','MINLENGTH',$rule_value));

				if($rule === 'maxlength' && is_numeric($rule_value) && isset($p_val))
					if(is_string($p_val) && strlen(htmlspecialchars_decode($p_val,ENT_QUOTES)) > 0+$rule_value)
						POSTErrors::addError($name,null,Language::message('checkers','MAXLENGTH',$rule_value));
					elseif(is_array($p_val))
						foreach($p_val as $add_id => $p_val2)
							if(is_string($p_val2) && strlen(htmlspecialchars_decode($p_val2,ENT_QUOTES)) > 0+$rule_value || !isset($p_val2))
								POSTErrors::addError($name,$add_id,Language::message('checkers','MAXLENGTH',$rule_value));
							elseif(is_array($p_val2) && count($p_val2) == 1 && strlen(htmlspecialchars_decode($p_val2[0],ENT_QUOTES)) > 0+$rule_value)
								POSTErrors::addError($name,$add_id,Language::message('checkers','MAXLENGTH',$rule_value));

				if($rule === 'rangelength' && !empty($rule_value) && isset($p_val))
					if(preg_match("/\[\s*(\d+)\s*,\s*(\d+)\s*\]/",$rule_value,$m))
					{
						$range_from = $m[1];$range_to = $m[2];
						if(is_string($p_val) && (($len = strlen(htmlspecialchars_decode($p_val,ENT_QUOTES))) > 0+$rule_to || 
							$len < 0+$range_from))
							POSTErrors::addError($name,null,Language::message('checkers','RANGELENGTH',$range_from,$range_to));
						elseif(is_array($p_val))
							foreach($p_val as $add_id => $p_val2)
								if(is_string($p_val2) && (($len = strlen(htmlspecialchars_decode($p_val2,ENT_QUOTES))) > 0+$range_to || 
									$len < 0+$range_from) || !isset($p_val2))
										POSTErrors::addError($name,$add_id,Language::message('checkers','RANGELENGTH',$range_from,$range_to));
								elseif(is_array($p_val2) && count($p_val2) == 1 && (($len = strlen(htmlspecialchars_decode($p_val2[0],ENT_QUOTES))) > 0+$rule_to || 
									$len < 0+$range_from))
										POSTErrors::addError($name,$add_id,Language::message('checkers','RANGELENGTH',$range_from,$range_to));
					}

				if($rule === 'min'  && is_numeric($rule_value) && isset($p_val))
					if(is_numeric($p_val) && $p_val < 0+$rule_value)
						POSTErrors::addError($name,null,Language::message('checkers','MIN',$rule_value));
					elseif(is_array($p_val))
						foreach($p_val as $add_id => $p_val2)
							if(is_numeric($p_val2) && $p_val2 < 0+$rule_value || !isset($p_val2))
								POSTErrors::addError($name,$add_id,Language::message('checkers','MIN',$rule_value));
							elseif(is_array($p_val2) && count($p_val2) == 1 && is_numeric($p_val2[0]) && $p_val2[0] < 0+$rule_value)
								POSTErrors::addError($name,$add_id,Language::message('checkers','MIN',$rule_value));

				
				if($rule === 'max' && is_numeric($rule_value) && isset($p_val))
					if(is_numeric($p_val) && $p_val > 0+$rule_value)
						POSTErrors::addError($name,null,Language::message('checkers','MAX',$rule_value));
					elseif(is_array($p_val))
						foreach($p_val as $add_id => $p_val2)
							if(is_numeric($p_val2) && $p_val2 > 0+$rule_value || !isset($p_val2) || !isset($p_val2))
								POSTErrors::addError($name,$add_id,Language::message('checkers','MAX',$rule_value));
							elseif(is_array($p_val2) && count($p_val2) == 1 && is_numeric($p_val2[0]) && $p_val2[0] > 0+$rule_value)
								POSTErrors::addError($name,$add_id,Language::message('checkers','MAX',$rule_value));


				if($rule === 'range' && !empty($rule_value) && isset($p_val))
					if(preg_match("/\[\s*(\d+)\s*,\s*(\d+)\s*\]/",$rule_value,$m))
                    {
						$range_from = $m[1];$range_to = $m[2];
						if( is_numeric($p_val) && ($p_val > 0+$range_to || $p_val < 0+$range_from))
                        	POSTErrors::addError($name,null,Language::message('checkers','RANGE',$range_from,$range_to));
						elseif(is_array($p_val))
							foreach($p_val as $add_id => $p_val2)
								if(is_numeric($p_val2) && ($p_val2 > 0+$range_to || $p_val2 < 0+$range_from) || !isset($p_val2))
										POSTErrors::addError($name,$add_id,Language::message('checkers','RANGE',$range_from,$range_to));
								elseif(is_array($p_val2) && count($p_val2) == 1 && 
									is_numeric($p_val2[0]) && ($p_val2[0] > 0+$rule_to || $p_val2[0] < 0+$range_from))
										POSTErrors::addError($name,$add_id,Language::message('checkers','RANGE',$range_from,$range_to));
                    }

				if($rule === 'email' && $rule_value === 'true' && isset($p_val))
					if(is_string($p_val) && !preg_match(self::$email_regexp,$p_val))
						POSTErrors::addError($name,null,Language::message('checkers','EMAIL'));
					elseif(is_array($p_val))
						foreach($p_val as $add_id => $p_val2)
							if(is_string($p_val2) && !preg_match(self::$email_regexp,$p_val2) || !isset($p_val2))
								POSTErrors::addError($name,$add_id,Language::message('checkers','EMAIL'));
							elseif(is_array($p_val2) && count($p_val2) == 1 && !preg_match(self::$email_regexp,$p_val2[0]))
								POSTErrors::addError($name,$add_id,Language::message('checkers','EMAIL'));

				if($rule === 'url' && $rule_value === 'true' && isset($p_val))
					if(is_string($p_val) && !preg_match(self::$url_regexp,$p_val))
						POSTErrors::addError($name,null,Language::message('checkers','URL'));
					elseif(is_array($p_val))
						foreach($p_val as $add_id => $p_val2)
							if(is_string($p_val2) && !preg_match(self::$url_regexp,$p_val2) || !isset($p_val2))
								POSTErrors::addError($name,$add_id,Language::message('checkers','URL'));
							elseif(is_array($p_val2) && count($p_val2) == 1 && !preg_match(self::$url_regexp,$p_val2[0]))
								POSTErrors::addError($name,$add_id,Language::message('checkers','URL'));
					
				if($rule === 'date' && $rule_value === 'true' && isset($p_val))
					if(is_string($p_val) && strtotime($p_val) === -1)
						POSTErrors::addError($name,null,Language::message('checkers','DATE'));
					elseif(is_array($p_val))
						foreach($p_val as $add_id => $p_val2)
							if(is_string($p_val2) && strtotime($p_val2) === -1 || !isset($p_val2))
								POSTErrors::addError($name,$add_id,Language::message('checkers','DATE'));
							elseif(is_array($p_val2) && count($p_val2) == 1 && strtotime($p_val2[0]) === -1)
								POSTErrors::addError($name,$add_id,Language::message('checkers','DATE'));

				if($rule === 'dateISO' && $rule_value === 'true' && isset($p_val))
					if(is_string($p_val) && !preg_match(self::$date_iso,$p_val))
						POSTErrors::addError($name,null,Language::message('checkers','DATEISO'));
					elseif(is_array($p_val))
						foreach($p_val as $add_id => $p_val2)
							if(is_string($p_val2) && !preg_match(self::$date_iso,$p_val2) || !isset($p_val2))
								POSTErrors::addError($name,$add_id,Language::message('checkers','DATEISO'));
							elseif(is_array($p_val2) && count($p_val2) == 1 && !preg_match(self::$date_iso,$p_val2[0]))
								POSTErrors::addError($name,$add_id,Language::message('checkers','DATEISO'));

				if($rule === 'number' && $rule_value === 'true' && isset($p_val))
					if(is_string($p_val) && !is_numeric(str_replace(",",".",$p_val)))
						POSTErrors::addError($name,null,Language::message('checkers','NUMBER'));
					elseif(is_array($p_val))
						foreach($p_val as $add_id => $p_val2)
							if(is_string($p_val2) && !is_numeric(str_replace(",",".",$p_val2)) || !isset($p_val2))
								POSTErrors::addError($name,$add_id,Language::message('checkers','NUMBER'));
							elseif(is_array($p_val2) && count($p_val2) == 1 && !is_numeric(str_replace(",",".",$p_val2[0])))
								POSTErrors::addError($name,$add_id,Language::message('checkers','NUMBER'));

				if($rule === 'digits' && $rule_value === 'true' && isset($p_val))
					if(is_string($p_val) && !preg_match(self::$digits,$p_val))
						POSTErrors::addError($name,null,Language::message('checkers','DIGITS'));
					elseif(is_array($p_val))
						foreach($p_val as $add_id => $p_val2)
							if(is_string($p_val2) && !preg_match(self::$digits,$p_val2) || !isset($p_val2))
								POSTErrors::addError($name,$add_id,Language::message('checkers','DIGITS'));
							elseif(is_array($p_val2) && count($p_val2) == 1 && !preg_match(self::$digits,$p_val2[0]))
								POSTErrors::addError($name,$add_id,Language::message('checkers','DIGITS'));
			}
		}
	}
	//}}}
}
//}}}

