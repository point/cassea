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
 * This file contains collection of classes for
 * handy storing of parameters for Template.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */

//{{{ TemplateParams
/**
 * This class holds array of TemplateParam objects and give methods
 * for handy manipulation of this list. It also supports fluent interface
 * for method set.
 *
 * In general, no limitation for parameters. They might be strings, integers,
 * arrays, objects. But it recomends to keep this params as simple as 
 * possible.
 */
class TemplateParams
{
	private 
		/**
		 * @var array
		 * List of parameters
		 */
		$properties = array()
		;

	//{{{ __set
	/**
	 * Magic method for setting value.
	 *
	 * @param string name of the parameter
	 * @param mixed value of the parameter
	 * @return null
	 * @see set
	 */
	function __set($name = null,$val = null)
	{
		$this->set($name,$val);
	}
	//}}}

	//{{{ __get
	/**
	 * Magic method for retrieving value of given parameter.
	 *
	 * @param string name of the parameter
	 * @return null
	 */
	function __get($name)
	{
		if(isset($this->properties[$name]))
			return $this->properties[$name];
		return null;
	}
	//}}}

	//{{{ __isset
	/**
	 * Magic method for checking of existence of the parameter
	 * by it's name
	 *
	 * @param string name of the parameter
	 * @return bool
	 */
	function __isset($name)
	{
		return isset($this->properties[$name]);
	}
	//}}}
	
	//{{{ set
	/**
	 * Similar to {@link __set} method, but this method 
	 * returns $this to support fluent interfaces.
	 *
	 * @param string name of the parameter
	 * @param mixed value of the parameter
	 * @return TemplateParams object
	 * @see __set
	 */
	
	function set($name = null,$val = null)
	{
		if(!isset($val) || empty($name)) return $this;

		if(!isset($this->properties[$name]))
			$this->properties[$name] = new TemplateParam($val);
		else
			$this->properties[$name]->setProp($val);
		return $this;
	}
	//}}}

	//{{{ attr
	/**
	 * For internal use only!
	 */
	function attr()
	{
		return $this->properties;
	}
	//}}}

	//{{{ merge
	/**
	 * It adds parameters of passed object to the current list.
	 * Identical params in the current object would be overwritten 
	 * by the new.
	 *
	 * @param TemplateParams object to be merged
	 * @return null
	 */
	function merge(TemplateParams $t)
	{
		foreach($t->attr() as $k=>$v)
				$this->properties[$k] = $v;
	}
	//}}}
}
//}}}

//{{{ TemplateParam
/**
 * Object of this class holds one parameter of the TemplateParams list.
 */
class TemplateParam implements IteratorAggregate,ArrayAccess
{
	private 
		/**
		 * @var mixed
		 * Holds scalar value. Null if value is array.
		 */
		$scalar = null,
		/**
		 * @var array
		 * Holds array value. Null if value is scalar.
		 */
		$array = null

		;
	//{{{ __construct
	/**
	 * Initialize and sets the property
	 *
	 * @param mixed parameter value
	 */
	function __construct($param = null)
	{
		if(!isset($param) ) return;
		$this->setProp($param);
	}
	//}}}

	//{{{ setProp
	/**
	 * Sets value of this parameter.
	 *
	 * @param mixed parameter value
	 * @return null
	 */
	function setProp($param )
	{
		if(is_scalar($param))
			$this->scalar = $param;
		elseif(is_array($param))
			$this->array = new ArrayObject($param);
	}
	//}}}

	//{{{ getIterator
	/**
	 * Standart overloaded function.
	 * Returns iterator if current object holds array
	 */
	function getIterator()
	{
		return $this->array->getIterator();
	}
	//}}}

	//{{ offsetExists
	/**
	 * Standart overloaded function.
	 */
	function offsetExists($offset)
	{
		return $this->array->offsetExists($offset);
	}
	//}}}

	//{{{ offsetGet
	/**
	 * Standart overloaded function.
	 */
	function offsetGet($offset)
	{
		return $this->array->offsetGet($offset);
	}
	//}}}

	//{{{ offsetSet
	/**
	 * Standart overloaded function.
	 */
	function offsetSet($offset,$value)
	{
		return $this->array->offsetSet($offset,$value);
	}
	//}}}

	//{{{ offsetUnset
	/**
	 * Standart overloaded function.
	 */
	function offsetUnset($offset)
	{
		return $this->array->offsetUnset($offset);
	}
	//}}} 

	//{{{ __toString
	/**
	 * Standart overloaded function.
	 * Return text representation of current scalar value.
	 */
	function __toString()
	{
		return (string)$this->scalar;
	}
	//}}}
}
//}}}
