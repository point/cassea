<?php
/*- vim:expandtab:shiftwidth=4:tabstop=4: 
{{{ LICENSE  
* Copyright (c) 2008, Cassea Project
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

 
// $Id:  $
//
class WidgetResultSet implements IteratorAggregate
{
	private
		$properties = array(),
		$def = null
		;
	function merge($arr)
	{
		foreach($arr as $k => $v)
			if(is_scalar($k) && is_scalar($v))
				$this->properties[$k] = $v;
	}
	function get($key)
	{
		return (isset($this->properties[$key]))?$this->properties[$key]:null;
	}
	function setDef($value)
	{
		if(!is_scalar($value)) return;
		$this->def = $value;
	}
	function getDef()
	{
		return $this->def;
	}
	function __get($key)
	{
		return $this->get($key);
	}
	function __isset($key)
	{
		return isset($this->properties[$key]);
	}
	function isEmpty()
	{
		return (empty($this->properties) && empty($this->def));
	}
	// implements
	function getIterator(){	return t(new ArrayObject($this->properties))->getIterator();}

}
class ResultSet
{
	private 
		$cur_for = null,
		$fors = array(),
		$for_values = array(),
		$default_values = array()
		;
	function f($selector = "",$index = null,$scope = "global")
	{

		$this->cur_for = count($this->fors);// i.e. +1
		$this->fors[$this->cur_for] = array("selector"=>$selector,"index"=>$index,"scope"=>$scope);
		$this->for_values[$this->cur_for] = array();
		return $this;
	}
	function set($key,$value)
	{
		if(!isset($this->cur_for)) return $this;
		$this->for_values[$this->cur_for][$key] = $value;
		return $this;
	}
	function def($value)
	{
		if(!isset($this->cur_for)) return $this;
		$this->default_values[$this->cur_for] = $value;
	}
	function findMatched(WidgetResultSet $wrs,WComponent $widget)
	{
		foreach($this->fors as $ind => $selectors_a)
			foreach(explode(",",$selectors_a['selector']) as $selector)
				if(SelectorMatcher::matched($widget,$selector,$selectors_a['index'],$selectors_a['scope']))
					$wrs->merge($this->for_values[$ind]);

		return $wrs;
	}
	function __call($name,$arguments)
	{
		if(!isset($arguments[0])) return;
		if($name === 'f') return $this->f($arguments[0]);
		return $this->set($name,$arguments[0]);
	}
}
