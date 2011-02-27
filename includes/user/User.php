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
 * This file contains class, storing basic user's information, such as login, email, etc.
 * All the information is stored in DB table.
 *
 * @author billy <alexey.mirniy@gmail.com>
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id:$
 * @package system
 * @since 
 */
//{{{ User
/**
 * Class for storing information about primary user's data. For now it's:
 * id, login, email, hashed password, salt, state, last login date, date of join and 
 * single access token. It's required to be at least one user in DB table. It's a 
 * special with id = -1 which represents guest and not-authorized user.
 * 
 * Additionally, helper classes may be used: OneTimeTokenAuth to manage one time login (see
 * docs inside this class) and CasseaProfile to keep additional to user's data in simple 
 * key-value way.
 */
class User extends EventBehavior
{
	/**
	 * Id of the guest user
	 */
    const GUEST = -1;
	/**
	 * Table to store information about the users
	 */
    const TABLE = 'user';
	/**
	 * Id of the user
	 * @var int
	 */
	private $id = null;
	/**
	 * Login of the user
	 * @var   string
	 */
	protected $login = 'Guest';
	/**
	 * Email of the user
	 * @var string
	 */
	protected $email = 'guest@example.com';

	/**
	 * State of the user. Default states are 'active', 'not_confirmed', 'banned'. 
	 * See DB schema for full info.
	 * @var string
	 */
	protected $state = 'active'; 
	/**
	 * Instance of the singleton
	 * @var User
	 */
	private static $instance = null;
	/**
	 * Hashed password of the user
	 * @var string
	 */
	private $hashed_password = null;
	/**
	 * DB-side salt for salting user's password
	 * @var string
	 */
	private $db_salt = null;

	protected 
		/**
		 * Date of last login
		 * @var string
		 */
		$last_login = null,
		/**
		 * Date then the user was registered
		 * @var string
		 */
		$date_joined = null,
		/**
		 * Token for single access
		 * @var string
		 */
		$single_access_token = null //for auth at RSS/Atom request
		;

	//{{{ get
	/**
	 * Singleton + Factory method to get specified user. 
	 * If argument is omitted, then current user object will be returned. The search
	 * is performed basing on the session information. Internally, passing <code>User::GUEST</code> id
	 * to the constructor will cause to find the user via session mechanism. 
	 * Once founded that object will be cached in <code>$instance</code> to prevent constant 
	 * search of particular user. 
	 *
	 * You can use constructor directly to create object of another user than currently logged in. 
	 *
	 * @param int user id to find
	 * @return   User
	 */
	public static function get($user_id = null)
	{
		if($user_id === null)
		{
			if (!is_object(self::$instance))
				self::$instance = new self(User::GUEST); //passing GUEST to find current session
			return self::$instance;
		}
		return new self($user_id);
	}
	//}}}


	//{{{ __construct 
	/**
	 * Behaviors BeforeUserInit, FillUserData, AfterUserInit are defined
	 *
	 * @param int user id to load
	 */
	public function __construct($user_id = null)
	{
		if($user_id === User::GUEST) //init session and find user
			$user_id = Session::getInstance()->find();

		if(is_null($user_id)) return; //simply user object without initial info
		if($this->id == $user_id && $this->login) return; //we have loaded info. No need to do it again

		elseif(isset($user_id) && is_numeric($user_id))
			$this->id = (int)$user_id;
		else throw new UserException("Wrong user id '$user_id'");

		$this->trigger("BeforeUserInit",$this);

		//user_id can be changed in mixin
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

			$this->trigger("FillUserData",array($this));
		}
		$this->trigger("AfterUserInit",$this);

