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

//{{{ User
/**
* @author       billy
*/
class User extends EventBehaviour
{
    const GUEST = -1;
    const TABLE = 'user';
	/**
    * @var      int
    */
    private $id = self::GUEST;
    /**
    * @var      String
    */
    protected $login = 'Guest';
    /**
    * @var      int
    */
    protected $email = 'guest@example.com';

	//default is 'active', 'not_confirmed', 'banned'. 
	//See DB schema for full info
	protected $state = 'active'; 
    /**
    * @var      User
    */
    private static $instance = null;

	private $hashed_password = null;
	private $db_salt = null;

	protected 
		$last_login = null,
		$date_joined = null,
		$single_access_token = null //for auth at RSS/Atom request
		;

    //{{{ get
    /**
    * @return   User
    */
	public static function get($user_id = null)
	{
		if($user_id === null)
		{
			if (!is_object(self::$instance))
				self::$instance = new User();
			return self::$instance;
		}
		return new self($user_id);
    }// }}}


    //{{{ __construct
	public function __construct( $user_id = null)
	{
		if($user_id === null) //init session and find user
			$this->id = Session::get()->find();
		elseif(isset($user_id) && is_numeric($user_id))
			$this->id = (int)$user_id;
		else throw new UserException("Wrong user id '$user_id'");

		$this->trigger("BeforeUserInit",$this);

		if($user_id !== self::GUEST) 
		{
			$r = DB::query("select * from ".self::TABLE." where id='".$this->user_id."' limit 1");
			if(!isset($r[0]))
				throw new UserException("Data for user_id='{$this->user_id}' not found");
			$data = $r[0];
			$this->login = $data['login'];
			$this->hashed_password = $data['password'];
			$this->db_salt = $data['salt'];
			$this->email = $data['email'];
			$this->state = $data['state']; 
			$this->last_login = $data['last_login'];
			$this->date_joined = $data['date_joined'];
			$this->single_access_token = $data['single_access_token'];

			$this->trigger("FillUserData",array($this,$data));
		}
	}
	//}}}

	// {{{
	static function renew()
	{
		$this->trigger("BeforeRenew");

		self::$instance = null;
		return User::get();
	}
    // }}}

	static function logout()
	{
		$this->trigger("BeforeLogout");

		Session::kill();
		return User::renew();
	}

	function delete()
	{
		if($this->id == self::GUEST) return;

		$this->trigger("BeforeDelete",$this);

		DB::query("delete from ".self::TABLE." where id='".$this->id."' limit 1");

		$this->id = self::GUEST;
	}

	//TODO 
	function setPassword($plain_password)
	{
		if(empty($plain_password))
			throw new UserException("Password could not be empty");
	}

	function setHashedPassword($hashed_password)
	{
		if(empty($plain_password) || !is_string($hashed_password))
			throw new UserException("Incorrect hashed password");

		$this->hashed_password = (string)$hashed_password;
	}

    //{{{ getId
    /**
    * @return   int
    */
	public function getId() {  return $this->id;  }
	// }}}
    
    //{{{ getLogin
    /**
    * @return   string
    */
	public function getLogin() {  return $this->login; }
	// }}}

	//TODO
	public function setLogin() {}


	public function getSalt() { return $this->db_salt;}

	public function getHashedPasssword() { return $this->hashed_password; }
    //{{{ getEmail 
    /**
    * @return   string
    */
	public function getEmail() {  return $this->email; }
	// }}}

	public function setEmail($email)
	{
		if(!self::checkEmail($email))
			throw new UserException("Email '$email' doesn't fit to regular expression");
		$this->email  = $email;
	}

	public function getState() { return $this->state; }
	
	public function setState($state)
	{
		if(empty($state) || !is_string($state))
			throw new UserException("State paramenter '$state' has incorrect format");
		$this->state = $state;
	}
    
    //{{{ getProfile
    /**
    * @return   Profile
    */
    public function getProfile() { return Profile::get($this->id);	}
	// }}}

	public function isGuest() { return $this->id == self::GUEST; }

	function getLastLogin() { return $this->last_login; }

	function setLastLogin($last_login)
	{
		if(empty($last_login))
			throw new UserException("Last login paramenter '$last_login' has incorrect format");
		if(is_numeric($last_login))
			$this->last_login = 0+$last_login;
		else 
			$this->last_login = strtotime($last_login);
	}

	function getDateJoined() { 	return $this->date_joined; 	}

	function setDateJoined($date_joined) 
	{
		if(empty($date_joined))
			throw new UserException("Date joined paramenter '$date_joined' has incorrect format");
		if(is_numeric($date_joined))
			$this->date_joined = 0+$date_joined;
		else 
			$this->date_joined = strtotime($date_joined);
	}
	function getSingleAccessToken() { return $this->single_access_token; }
		
	function setSingleAccessToken($token)
	{
		if(empty($token))
			throw new UserException("Single access token '$token' has incorrect format");
		$this->single_access_token = (string)$token;
	}

	//TODO
	function save()
	{
	
	}

	function __destruct() { $this->save(); 	}

	static function findBySingleAccessToken($token)
	{
		if(empty($token))
			throw new UserException("Token is empty");

		$r = DB::query("select id from ".self::TABLE." where single_access_token='".
			Filter::apply($token, Filter::STRING_QUOTE_ENCODE)." limit 1");
		return isset($r[0])?$r[0]:self::GUEST;
	}

	//for single principle with findBySingleAccessToken
	static function findByOneTimeToken($token) 
	{
		return OneTimeTokenAuth::findByOneTimeToken($token);
	}


	// Need for console functions
	function getUsersList(){
		return DB::query('select * from '.self::TABLE.'');
    }
    function getNotConfirmed(){
       return DB::query('select * from '.self::TABLE_REGISTRATION.' order by expires');
	}

	function checkEmail($email)
	{
		return !empty($email && )preg_match(POSTChecker::$email_regexp,$email);
	}


}// }}}

?>
