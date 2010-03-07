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
 * This file contains class for managing error messages, occurred
 * while checking POST data.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id:$
 * @package system
 * @since 
 */

//{{{ POSTErrors
/**
 * Handle error messages for control widgets, data from which wasn't validated.
 * If custom messages are specified, they will be used instead of system default.
 */
class POSTErrors
{

	/**
	 * Array of error messages for each widget
	 * @var array
	 */
	private static $errors = array();
	/**
	 * Holds POST data for the widget with error in validating.
	 * @var array 
	 */
	private static $post_data = array();
	/**
	 * Array of custom messages for built-in validators.
	 * @var array
	 */
    private static $custom_messages = array();

	//{{{ setCustomMessages
	/**
	 * Set custom messages to display if POST data wasn't validated 
	 * by the valuecheker(s).
	 *
	 * Primarily for internal use.
	 *
	 * @param array array of messages per widget
	 * @return null
	 */
    static function setCustomMessages($messages){
        self::$custom_messages = $messages;
    }
	//}}}

	//{{{ addError
	/**
	 * Adds error message to the specified widget.
	 * Widget is defined by the name and optional additional_id which
	 * appear if particular widget consist as a part of WRoll widget.
	 *
	 * All POST data for the widget with error will be saved for 
	 * further restoring of non validated values.
	 * 
	 * @param string name property of the widget
	 * @param string additional_id in case of WRoll
	 * @param string message to be shown. If custom message was early defined,
	 * this parameter will be ignored.
	 */
	static function addError($widget_name, $additional_id,$message)
	{
		self::$errors[$widget_name][] = array('additional_id'=>$additional_id,'message'=> isset(self::$custom_messages[$widget_name])?self::$custom_messages[$widget_name]:$message);
		if(!isset(self::$post_data[$widget_name]))
			self::$post_data[$widget_name] = Controller::getInstance()->post->{$widget_name};
	}
	//}}}

	//{{{ hasErrors
	/**
	 * Returns true if there is non validated widget.
	 *
	 * @param null
	 * @return bool
	 */
	static function hasErrors()
	{
		return !empty(self::$errors);
	}
	//}}}

	//{{{ saveErrorList
	/**
	 * Saves errors and data for non validated widgets in persistent storage in 
	 * order to restore while the next GET request with the same URL.
	 *
	 * Primarily for internal use.
	 *
	 * @param null
	 * @return null
	 */
	static function saveErrorList()
	{
		self::savePOSTData();
		$storage = Storage::createWithSession("post_errors",300);
		$storage->set('errors',self::$errors);
		$storage->set('data',self::$post_data);
	}
	//}}}

	//{{{ restoreErrorList
	/**
	 * Restores previously saved error list and data array for non validated 
	 * widgets. 
	 *
	 * Primarily for internal use.
	 *
	 * @param null
	 * @return null
	 */
	static function restoreErrorList()
	{
		$storage = Storage::createWithSession("post_errors");
		self::$errors = $storage->get('errors');
		self::$post_data = $storage->get('data');
	}
	//}}}

	//{{{ flushErrors
	/**
	 * Resets array of errors and appropriate data.
	 * Additionally persistent storage will be cleaned 
	 * too.
	 *
	 * @param null
	 * @return null
	 */
	static function flushErrors()
	{
		$storage = Storage::createWithSession("post_errors");
		$storage->un_set('errors');
		$storage->un_set('data');
		self::$errors = array();
		self::$post_data = array();
	}
	//}}}

	//{{{ getErrorFor
	/**
	 * Returns error for specified widget.
	 * Usually called from WControl to retrieve error message
	 * that should be displayed on the page.
	 *
	 * As of {@link addError} widget is defined by the name
	 * and additional_id.
	 *
	 * @param string name of the widget
	 * @param string additional id for this widget
	 * @param array with the messages or null if messages wasn't defined.
	 */
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
	//}}}

	//{{{ getPOSTData
	/**
	 * Returns POST data, passed by the submited form.
	 * It used for restoring inputed value to edit.
	 *
	 * As of {@link addError} widget is defined by the name
	 * and additional_id.
	 * 
	 * @param string name of the widget
	 * @param string additional id for this widget
	 * @param string value of the specified filed or null if 
	 * nothing was found.
	 */
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
	//}}}

	//{{{ savePOSTData
	/**
	 * Saves POST data to further restoring and accessing to it 
	 * via {@link getPOSTData} method.
	 *
	 * Primarily for internal use.
	 *
	 * @param null
	 * @return null
	 */
	static function savePOSTData()
	{
		$post = Controller::getInstance()->post;
		if(!isset($post)) return;
		foreach($post as $name => &$v)
			if(!isset(self::$post_data[$name]))
				self::$post_data[$name] = $v;
	}
	//}}}
}
//}}}