		$this->mix(new DirtyMixin($this, 'instance'));
		register_shutdown_function(array($this,"save"), false);
	}
	//}}}

	//{{{ renew
	/**
	 * Initiating renewal of cached object. That means that
	 * search of current user will start from the beginning.
	 *
	 * @return User new founded instance
	 */
	static function renew()
	{
		self::$instance = null;
		return User::get();
	}
	//}}}

	//{{{ logout
	/**
	 * Kills the session of current user and renew stored instance.
	 *
	 * Behavior BeforeLogout is defined.
	 *
	 * @return User new instance of the user (guest user)
	 */
	function logout()
	{
		if($this->id != Session::getInstance()->getUserId()) return;
		$this->trigger("BeforeLogout");

		Session::getInstance()->kill();
		return User::renew();
	}
	//}}}

	//{{{ delete
	/**
	 * Deletes particular user from the database. If the user is a guest, exception will be raised.
	 *
	 * After the deletion, to prevent unstable state, object gets GUEST user id.
	 * If currently logged in user is deleting, the session will be reinitialized as a anonymous.
	 *
	 * Behavior BeforeDelete and AfterDelete are defined.
	 *
	 * @param null
	 * @return null
	 * @throws UserException if user cannot be deleted.
	 */
	function delete()
	{
		if($this->id == self::GUEST) 
			throw new UserException("Cannot delete guest user");

		$this->trigger("BeforeDelete",$this);

		DB::query("delete from ".self::TABLE." where id='".$this->id."' limit 1");

		if(Session::getInstance()->getUserId() == $this->id)
			Session::getInstance()->setUserId(self::GUEST);
		$this->id = self::GUEST;
		$this->trigger("AfterDelete",$this);
	}
	//}}}

	//{{{ add
	/**
	 * Adds the user to the database. 
	 * There are bunch of required parameters required to be passed as $params array:
	 * - login: the arbitrary string which will be used as a login for the user. The checks defined in
	 * {@link setLogin} method will be performed.
	 * - password: user's password. The checks defined in {@link setPassword} will be performed.
	 * - email: required parameter to, for example, send confirmation emails. The checks defined in 
	 * {@link setEmail} method will be performed.
	 *
	 * Optionally, the 'state' parameter may be passed, showing the status of newly created user. 
	 * If it's omitted, the "not_confirmed" or "active" state will be chosen depending on the 
	 * <code>config.user.registration_confirm</code> config parameter. If the value distinguish 
	 * from the described ones, make sure that this value could be held by the DB schema of the 'state' column.
	 *
	 * If <code>config.user.registration_confirm</code> config parameter is set to non-false value, 
	 * the one time token will be added for newly created user. This could be used for example to
	 * send activation email. Logic behind that should be created separately in the model/controller.
	 *
	 * Additionally, the profile entry for the new user will be created.
	 *
	 * Behavior BeforeAddNewUser is defined.
	 *
	 * @param array parameters for the new user as described above
	 * @return User new user object
	 * @throws UserException in case of errors
	 */
	static function add(array $params)
	{
		if(empty($params['login']) || 
			empty($params['password']) || 
			empty($params['email']))
			throw new UserException("Login, password and email must be passed");

		$config = Config::getInstance();
		
		if(empty($params['state']))
			$params['state'] = ($config->user->registration_confirm)?"not_confirmed":"active";

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

		Profile::addUser($new_user->getId());
		return $new_user;
	}
	//}}}
	
	//{{{ recoverPassword
	/**
	 * Helper function to recover lost password. It just finds user by email, sets
	 * random token for one time auth and returns it back. All the logic related to the
	 * email sending should created separately.
	 *
	 * @param string email of the user
	 * @return string one time auth token for the founded user
	 * @throws UserException if user wasn't found or in case of incorrect parameter
	 */
	static function recoverPassword($email) 
	{
		if(!self::checkEmailFormat($email))
			throw new UserException("Email doesn't fit the format");

		$user = self::findBy("email",$email);
		if($user === null)
			throw new UserException("User not found");

		return OneTimeTokenAuth::generateAndAddToken($user->getId());
	}
	//}}}

	//{{{ setPassword
	/**
	 * Sets password for the current user. The password is represented as a plain string which will
	 * be hashed. The verification by {@link checkPasswordFormat} is performed beforehand. The UserException
	 * will be raised in case of error.
	 *
	 * @param string the plain password to be hashed and set
	 * @return null
	 * @throws UserException in case of errors
	 */
	function setPassword($plain_password)
	{
		if(!PasswordAuth::checkPasswordFormat($plain_password))
			throw new UserException("Incorret password parameter");

		if($this->id == self::GUEST)
			throw new UserException("Cannot change password for guest user. Use User::add instead");

		$this->hashed_password = PasswordAuth::hashPassword($this,$plain_password);
	}
	//}}}
	
	//{{{ setHashedPassword
	/**
	 * Use with care! 
	 * Sets already hashed password for the user account. Using wrong hashing method will make 
	 * the password unusable. So the user won't be able to login.
	 *
	 * @param string already hashed password for the user
	 * @return null
	 * @throws UserException if parameter is wrong
	 */
	function setHashedPassword($hashed_password)
	{
		if(empty($plain_password) || !is_string($hashed_password))
			throw new UserException("Incorrect hashed password");

		$this->hashed_password = (string)$hashed_password;
	}
	//}}}

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

	//{{{ setLogin
	//could be called only against new users
	/**
	 * Sets login for the user. It could be set if it fits the format, user isn't a guest, 
	 * and there is no user with the same login.
	 *
	 * @param new user login
	 * @return null
	 * @throws UserException in case of errors
	 */
	public function setLogin($new_login) 
	{
		if(!PasswordAuth::checkLoginFormat($new_login))
			throw new UserException("Incorrect login parameter");

		if($this->id == self::GUEST)
			throw new UserException("Cannot change login for guest user. Use User::add instead");

		if(self::findBy("login",$new_login) !== null)
			throw new UserException("User with login '$new_login' already exists");

		$this->login = $new_login;
	}
	//}}}


	//{{{ getSalt
	/**
	 * @return string
	 */
	public function getSalt() { return $this->db_salt;}
	//}}}

	//{{{ setSalt
	/**
	 * Sets DB-side salt for hashing the password.
	 *
	 * @param string salt to be set
	 * @return null
	 * @throws UserException in case of errors
	 */
	public function setSalt($salt) 
	{ 
		if(empty($salt) || !preg_match("/[A-Za-z0-9]+/",$salt))
			throw new UserException("Incorrect salt parameter '{$salt}'");
		$this->db_salt = $salt;
	}
	//}}}

	//{{{ getHashedPasssword
	/**
	 * @return string
	 */
	public function getHashedPasssword() { return $this->hashed_password; }
	//}}}
	
    //{{{ getEmail 
    /**
    * @return   string
    */
	public function getEmail() {  return $this->email; }
	// }}}

	//{{{ setEmail
	/**
	 * Sets the new email address for the particular user.
	 *
	 * @param email address
	 * @return null
	 * @throws UserException in case of wrong email format
	 */
	public function setEmail($email)
	{
		if(!self::checkEmailFormat($email))
			throw new UserException("Email '$email' doesn't fit to regular expression");
		$this->email = $email;
	}
	//}}}

	//{{{ getState
	public function getState() { return $this->state; }
	//}}}
	
	//{{{ setState
	/**
	 * Sets new status of the user. If the value distinguish 
	 * from the described ones, make sure that this value 
	 * could be held by the DB schema of the 'state' column.
	 *
	 * @param string new state
	 * @return null
	 * @throw UserException in case of error
	 */
	public function setState($state)
	{
		if(empty($state) || !is_string($state))
			throw new UserException("State paramenter '$state' has incorrect format");
		$this->state = $state;
	}
	//}}}
    
    //{{{ getProfile
    /**
    * @return Profile object for the current user
    */
    public function getProfile() { return Profile::get($this->id);	}
	// }}}

	//{{{ isGuest
	/**
	 * @return bool which shows if user is anonymous
	 */
	public function isGuest() { return $this->id == self::GUEST; }
	//}}}

	//{{{ getLastLogin
	/**
	 * @return string last login date time
	 */
	function getLastLogin() { return $this->last_login; }
	//}}}

	//{{{ setLastLogin
	/**
	 * Sets last login date-time stamp. Parameter could be a string. In this 
	 * case it will be converted to UNIX time stamp using strtotime().
	 *
	 * @param int|string last login datetime
	 * @return null
	 * @throw UserException in case of errors
	 */
	function setLastLogin($last_login)
	{
		if(empty($last_login))
			throw new UserException("Last login paramenter '$last_login' has incorrect format");
		if(is_numeric($last_login))
			$this->last_login = 0+$last_login;
		else 
			$this->last_login = strtotime($last_login);
	}
	//}}}

	//{{{ getDateJoined
	/**
	 * @return string date when user registered on the site
	 */
	function getDateJoined() { 	return $this->date_joined; 	}
	//}}}

	//{{{ setDateJoined
	/**
	 * Adjusts the date when user joined the site.
	 * Parameter could be string. In this case it will converted to unix time stamp
	 * using the strtotime().
	 *
	 * @param int|string date joined datetime
	 * @return null
	 * @throw UserException in case of errors
	 */
	function setDateJoined($date_joined) 
	{
		if(empty($date_joined))
			throw new UserException("Date joined paramenter '$date_joined' has incorrect format");
		if(is_numeric($date_joined))
			$this->date_joined = 0+$date_joined;
		else 
			$this->date_joined = strtotime($date_joined);
	}
	//}}}

	//{{{ getSingleAccessToken
	function getSingleAccessToken() { return $this->single_access_token; }
	//}}}

	//{{{ setSingleAccessToken
	/**
	 * Sets new single access token for the user. 
	 *
	 * @param string token to set
	 * @return null
	 * @throw UserException if token doesn't fit the format
	 */
	function setSingleAccessToken($new_token) 
	{ 
		if(empty($new_token) || !preg_match("/[A-Za-z0-9]+/",$new_token))
			throw new UserException("Single access token '$token' has incorrect format");
		$this->single_access_token = $new_token;
	}
	//}}}

	//{{{
	/**
	 * Generates, sets and returns the single access token, basing on
	 * {@link setSingleAccessToken}.
	 *
	 * @param null
	 * @return string generated token
	 */
	function setRandomSingleAccessToken()
	{
		//salt is pretty fit as single access token, so why not to use
		$this->setSingleAccessToken($token = PasswordAuth::generateSalt());
		return $token;
	}
	//}}}
		
	//{{{ save
	/**
	 * Saves info about the user when need. 
	 * When new user was created, the insertion is making in any case.
	 * The save operation is scheduled on each shutdown to get the convenient 
	 * auto-save feature while working with users. To prevent unnecessary DB inserts,
	 * this method based on the "dirty" plugin, which tells when any internal 
	 * data was changed and required to be saved to the database.
	 *
	 * Additionally, an extra info could be passed to the $params2save data structure via the 
	 * BeforeSave* Behaviors. 
	 * For example, it could be some OAuth or OpenID info.
	 * And to achieve this $params2save introduces special format to store data -- it's array with mixed 
	 * keys:
	 * - if key is a string, the resulting DB query will be looks like `key`='value'
	 * - if key is a numeric value, the value will be passed to resulting DB query as-is without any manipulations.
	 * 
	 * Behaviors BeforeSaveNewUser, AfterSaveNewUser, BeforeSaveUser, AfterSaveUser are available.
	 *
	 * @param bool if true, the existing user info will be saved under any circumstances.
	 * @return null
	 */
	function save($force = true)
	{
		//no need to save guest user
		if($this->id == self::GUEST)
			return;

		//making insert
		if($this->id === null)
		{
			$params2save = array(
				"login"=>		Filter::apply($this->getLogin(),Filter::STRING_QUOTE_ENCODE),
				"email"=>		Filter::apply($this->getEmail(),Filter::STRING_QUOTE_ENCODE),
				"state"=>		Filter::apply($this->getState(),Filter::STRING_QUOTE_ENCODE),
				"password"=>	Filter::apply($this->getHashedPasssword(),Filter::STRING_QUOTE_ENCODE),
				"salt"=>		Filter::apply($this->getSalt(),Filter::STRING_QUOTE_ENCODE),
				// actually we are not intend to log him in
				//"last_login = now()", 
				"date_joined = now()",
				"single_access_token"=>Filter::apply($this->getSingleAccessToken(),Filter::STRING_QUOTE_ENCODE));

			$this->trigger("BeforeSaveNewUser",array($this,&$params2save));

			$to_sql = array();
			foreach($params2save as $k=>$v)
				if(is_string($k))
					$to_sql[] = "`$k`='$v'";
				elseif(is_int($k))
					$to_sql[] = $v;
			if(empty($to_sql))
				throw new UserException("Can't insert new user. Data is empty");

			$this->id = DB::query("insert into ".self::TABLE." set ".implode(", ",$to_sql));

			$this->trigger("AfterSaveNewUser",array($this));
		}
		//dirty method appears via the "dirty" mixin plugin
		//and show if internal data should be saved to DB
		elseif($force || $this->dirty())
		{
			$params2save = array(
				"login"=>		Filter::apply($this->getLogin(),Filter::STRING_QUOTE_ENCODE),
				"email"=>		Filter::apply($this->getEmail(),Filter::STRING_QUOTE_ENCODE),
				"state"=>		Filter::apply($this->getState(),Filter::STRING_QUOTE_ENCODE),
				"password"=>	Filter::apply($this->getHashedPasssword(),Filter::STRING_QUOTE_ENCODE),
				"salt"=>		Filter::apply($this->getSalt(),Filter::STRING_QUOTE_ENCODE),
				"last_login = from_unixtime('".$this->getLastLogin()."') ",
				"date_joined = from_unixtime('".$this->getDateJoined()."') ",
				"single_access_token"=>Filter::apply($this->getSingleAccessToken(),Filter::STRING_QUOTE_ENCODE));

			$this->trigger("BeforeSaveUser",array($this,&$params2save));

			$to_sql = array();
			foreach($params2save as $k=>$v)
				if(is_string($k))
					$to_sql[] = "`$k`='$v'";
				elseif(is_int($k))
					$to_sql[] = $v;
			if(empty($to_sql))
				throw new UserException("Can't save user. Data is empty");

			DB::query("update ".self::TABLE." set ".implode(", ",$to_sql)." where id='{$this->id}' limit 1");

			$this->trigger("AfterSaveUser",array($this));
		}
	}
	//}}}

	//{{{ findBySingleAccessToken
	/**
	 * Finds user by given single access token. If nobody was found,
	 * the guest user object will be returned.
	 *
	 * @param string token to look up
	 * @return User object
	 * @throw UserException if token has wrong format
	 */
	static function findBySingleAccessToken($token)
	{
		if(empty($token))
			throw new UserException("Token is empty");

		$r = DB::query("select id from ".self::TABLE." where single_access_token='".
			Filter::apply($token, Filter::STRING_QUOTE_ENCODE)."' limit 1");
		$id = isset($r[0])?$r[0]['id']:self::GUEST;
		return new self($id);
	}
	//}}}

	//{{{ findByOneTimeToken
	//for single principle with findBySingleAccessToken
	/**
	 * Finds user by given one time token. If nobody was found,
	 * the guest user object will be returned.
	 * Also, the one time token could be deleted if the appropriate user
	 * was found.
	 *
	 * @param string token to search user by
	 * @param bool defines whether to remove given token
	 * @return User object
	 */
	static function findByOneTimeToken($token, $delete_after = true) 
	{
		return new self(OneTimeTokenAuth::findUser($token, $delete_after));
	}
	//}}}

	//{{{ findIdBy
	/**
	 * Finds user id by the any of the key-value (login, date_joined, etc...).
	 * For example, this could be used in such way:
	 * <pre><code>
	 * $user = User::findIdBy('login','point');
	 * </code></pre>
	 *
	 * @param string key to search
	 * @param string value of the key to search 
	 * @return int|null id of the found user, or null if nobody was found
	 * @throws UserException of any of the parameters has incorrect format 
	 * of if such key doesn't map to the DB field.
	 */
	static function findIdBy($key = "login", $value = "")
	{
		if(empty($key) || !is_string($key))
			throw new UserException("Incorrect key '$key'");
		if(empty($value) || !is_string($value))
			throw new UserException("Incorrect value '$value'");

		try {
			$ret = DB::query("select id from ".self::TABLE.
				" where `".Filter::apply(strtolower($key),Filter::STRING_QUOTE_ENCODE)."`='".Filter::apply($value,Filter::STRING_QUOTE_ENCODE).
				"' limit 1");
		}catch(DBException $e) {
			throw new UserException("Cannot not find user with '$key'='$value'");
		}
		return isset($ret[0])?$ret[0]['id']:null;
	}
	//}}}

	//{{{ findBy
	/**
	 * As {@link findIdBy} searches user by key-value, but returns object
	 * instead of id.
	 *
	 * @param string key to search
	 * @param string value of the key to search 
	 * @return User|null object, or null if nobody was found
	 * @throws UserException of any of the parameters has incorrect format 
	 * of if such key doesn't map to the DB field.
	 */
	static function findBy($key = "login",$value = "") { $id = self::findIdBy($key,$value); return $id?self::get($id):null; }
	//}}}

	//{{{ auth
	/**
	 * Authenticates current user with credentials, passed as a parameters. The user should be
	 * guest. If not, exception will be raised. You should make logout before.
	 * 
	 * The $auth_credentials parameter should contain information to auth. In case of simple
	 * built-in auth, the array must contain "login" and "password" keys. Optionally, "one_time_token" 
	 * may be passed to authenticate using it instead of login and password. 
	 * Custom auth methods (OAuth, OpenID) may use this array to pass required information. The
	 * BeforeAuth behavior code intercept this credentials and manage custom authentication.
	 * If after that callback session was updated with new user, the auth process considered to be 
	 * successful and further actions will be skipped.
	 *
	 * If user.split_auth_message is not false, the incorrect auth message will be split into two messages:
	 * one for incorrect login, another for incorrect password. In other case, the single message 
	 * will be outputted via the exception.
	 *
	 * Behaviors BeforeAuth and AfterAuth are available.
	 */
	function auth(array $auth_credentials)
	{
		if($this->id !== self::GUEST)
			throw new UserException("User already authenticated.Log out before.");
		if(empty($auth_credentials))
			throw new UserException("You must specify auth credentials. E.g. array('login'=>'qwe', 'password'=>'qwe') ");

		$this->trigger("BeforeAuth",array($this,&$auth_credentials));
		
		$new_user = User::renew();
		if($new_user->isGuest() && //there was no custom auth. Still guest
			isset($auth_credentials['login'], $auth_credentials['password']))
		{
			$new_user = self::findBy("login",$auth_credentials['login']);
			if(Config::getInstance()->user->split_auth_message)
			{
				if(is_null($new_user))
					throw new UserAuthException("No such user with login '{$auth_credentials['login']}'");

				if(!PasswordAuth::match($new_user, $auth_credentials['password']))
					throw new UserAuthException("Password don't match");

				elseif($new_user->getState() != "active") 
					throw new UserAuthException("User is not active");
			}
			elseif(is_null($new_user) || $new_user->getState() != "active" || 
				!PasswordAuth::match($new_user, $auth_credentials['password']))
					throw new UserAuthException("Login or password don't match or user is not active");
		}

		if(User::renew()->isGuest() && //there was no custom auth => auth with one time token
			Config::getInstance()->session->one_time_token->allowed &&
			isset($auth_credentials['one_time_token']))
		{
			if(is_null($user_id = OneTimeTokenAuth::findUser($auth_credentials['one_time_token'],true)))
				throw new UserAuthException("Wrong one time token");
			
			$new_user = self::findBy("id",$user_id);
		}

		$this->trigger("AfterAuth",array($this,&$new_user));
		
		return self::forceAuth($new_user);
	}
	//}}}

	//{{{ forceAuth
	/**
	 * Forces user authentication without ANY checks. Use with caution. 
	 * Primarily it's used by the internal methods.
	 *
	 * @param User object to be authenticated
	 * @return User object
	 */
	static function forceAuth($new_user) 
	{
		$new_user->setLastLogin(time());
		$new_user->save();
		Session::getInstance()->setUserId($new_user->getId());
		Session::getInstance()->save();
		User::renew();
		return $new_user;
	}
	//}}

	//{{{ getAll
	/**
	 * Retrieves all the user's info with given state. If state == "all", all the users will be 
	 * selected, otherwise only users with state $state will be selected.
	 * The second parameter tells whether to load full info or only ids.
	 *
	 * @param string state of the users to be selected
	 * @param bool whether to load full info about the users
	 * @return array indexed array ids or with array of selected info
	 */
	static function getAll($state = "all",$full_info = false)
	{
		$all = array();
		$state = Filter::apply($state,Filter::STRING_QUOTE_ENCODE);
		foreach(DB::query("select ".($full_info?"*":"id")." from ".self::TABLE.($state !== "all"?" where state='$state'":"")) as $v)
			$all[] = $full_info?$v:$v['id'];
		return $all;
	}
	//}}}

	//{{{ checkEmailFormat
	/**
	 * Helper function to get info about correctness of passed email address.
	 *
	 * @return bool
	 */
	static function checkEmailFormat($email)
	{
		return !empty($email) && preg_match(POSTChecker::$email_regexp,$email);
	}
	//}}}
}
// }}}

