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

class HTTPParamHolder implements IteratorAggregate
{
	private $vars = array(),
		$checked_vars = array();
	function __construct(array $vars,$allow_array = true)
	{
		if(!empty($this->vars) || !empty($this->checked_vars)) return;
		if(!empty($vars))
			$this->vars = $vars;
		foreach($vars as $k=>&$v)
			if((is_scalar($v) && mb_check_encoding($k,"UTF8") && mb_check_encoding($v,"UTF8"))||
				$allow_array &&!is_scalar($v) && mb_check_encoding($k,"UTF8"))
				$this->checked_vars[$k] = &$v;
	}
	function getAll()
	{
		return $this->vars;
	}
	function getAllChecked()
	{
		return $this->checked_vars;
	}
	function __get($var_name)
	{
		if(isset($this->checked_vars[$var_name]))
			return $this->checked_vars[$var_name];
		return null;
	}
	function __isset($var_name)
	{
		return isset($this->checked_vars[$var_name]);
	}
	function bindFilter($var_name,$type)
	{
		if(!isset($this->checked_vars[$var_name])) return;
		$this->checked_vars[$var_name] = Filter::filter($this->checked_vars[$var_name],Filter::getFilter($type));
	}
	function isEmpty()
	{
		return empty($this->checked_vars);
	}
	function getIterator()
	{
		return t(new ArrayObject($this->checked_vars))->getIterator();
	}
	function delete($var_name)
	{
		if(!isset($this->checked_vars[$var_name])) return;
		unset($this->checked_vars[$var_name]);
	}
}
?>
