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
 * This file contains class for managing $_GET and $_POST data.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id:$
 * @package system
 * @since 
 */


/* This part is based on CodeIgnitier 
 * author	ExpressionEngine Dev Team
 * link		http://codeigniter.com/user_guide/libraries/input.html
*/
// Clean $_COOKIE Data
// Also get rid of specially treated cookies that might be set by a server
// or silly application, that are of no use to a CI application anyway
// but that when present will trip our 'Disallowed Key Characters' alarm
// http://www.ietf.org/rfc/rfc2109.txt
// note that the key names below are single quoted strings, and are not PHP variables
unset($_COOKIE['$Version']);
unset($_COOKIE['$Path']);
unset($_COOKIE['$Domain']);

//{{{ HTTPParamHolder
/**
 * This class designed to keep HTTP data, such as GET, POST or COOKIE.
 * 
 * It normalize incoming data: checks encoding (must be UTF8), sanitizing it
 * (using {@link Filter::sanitizeVars}). Optionally, it allows bind one of the
 * available filter for a single variable. Additionally, user has access to 
 * raw data (unchecked, unfiltered, etc) as it comes via standard php 
 * variables ($_GET, $_POST).
 *
 * Keep in mind, that if array as a value was given, it will sanitize recursively.
 *
 */
class HTTPParamHolder implements IteratorAggregate,ArrayAccess
{
	private 
		/**
		 * Keeps untouched variables
		 * @var array
		 */
		$vars = array(),
		/**
		 * Keeps checked, filtered, sanitized values
		 */
		$checked_vars = array();

	//{{{ __construct
	/**
	 * Creates new instance of this class. 
	 * It fills arrays with unchecked variables and 
	 * makes checks for right encoding of inputted variables.
	 *
	 * If arrays are allowed, they also will be checked recursively.
	 *
	 * Additionally, here all variables will be sanitized. Arrays will be 
	 * sanitized recursively.
	 *
	 * @param array variables to store
	 * @param bool whenever to allow arrays to store. Default is false.
	 * Setting it to true for $_POST data, and leaving for other types.
	 */
	function __construct(array $vars,$allow_array = false)
	{
		//if(!empty($this->vars) || !empty($this->checked_vars)) return;
		
		//setting up unchecked variables
		if(!empty($vars))
			$this->vars = $vars;

		foreach($vars as $k=>$v)
        {
            if($k{0} === "_" && $k{1} !== "_") continue;
			if((is_scalar($v) && mb_check_encoding($k,"UTF8") && mb_check_encoding($v,"UTF8"))||
				$allow_array && is_array($v) && mb_check_encoding($k,"UTF8") && $this->checkUTF8InArray($v))
                $this->checked_vars[$k] = $this->sanitizeVars($v);
        }
	}
	//}}}

	//{{{ getAll
	/** 
	 * Returns all unchecked data. Use outputted data carefully.
	 *
	 * @param null
	 * @return array of unchecked data
	 */
	function getAll()
	{
		return $this->vars;
	}
	//}}}

	//{{{ getAllChecked
	/**
	 * Returns all checked and filtered data
	 * 
	 * If you use it to iterate through held data, don't use this method.
	 * This class supports IteratorAggregate interface.
	 *
	 * @param null
	 * @return array of checked data
	 */
	function getAllChecked()
	{
		return $this->checked_vars;
	}
	//}}}

	//{{{ __get
	/**
	 * Magic method which returns checked value by the name.
	 *
	 * @param string the name of value to look up
	 * @return mixed stored value with given name
	 */
	function __get($var_name)
	{
		if(isset($this->checked_vars[$var_name]))
			return $this->checked_vars[$var_name];
		return null;
	}
	//}}}
	
	public function offsetGet($offset)
	{
		return $this->__get($offset);
	}

	//{{{ __isset
	/**
	 * Magic method which checks whenever value with given 
	 * name already exists.
	 *
	 * @param string the name of value to check
	 * @return bool true if searched value exists
	 */
	function __isset($var_name)
	{
		return isset($this->checked_vars[$var_name]);
	}
	//}}}
	
