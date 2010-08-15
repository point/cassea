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
    private $id = null;
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
				self::$instance = new User(User::GUEST); //passing GUEST to find current session
			return self::$instance;
		}
		return new self($user_id);
    }// }}}


    //{{{ __construct
	public function __construct( $user_id = null)
	{
		if($user_id === User::GUEST) //init session and find user
			$this->id = Session::get()->find();
		
		if(is_null($iser_id)) return; //simply user object without initial info

		elseif(isset($user_id) && is_numeric($user_id))
			$this->id = (int)$user_id;
		else throw new UserException("Wrong user id '$user_id'");

		$this->trigger("BeforeUserInit",$this);

		if($user_id !== self::GUEST) 
		{
			$r = DB::query("select * from ".self::TABLE." where id='".$this->id."' limit 1");
			if(!isset($r[0]))
				throw new UserException("Data for user_id='{$this->id}' not found");
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

	static function add(array $params)
	{
		if(empty($params['login']) || 
			empty($params['password']) || 
			empty($params['email']))
			throw new UserException("Login, password and email must be passed");

		$config = Config::getInstance();
		
		if(empty($params['state']))
			$params['state'] = ($config->user->registration_confirm)?"not_active":"active";

		if(self::findBy('login',$params['login']))
			throw new UserException("User with the same login already exists");

		$new_user = new self(null);

		$new_user->setLogin($params['login']);
		$new_user->setPassword($params['password']);
		$new_user->setState($params['state']);
		$new_user->setEmail($params['email']);

		$new_user->trigger("BeforeAddNewUser",array($new_user,&$params));

		$new_user->save();

		$one_time_token = null;
		if($config->user->registration_confirm)
			$one_time_token = OneTimeTokenAuth::generateAndAddToken($new_user->getId());

		return $new_user;
	}
	
	static function recoverPassword($email) 
	{
		if(!self::checkEmailFormat($email))
			throw new UserException("Email doesn't fit the format");

		$user = self::findBy("email",$email);
		if($user === null)
			throw new UserException("User not found");

		return OneTimeTokenAuth::generateAndAddToken($user->getId());
	}

	function setPassword($plain_password)
	{
		if(!PasswordAuth::checkPasswordFormat($plain_password))
			throw new UserException("Incorret password parameter");

		if($this->id !== null)
			throw new UserException("Cannot change password for already logged-in or guest user. Use User::add instead");

		$this->hashed_password = PasswordAuth::hashPassword($this,$plain_password);
	}

	//use with care
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

	//could be called only against new users
	public function setLogin($new_login) 
	{
		if(!PasswordAuth::checkLoginFormat($new_login))
			throw new UserException("Incorrect login parameter");

		if($this->id !== null)
			throw new UserException("Cannot change login for already logged-in or guest user. Use User::add instead");

		if(self::findBy("login",$new_login) !== null)
			throw new UserException("User with login '$new_login' already exists");

		$this->login = $new_login;
	}


	public function getSalt() { return $this->db_salt;}

	public function setSalt($salt) 
	{ 
		if(empty($salt) || !preg_match("/[A-Za-z0-9]+/",$salt))
			throw new UserException("Incorrect salt parameter");
		$this->db_salt = $salt;
	}

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
		$this->email = $email;
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

	function setSingleAccessToken($new_token) 
	{ 
		if(empty($new_token) || !preg_match("/[A-Za-z0-9]+/",$new_token))
			throw new UserException("Incorrect token parameter");
		$this->single_access_token = $new_token;
	}
	function setRandomSingleAccessToken()
	{
		//salt is pretty fit as single acess token, so why not to use
		$this->setSingleAccessToken(PasswordAuth::generateSalt());
	}
		
	function setSingleAccessToken($token)
	{
		if(empty($token))
			throw new UserException("Single access token '$token' has incorrect format");
		$this->single_access_token = (string)$token;
	}

	function save()
	{
		//no need to save guest user
		if($this->id == self::GUEST)
			return;

		//making insert
		if($this->id === null)
		{
			$params2save = array(
				"login"=>		Filter::apply($this->getLogin(),Filter::STRING_QUOTE_ENCODE)
				"email"=>		Filter::apply($this->getEmail(),Filter::STRING_QUOTE_ENCODE)
				"state"=>		Filter::apply($this->getState(),Filter::STRING_QUOTE_ENCODE)
				"password"=>	Filter::apply($this->getHashedPasssword(),Filter::STRING_QUOTE_ENCODE)
				"salt"=>		Filter::apply($this->getSalt(),Filter::STRING_QUOTE_ENCODE)
				"last_login =now()".
				"date_joined =now()".
				"single_access_token"=>Filter::apply($this->getSingleAccessToken(),Filter::STRING_QUOTE_ENCODE));

			$this->trigger("BeforeSaveNewUser",array($this,&$params2save));

			//TODO!!Ask  Billy
			$this->id = DB::getStmt('insert into '. self::TABLE.' ('.implode(", ",array_keys($params2save)).') value ('.
				implode(",",array_pad(array(),count($params2save),"?")).")")
				->execute(array_values($params));

			$this->trigger("AfterSaveNewUser",array($this));
		}
		else
		{
			$params2save = array(
				"email"=>		Filter::apply($this->getEmail(),Filter::STRING_QUOTE_ENCODE)
				"state"=>		Filter::apply($this->getState(),Filter::STRING_QUOTE_ENCODE)
				"password"=>	Filter::apply($this->getHashedPasssword(),Filter::STRING_QUOTE_ENCODE)
				"salt"=>		Filter::apply($this->getSalt(),Filter::STRING_QUOTE_ENCODE)
				"last_login = from_unixtime(".$this->getLastLogin()."), "
				"single_access_token"=>Filter::apply($this->getSingleAccessToken(),Filter::STRING_QUOTE_ENCODE));

			$this->trigger("BeforeSaveUser",array($this,&$params2save));

			DB::getStmt('update '. self::TABLE.' ('.implode(", ",array_keys($params2save)).') value ('.
				implode(",",array_pad(array(),count($params2save),"?")).") where id='".$this->id."' limit 1")
				->execute(array_values($params));

			$this->trigger("AfterSaveUser",array($this));
		}
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
	static function findIdBy($key = "login", $value = "")
	{
		if(empty($key) || !is_string($key))
			throw new UserException("Incorrect key '$key'");
		if(empty($value) || !is_string($value))
			throw new UserException("Incorrect value '$value'");

		try {
			$ret = DB::query("select id from ".self::TABLE.
				" where `".Filter::apply($key,Filter::STRING_QUOTE_ENCODE)."`='".Filter::apply($value,Filter::STRING_QUOTE_ENCODE).
				"' limit 1");
		}catch(DBException $e) {
			throw new UserException("Could not find user with '$key'='$value'");
		}
		return isset($ret[0])?$ret[0]['id']:null;
	}

	//return user object
	static function findBy($key = "login",$value = "") { $id = self::findIdBy($key,$value); return $id?self::get($id):null; }


	function auth(array $auth_tokens)
	{
		if($this->id !== self::GUEST)
			throw new UserException("User already authenticated");
		if(empty($auth_tokens))
			throw new UserException("You must specify auth tokens. E.g. array('login'=>'qwe', 'password'=>'qwe') ");
		$this->trigger("BeforeAuth",array($this,&$auth_tokens));

		
		if(User::renew()->isGuest() && //there was no custom auth. Still guest
			isset($auth_tokens['login'], $auth_tokens['password']))
		{
		
			if(is_null($new_user = self::findBy("login",$auth_tokens['login'])))
				throw new UserAuthException("No such user");

			if(Config::getInstance()->user->split_auth_message)
				if(!PasswordAuth::match($new_user, $unhashed_password))
					throw new UserAuthException("Password don't match");
				elseif($new_user->getState() != "active") 
					throw new UserAuthException("User is not active");
			elseif($new_user->getState() != "active" || !PasswordAuth::match($new_user, $unhashed_password))
					throw new UserAuthException("Login or password don't match");

			Session::get()->setUserId($new_user->getId()); //setting user id for seesion
			User::renew();
		}

		if(User::renew()->isGuest() && //there was no custom auth => auth with one time token
			Config::getInstance()->one_time_token->allowed &&
			isset($auth_tokens['one_time_token']))
		{
			if(is_null($user_id = OneTimeTokenAuth::findUser($auth_tokens['one_time_token'],true)))
				throw new UserAuthException("Wrong one time token");
			
			$new_user = self::findBy("id",$user_id);

			Session::get()->setUserId($new_user->getId());
			User::renew();
		}

	}
	// Need for console functions
	/*function getUsersList(){
		return DB::query('select * from '.self::TABLE.'');
    }
    function getNotConfirmed(){
       return DB::query('select * from '.self::TABLE_REGISTRATION.' order by expires');
	}*/

	static function checkEmailFormat($email)
	{
		return !empty($email) && preg_match(POSTChecker::$email_regexp,$email);
	}


}// }}}

?>
