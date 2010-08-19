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

//{{{ OneTimeTokenAuth

class OneTimeTokenAuth
{
	const TABLE = "user_one_time_tokens";

	static function findUser($token,$delete_after = false)
	{
		$config = Config::getInstance();

		if(!$config->session->one_time_token->allowed)
			throw new UserException("One time tokens are not maintained");
		if(!preg_match($config->session->one_time_token->regexp,$token))
			throw new UserException("Token doesn't match format.");

		$res = DB::query("select * from ".self::TABLE." where token='".Filter::apply($token,Filter::STRING_QUOTE_ENCODE)."' and time > unix_timestamp()");

		if(isset($res[0]))
		{
			if($delete_after) DB::query("delete from ".self::TABLE." where token='".Filter::apply($token,Filter::STRING_QUOTE_ENCODE)."' limit 1");
			return $res[0]['user_id'];
		}
		return null;
	}

	protected static function deleteExpired()
	{
        $sql = 'delete from ' . self::TABLE . ' where time < unix_timestamp()';
        DB::query($sql);
        return DB::getMysqli()->affected_rows;
	}

	static function addToken($token, $user_id)
	{
		$config = Config::getInstance();
		if(!$config->session->one_time_token->allowed)
			throw new UserException("One time tokens are not maintained");
		if(!preg_match($config->session->one_time_token->regexp,$token))
			throw new UserException("Token doesn't match format.".
			   " If it was auto-genereated, reset session.one_time_token.regexp to default value");
		if(empty($user_id) || !is_numeric($user_id) || $user_id == User::GUEST)
			throw new UserException("Incorrect user id was given");

		self::deleteExpired();

		DB::query("insert ignore into ".self::TABLE." token='".$token."', time='".
			time()+(int)$config->session->one_time_token->valid_for."', user_id='".(int)$user_id);
	}
	static function generateAndAddToken($user_id)
	{
		self::addToken(($token = md5(rand()*time())),$user_id);
		return $token;
	}
	static function exists($user_id)
	{
		if(empty($user_id) || !is_numeric($user_id))
			throw new UserExeption("Incorrect user_id was given");

		$res = DB::query("select count(*) as c from ".self::TABLE." where user_id=".(int)$user_id);
		return $res && $res[0]['c'] == 1;
	}
	static function deleteByToken($token)
	{
		if(empty($token))
			throw new UserExeption("Incorrect token parameter was given");

		DB::query("delete from ".self::TABLE." where token='".Filter::apply($token,Filter::STRING_QUOTE_ENCODE)."' limit 1");
	}
	static function deleteByUserId($user_id)
	{
		if(empty($user_id) || !is_numeric($user_id))
			throw new UserExeption("Incorrect user_id was given");
		DB::query("delete from ".self::TABLE." where user_id=".(int)$user_id);
	}
}
//}}}
