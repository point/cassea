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
 * This file contains class for encapsultaing data, pasing to the
 * widget during the managing data.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */

 
//{{{ WidgetResultSet
/**
 * This class is only for internal using. No one user-land
 * function should create instance of this class.
 *
 * It creates by the internal classes when system intend to
 * pass some data to the widget. For example, 'text' for WText.
 *
 * Data, stored inside the object could be merged with some 
 * additional data, passed from <code>DataObjectPool</code>
 *  or <code>ResultSet</code> object. They are use only 
 *  setter-like methods and <code>merge()</code>.
 *
 * Instance of this class passed to the widget's <code>setData</code>
 * method. Widget could retreive data with all kind of getter-like
 * methods: <code>get()</code>, <code>getDef()</code> or
 * directly through the <code>__get</code> magick method.
 */
class WidgetResultSet implements IteratorAggregate
{
	private
		/**
		 * Array of stored values
		 * @var array
		 */
		$properties = array(),
		/**
		 * Default value
		 * @var mixed
		 */
		$def = null
		;

	//{{{ merge
	/**
	 * Used to mix already stored data with new data.
	 * Usually called while gathering info from datasets.
	 *
	 * @var array array
	 * @return null
	 */
	function merge($arr)
	{
        if(!is_array($arr)) return;
		foreach($arr as $k => $v)
			if(is_scalar($k) && !is_resource($v))
				$this->properties[$k] = $v;
	}
	//}}}

	//{{{ get
	/**
	 * Used by widgets to retreive value with the given key.
	 * 
	 * @var string the key
	 * @return mixed 
	 */ 
	function get($key)
	{
		return (isset($this->properties[$key]))?$this->properties[$key]:null;
	}
	//}}}

	//{{{ getDef
	/**
	 * Returns default widget value. It might be setted if no data for
	 * the widget was found. For example, for WText, "text" property is default
	 * and could be setted by the system.
	 *
	 * @var mixed default value
	 * @return null
	 * @see getDef
	 */
	function setDef($value)
	{
		if(!isset($value) || is_resource($value)) return;
		$this->def = $value;
	}
	//}}}
	
	//{{{ getDef
	/**
	 * Returns default value for widget
	 * 
	 * @param null
	 * @return mixed
	 * @see getDef
	 */
	function getDef()
	{
		return $this->def;
	}
	//}}}

	//{{{ __get
	/**
	 * Handy replacement for {@link get} method.
	 *
	 * @var string the key
	 * @return mixed
	 */
	function __get($key)
	{
		return $this->get($key);
	}
	//}}}

	//{{{ __isset
	/**
	 * Checks if value exists in the object
	 *
	 * @param string the key
	 * @return bool
	 */
	function __isset($key)
	{
		return isset($this->properties[$key]);
	}
	//}}}

	//{{{ isEmpty
	/**
	 * Checks if current object has no assigned data
	 *
	 * @param null
	 * @return bool
	 */
	function isEmpty()
	{
		return (empty($this->properties) && empty($this->def));
	}
	//}}}

	// {{{ getIterator
	// implements
	function getIterator(){	return t(new ArrayObject($this->properties))->getIterator();}
	//}}}

}
//}}}


