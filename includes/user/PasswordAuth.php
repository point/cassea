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
 * This class maintains password authentification of the users.
 * It use code and DB salting mechanisms to prevent cracking passwords
 * with rainbow tables.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: Controller.php 184 2009-11-05 15:14:47Z point $
 * @since 
 */
//{{{ PasswordAuth
class PasswordAuth
{

	//{{{ checkLoginFormat
	/**
	 * Checks login for conformity with the format, defined in config.
	 *
	 * @return bool result of conformity check
	 */
	static function checkLoginFormat($login)
	{
        return !empty($login) && preg_match(Config::getInstance()->user->login_regexp, $login);
	}
	//}}}

	//{{{ checkPasswordFormat
	/**
	 * Checks password for conformity with the format, defined in config.
	 *
	 * @return bool result of conformity check
	 */
	static function checkPasswordFormat($password)
	{
		return !empty($password) && preg_match(Config::getInstance()->user->password_regexp, $password);
	}
	//}}}

	//{{{ generatePassword
	/**
	 * Generates password with required length with limited possible chars 
	 * to escape misunderstood. e.g. 0 and O. The length must be > 2 and < 64.
	 * In other cases, the length will be reduced to the specified values.
	 *
	 * @param int 
	 * @return string genarated password
	 */
	static function generatePassword($length = 8)
	{
		$length = max(2, min($length,64));

		$str = 'ABCDEFGHIJKLMNPQRSTUVWYXZabcdefghijkmnpqrstuvwyxz123456789';
		$len_1 = strlen($str)-1;
		$res='';
		for($i=0;$i<$length;$i++)
			$res.=$str[mt_rand(0,$len_1)];
		return $res;
	}
	//}}}

    //{{{ generateSalt
	/**
	 * Generates salt for the DB salting of the password. Returns 
	 * unique string of variable length, but not grater 16 letters.
	 *
	 * @return string genarated salt
	 */
    static function generateSalt(){
        return substr(md5(uniqid(rand(), true)),rand(0,13),16);
	}
	//}}}

	//{{{ match
	/**
	 * Returns if password matches with the hashed password of the given user.
	 *
	 * This method contain additional functionality to move existece base of passwords to the 
	 * newer hashing algorithm. To do it, specify old names of the hash classes in
	 * <code>user.password.transition.hash_classes</code> config parameter, separated
	 * with the "," .
	 * In this case, 
	 * this method will try to match the password using current hash function.
	 * In case of failure, every hash class from the transition will be used sequentially
	 * to check the password. If this search was succeed, password hash will be updated with
	 * newer version of hash algorithm.
	 *
	 * All hash manipulations are made using {@link CryptoProvider} class.
	 *
	 * @param User instance of the user, which password is checking
	 * @param string unhashed password, usually entered in "password" field on the form
	 * @return bool true if hashed passwords are equal
	 * @see CryptoProvider
	 */
	static function match(User $user, $unhashed_password)
	{
		$config = Config::getInstance();
		$cp = new CryptoProvider();

		$res = $res2 = false;

		$res = (self::hashPassword($user,$unhashed_password)== $user->getHashedPasssword());

		//current hash algo didn't return proper value. Trying to use transition hash algo
		if(!$res && $config->user->password->transition->use)
			foreach(array_map('trim',explode(",",$config->user->password->transition->hash_classes)) as $v)
				if(($res2 = $cp->hash($password_string,$v))) break;

		// if user hash hash with received from old hash function -> change his hash with new hash algo
		if($res2)
			$user->setHashedPassword($cp->hash($password_string,$config->password->hash));

		return ($res || $res2);
	} 
	//}}}

	//{{{ hashPassword
	/**
	 * Performs hashing of the password. 
	 * If user is newly created, new random hash will be assigned. This salt
	 * is mandatory.
	 *
	 * The server-side salt is optional and could be mixed in if config parameter 
	 * <code>user.server_salt.use</code> is set.
	 *
	 * Hashing method is defined by the <code>user.password.hash</code> config parameter.
	 * It could be as custom as default for all hashing (user.password.hash=":default")
	 *
	 * @param User instance of the user, which salt is taken.
	 * @param string unhashed password to be hashed
	 * @return string hashed password
	 * @see CryptoProvider
	 * @throws UserException
	 */
	static function hashPassword($user,$unhashed_password)
	{
		$config = Config::getInstance();

		if(empty($unhashed_password))
			throw new UserException("Password could not be empty");

		$cp = new CryptoProvider();
		$user_salt = $user->getSalt()?$user->getSalt():self::generateSalt();
		$user->setSalt($user_salt);
		$password_string = $unhashed_password.$user_salt.
			($config->user->server_salt->use?$config->user->server_salt->salt:"");
		return $cp->hash($password_string, Config::getInstance()->user->password->hash) ;
	}
	//}}}
}
//}}}