	public function offsetExists($offset) 
	{
		return $this->__isset($offset);
	}

	//{{{ cleanStrings
	/**
	 * Removes empty string from the array of checked values.
	 *
	 * String are compared strictly (===).
	 *
	 * @param null
	 * @return null
	 */
    function cleanStrings()
    {
        foreach($this->checked_vars as $k => $v)
            if($v === "") unset($this->checked_vars[$k]);
	}
	//}}}

	//{{{ bindFilter
	/**
	 * Binds filter to the value with given key. 
	 * All stored data, snapped to the key will be immediately filtered
	 * upon specified type of filter.
	 *
	 * @param string name of the value to filter
	 * @param mixed string or numeric, representing type of the filter
	 * @see Filter
	 */
	function bindFilter($var_name,$type)
	{
		if(!isset($this->checked_vars[$var_name])) return;
		$this->checked_vars[$var_name] = Filter::apply($this->checked_vars[$var_name],Filter::getFilter($type));
	}
	//}}}
	
	function bindRegexp($var_name,$regexp)
	{
		if(!isset($this->checked_vars[$var_name])) return;
		$this->checked_vars[$var_name] =  preg_match($regexp,$this->checked_vars[$var_name])?$this->checked_vars[$var_name]:null;
	}

	//{{{ isEmpty
	/**
	 * Used to check up if nothing is stored in the current object.
	 *
	 * @param null
	 * @return bool
	 */
	function isEmpty()
	{
		return empty($this->checked_vars);
	}
	//}}}

	//{{{ getIterator
	/**
	 * Returns iterator to allow foreach enumeration
	 *
	 * @param null
	 * @return Traversable iterator
	 */
	function getIterator()
	{
		return t(new ArrayObject($this->checked_vars))->getIterator();
	}
	//}}}

	//{{{ delete
	/**
	 * Removes value with given name from the list of checked
	 * data. Unchecked data will be leaved untouched.
	 *
	 * @param string name of the value to delete
	 * @return null
	 * @see __unset
	 */
	function delete($var_name)
	{
		if(!isset($this->checked_vars[$var_name])) return;
		unset($this->checked_vars[$var_name]);
	}
	//}}}

	//{{{ __unset
	/**
	 * Magic method, which works as {@link delete} method.
	 *
	 * @param string name of the value to delete
	 * @return null
	 * @see delete
	 */
	function __unset($var_name)
	{
		$this->delete($var_name);
	}
	//}}}

	public function offsetUnset($offset)
	{
		$this->__unset($offset);
	}
	//{{{ sanitizeVars
	/**
	 * Removes unwanted sequences from the given string or array.
	 * It utilize {@link Filter::sanitizeVars} function.
	 *
	 * @param mixed value to be sanitized. If array was specified, this 
	 * method will walks recursively.
	 * @return mixed sanitized data
	 */
    private function sanitizeVars($str)
    {
        if(is_array($str))
        {
            $new_vals = array();
            foreach($str as $k=>$v)
				$new_vals[$k] = $this->sanitizeVars($v);
            return $new_vals;
        }
        return Filter::sanitizeVars($str);
    }
	//}}}

	//{{{ checkUTF8InArray
	/**
	 * Checks if there is not not UTF8 value in the specified array.
	 * If though such data was found, they will be removed from the array.
	 *
	 * Parameter to this method is passed by reference.
	 *
	 * @param array reference to array to check.
	 * @return null
	 */
	private function checkUTF8InArray(&$v)
	{
		foreach($v as $k2=>&$v2)
		{
			if(!mb_check_encoding($k2,"UTF8"))
				unset($v[$k2]);
			if(is_array($v2)) 
				$this->checkUTF8InArray($v2);
			elseif(!mb_check_encoding($v2,"UTF8"))
				unset($v[$k2]);
		}
		return true;
    }
	//}}}
}
//}}}
?>
