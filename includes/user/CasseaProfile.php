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
 * Simple and straightforward key-value implementation of profile. 
 * It stores data in DB in row-per-user manner.
 *
 * In order to retrieve data, you should use {@link get} method of  through
 * __get magic method:
 * 
 * <pre><code>
 * 
 * $profile = Profile::get();
 *
 * $birthday = $profile->get('birthday');
 * $hair = $profile->hair;
 *
 * </code></pre>
 *
 * Similar way to set some value - with {@link set} or direct assignment.
 *
 * In all cases, for each required profile parameter, DB column must be present.
 * Trying to set/get non-existent parameter will cause generating ProfileException.
 *
 * Only scalar values could be stored in this profile class.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id:$
 * @package system
 * @since 
 */
//{{{ CasseaProfile
class CasseaProfile implements iProfile
{

	/**
	 * Table name to store data.
	 */
	const TABLE = 'profile';

	/**
	 * @var int id of the user, for which profile object was created.
	 */
	protected $user_id = null;
	/**
	 * @var array profile data.
	 */
	protected $fields = array();

	//{{{ __construct
	/**
	 * @var int id of the user
	 * @throws ProfileException in case of errors
	 */
	function __construct($user_id)
	{
		if(!is_numeric($user_id) || $user_id < 0)
			throw new ProfileException("Unknown user id '".$user_id."'");

		$r = DB::query("select * from ".self::TABLE." where user_id='".$user_id."' limit 1");
		if(count($r))
			$this->fields = array_shift($r);
		else throw new ProfileException("Profile for user with id='{$user_id}' not found");

		$this->user_id = $user_id;
	}
	//}}}

	//{{{ addUser
	/**
	 * This method is called when new user was created and DB row should be added.
	 *
	 * @param int id of newly created user
	 * @return null
	 */
	static function addUser($user_id)
	{
		DB::query("insert into ".self::TABLE." set user_id='".$user_id."'");
	}
	//}}}

	//{{{ __get
	/**
	 * Magic method to retrieve profile data
	 *
	 * @param string name of the profile data
	 * @return scalar value
	 */
	function __get($name)
	{
		if(!array_key_exists($name, $this->fields))
			throw new ProfileException("Profile parameter '{$name}' doesn't exist");
		return $this->fields[$name];
	}
	//}}}

	//{{{ get
	/**
	 * Alias for {@link __get} magic method
	 * @see __get
	 */
	function get($name)
	{ return $this->$name; }
	//}}}

	//{{{ set
	/**
	 * Alias for {@link __set} magic method
	 * @see __set
	 */
	function set($field_name, $value){$this->$field_name = $value;}
	//}}}

	//{{{ __set
	/**
	 * Magic method for setting value to given profile field.
	 *
	 * It also updates DB table with new value.
	 *
	 * @param string name of the profile field
	 * @param scalar value 
	 * @throws ProfileException in case of error
	 */
	function __set($field_name, $value)
	{
		if(!is_scalar($value) 
			|| !array_key_exists($field_name, $this->fields))
			throw new ProfileException("Incorrect value or profile parameter '{$field_name}' doesn't exist");

		$this->fields[$field_name] = $value;
		
		$field_name = Filter::apply($field_name,Filter::STRING_QUOTE_ENCODE);
		$value = Filter::apply($value,Filter::STRING_QUOTE_ENCODE);
		DB::query("update ".self::TABLE." set ".$field_name." = '".$value."' where user_id='".$this->user_id."' limit 1");
	}
	//}}}
}// }}}

