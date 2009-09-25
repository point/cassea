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

//{{{ CasseaProfile
class CasseaProfile implements iProfile
{

	const TABLE = 'profile';

	protected $user_id = null;
	protected $fields = array();

	function __construct($user_id)
	{
		if(!is_numeric($user_id) || $user_id < 0)
			throw new ProfileException("Unknown user id '".$user_id."'");

		$r = DB::query("select * from ".self::TABLE." where user_id='".$user_id."' limit 1");
		if(count($r))
			$this->fields = array_shift($r);
		$this->user_id = $user_id;
	}
	static function addUser($user_id)
	{
		DB::query("insert into ".self::TABLE." set user_id='".$user_id."'");
	}
	function __get($name)
	{
		return isset($this->fields[$name])?$this->fields[$name]:null;
	}

	function set($field_name, $value){$this->$field_name = $value;}

	function __set($field_name, $value)
	{
		if(!is_scalar($value) || !isset($this->fields[$field_name])) return;
		$this->fields[$field_name] = $value;
		
		$field_name = Filter::filter($field_name,Filter::STRING_QUOTE_ENCODE);
		$value = Filter::filter($value,Filter::STRING_QUOTE_ENCODE);
		DB::query("update ".self::TABLE." set ".$field_name." = '".$value."' where user_id='".$this->user_id."' limit 1");
	}
}// }}}

