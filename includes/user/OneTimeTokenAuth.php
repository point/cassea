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
 * This class intends for keeping and managing one time token, 
 * used for authentication. 
 *
 * The most obvious purpose is for storing one time tokens to restore
 * lost passwords, or some other action, which require one time authentication. 
 * Default storage is DB table 'user_one_time_token'. 
 *
 * In general, the sequence <code>OneTimeTokenAuth::generateAndAddToken()</code> and 
 * <code>OneTimeTokenAuth::findUser()</code> is used.
 *
 * It additionally has methods for adding user-defined tokens, existence check and 
 * deleting tokens.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id:$
 * @package system
 * @since 
 */
//{{{ OneTimeTokenAuth
class OneTimeTokenAuth
{
	/**
	 * Table to store tokens
	 */
	const TABLE = "user_one_time_token";

	//{{{ findUser
	/**
	 * This method will try to find user id by the given token
	 * and will delete it from the table, if $delete_after 
	 * key is true.
	 * Deleting the key is need to prevent authenticating user by the 
	 * same token several times.
	 * May throw exception if tokens are disabled in config (session.one_time_token.allowed) or
	 * if token doesn't fit the format, defined by the session.one_time_token.regexp config value.
	 *
	 * @param string token to identify the user
	 * @param bool defines whether to delete or not the token after success search
	 * @return int|null user id or null if nothing was found
	 */
	static function findUser($token,$delete_after = true)
	{
		$config = Config::getInstance();

		if(!$config->session->one_time_token->allowed)
			throw new UserException("One time tokens are not maintained. See config for session.one_time_token.allowed");
		if(!preg_match($config->session->one_time_token->regexp,$token))
			throw new UserException("Token doesn't match format. See config for session.one_time_token.regexp");

		$res = DB::query("select * from ".self::TABLE." where token='".Filter::apply($token,Filter::STRING_QUOTE_ENCODE)."' and time > unix_timestamp()");

		if(isset($res[0]))
		{
			if($delete_after) DB::query("delete from ".self::TABLE." where token='".Filter::apply($token,Filter::STRING_QUOTE_ENCODE)."' limit 1");
			return $res[0]['user_id'];
		}
		return null;
	}
	//}}}

	//{{{ deleteExpired
	/**
	 * Service method for removing expired tokens from the DB.
	 *
	 * @return int number of deleted records
	 */
	protected static function deleteExpired()
	{
        $sql = 'delete from ' . self::TABLE . ' where time < unix_timestamp()';
        DB::query($sql);
        return DB::getMysqli()->affected_rows;
	}
	//}}}

	//{{{ addToken
	/**
	 * Assigns custom user-defined token to the user and records it to the DB. 
	 * It may throw exception in case if one time tokens are disabled:
	 * session.one_time_token.allowed config value. Or if new token doesn't fit
	 * the format, defined by the session.one_time_token.regexp config.
	 *
	 * @param string token to be added
	 * @param int id of the user to which assign given token
	 * @return null
	 */
	static function addToken($token, $user_id)
	{
		$config = Config::getInstance();
		if(!$config->session->one_time_token->allowed)
			throw new UserException("One time tokens are not maintained. See config for session.one_time_token.allowed");
		if(!preg_match($config->session->one_time_token->regexp,$token))
			throw new UserException("Token doesn't match format.".
			   " If it was auto-genereated, reset session.one_time_token.regexp to default value");
		if(empty($user_id) || !is_numeric($user_id) || $user_id == User::GUEST)
			throw new UserException("Incorrect user id was given");

		self::deleteExpired();

		DB::query("insert ignore into ".self::TABLE." (token, time,user_id) values('".$token."', '".
			(time()+(int)$config->session->one_time_token->valid_for)."', '".(int)$user_id."')");
	}
	//}}}

	//{{{ generateAndAddToken
	/**
	 * Generates token in default format and adds it to the DB with 
	 * {@link addToken} method. 
	 * Be careful with this function in case of custom format of the token. 
	 * The exception may be raised while trying to find the user by the token.
	 *
	 * @param int id of the user to which assign the new token
	 * @return string token which was added
	 */
	static function generateAndAddToken($user_id)
	{
		self::addToken(($token = md5(rand()*time())),$user_id);
		return $token;
	}
	//}}}

	//{{{ exist
	/**
	 * Checks if there is a token for the given user, defined by user_id parameter.
	 *
	 * @param int id of the user to search
	 * @return bool existence of the token for that user
	 */
	static function exists($user_id)
	{
		if(empty($user_id) || !is_numeric($user_id))
			throw new UserExeption("Incorrect user_id was given");

		$res = DB::query("select count(*) as c from ".self::TABLE." where user_id=".(int)$user_id);
		return $res && $res[0]['c'] == 1;
	}
	//}}}

	//{{{ deleteByToken
	/**
	 * Deletes given token from the DB
	 *
	 * @param string token to delete
	 * @return null
	 */
	static function deleteByToken($token)
	{
		if(empty($token))
			throw new UserExeption("Incorrect token parameter was given");

		DB::query("delete from ".self::TABLE." where token='".Filter::apply($token,Filter::STRING_QUOTE_ENCODE)."' limit 1");
	}
	//}}}
	
	//{{{ deleteByUserId
	/**
	 * Deletes all tokens which belong to the given user.
	 *
	 * @param int id of the user
	 * @return null
	 */
	static function deleteByUserId($user_id)
	{
		if(empty($user_id) || !is_numeric($user_id))
			throw new UserExeption("Incorrect user_id was given");
		DB::query("delete from ".self::TABLE." where user_id=".(int)$user_id);
	}
	//}}}
}
//}}}
