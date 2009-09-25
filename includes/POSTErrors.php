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

//{{{ POSTErrors
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
// }}}

