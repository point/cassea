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

class POSTChecker
{
	static $email_regexp = "/[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+(?:[A-Z]{2}|com|org|net|gov|mil|biz|info|mobi|name|aero|jobs|museum)/i";
	static $url_regexp = "/^(((https?):\/\/)?(?:([a-zA-Z\d\-_]+)@?([a-zA-Z\d\-_]+)\:)?((?:(?:(?:(?:[a-zA-Z\d](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?)\.)*([a-zA-Z](?:(?:[a-zA-Z\d]|-)*[a-zA-Z\d])?))|(?:(?:\d+)(?:\.(?:\d+)){3}))(?::(\d+))?)(?:\/((?:(?:(?:[a-zA-Z\d$\-_.+!*'(),~]|(?:%[a-fA-F\d]{2}))|[;:@&=])*)(?:\/(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),~]|(?:%[a-fA-F\d]{2}))|[;:@&=])*))*)(\?(?:(?:(?:[a-zA-Z\d$\-_.+!*'(),~]|(?:%[a-fA-F\d]{2}))|[;:@&=])*))?)?)$/i";
	static $date_iso = "/^\d{4}[\/-]\d{1,2}[\/-]\d{1,2}$/";
	static $digits = "/^\d+$/";
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
					if(!isset($p_val) ||
                        (/*is_string($p_val) &&*/ ($p_val === null || $p_val === "")))
						POSTErrors::addError($name,null,Language::message('checkers','REQUIRED'));
					elseif(is_array($p_val))
						foreach($p_val as $add_id => $p_val2)
							if(is_string($p_val2) && $p_val2 == "" || $p_val2 === null)
								POSTErrors::addError($name,$add_id,Language::message('checkers','REQUIRED'));
							elseif(is_array($p_val2) && count($p_val2) == 1 && ($p_val2[0] === null || $p_val2[0] === ""))
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
}
class POSTErrors
{

	private static $errors = array();
	private static $post_data = array();
    private static $custom_messages = array();

    static function setCustomMessages($messages){
        self::$custom_messages = $messages;
    }

	static function addError($widget_name, $additional_id,$message)
	{
		self::$errors[$widget_name][] = array('additional_id'=>$additional_id,'message'=> isset(self::$custom_messages[$widget_name])?self::$custom_messages[$widget_name]:$message);
		if(!isset(self::$post_data[$widget_name]))
			self::$post_data[$widget_name] = Controller::getInstance()->post->{$widget_name};
	}
	static function hasErrors()
	{
		return !empty(self::$errors);
	}
	static function saveErrorList()
	{
		self::savePostData();
		$storage = Storage::createWithSession("post_errors",300);
		$storage->set('errors',self::$errors);
		$storage->set('data',self::$post_data);
	}
	static function restoreErrorList()
	{
		$storage = Storage::createWithSession("post_errors");
		self::$errors = $storage->get('errors');
		self::$post_data = $storage->get('data');
	}
	static function flushErrors()
	{
		$storage = Storage::createWithSession("post_errors");
		$storage->un_set('errors');
		$storage->un_set('data');
		self::$errors = array();
		self::$post_data = array();
	}
	static function getErrorFor($name,$additional_id = null)
	{
		if(!isset(self::$errors[$name])) return null;

		$e = self::$errors[$name];
		$e2 = array();
		if(!isset($additional_id))
			foreach($e as $v)
				$e2[] = $v['message'];
		else 
			foreach($e as $v)
				if($v['additional_id'] == $additional_id)
					$e2[] = $v['message'];
		
		if(empty($e2)) return null;
        //unset(self::$errors[$name]);
		return $e2;
	}
	static function getPOSTData($name,$additional_id = null)
	{
		if(!isset(self::$post_data[$name])) return null;

		if(isset($additional_id))
			if(isset(self::$post_data[$name][$additional_id]))
			{
				$p = self::$post_data[$name][$additional_id];
				if(!is_array($p))
				{
					unset(self::$post_data[$name][$additional_id]);
					return $p;
				}
				else
					return array_shift(self::$post_data[$name][$additional_id]);
			}
			else return null;
		else
			return is_scalar(self::$post_data[$name])?self::$post_data[$name]:null;
	
	}
	static function savePostData()
	{
		$post = Controller::getInstance()->post;
		if(!isset($post)) return;
		foreach($post as $name => &$v)
			if(!isset(self::$post_data[$name]))
				self::$post_data[$name] = $v;
	}
}

//{{{ CheckerException
class CheckerException extends CasseaException
{
	protected 
			$widget_name = null,
			$additional_id = null
			;
	function __construct($message = null, $widget_name = null,$additional_id = null)
	{
		parent::__construct($message,1);
		$this->widget_name = $widget_name;
		$this->additional_id = $additional_id;
	}
	function getWidgetName()
	{
		return $this->widget_name;
	}
	function getAdditionalId()
	{
		return $this->additional_id;
	}
}
//}}}


?>
