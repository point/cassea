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

//{{{ PasswordAuth

class PasswordAuth
{
	static function checkLoginFormat($login)
	{
        return !empty($login) && preg_match(Config::getInstance()->user->login_regexp, $login);
	}

	static function checkPasswordFormat($password)
	{
		return !empty($password) && preg_match(Config::getInstance()->user->password_regexp, $password);
	}

	// {{{ generatePassword
	static function generatePassword( $length = 8 )
	{
		$length = min($length,64);

		$str='123456789QWERTYUIPASDFGHJKLZXCVBNM';
		$len_1 = strlen($str)-1;
		$res='';
		for($i=0;$i<$length;$i++)
			$res.=$str[mt_rand(0,$len_1)];
		return $res;
	}//}}}

    // {{{ generateSalt
    static function generateSalt(){
        return substr(md5(uniqid(rand(), true)),rand(0,15),16);
	}
	//}}}

	// {{{
	static function comparePasswords($unhashed_password,$hashed_password,$server_salt)
	{
		return 
			self::buildPassword($unhashed_password,$server_salt,Config::getInstance()->user->secret) == $hashed_password;
    } // }}}
}


//}}}
