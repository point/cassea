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

//{{{ Session
/**
 * This class is used to keep the state between request at server side.
 * It can use different storages to keep data, such as database, 
 * memcached, redis, etc. All the engines are located in the 
 * vendors/session/{engine}/ directories.
 * This class use some of the config directives, which are described 
 * at config.ini file.
 */
class Session extends EventBehavior
{
	/**
	 * Instance of session class.
	 * @var      SessionBase
	 */
	protected static $instance = null;
	/**
	 * Instance of engine to store data.
	 * @var      SessionEngine
	 */
	protected $engine = null;
	/**
	 * Full IP address of the user.
	 * @var      string
	 */
	protected $ip;
	/**
	 * Session id.
	 * @var      string
	 */
	protected  $id = null;
	/**
	 * Id of the session's user.
	 * @var      int
	 */
	protected $user_id = null;
	/**
	 * Remember me flag.
	 * @var      bool
	 */
	protected $remebmer_me = false;
	/**
	 * DB-side cast for the session.
	 * @var      string 
	 */
	protected $cast = null;
	/**
	 * Flag which shows if user is verified guest (which 
	 * has signed cookie).
	 * @var      bool
	 */
	protected $verified_guest = false;
	/**
	 * Parameters to be saved to the storage.
	 * @var      array
	 */
	public $params2save = array();
	/**
	 * Persistance flag.
	 * @var      bool
	 */
	protected $is_persistent = true;

	//{{{ __construct
	public function __construct()
	{
		$config = Config::getInstance();

		$this->trigger("BeforeInit",$this);

		$this->params2save += array("id","user_id","cast","ip","remember_me");

		$sessionEngine = Config::getInstance()->session->engine;
		$classname = nameToClass($sessionEngine);
		Autoload::addVendor("session",$sessionEngine);

		$classname .= "Session";

		$this->engine = new $classname();
		if(!$this->engine instanceof SessionEngine)
			throw new SessionException("Class '$classname' is not valid session engine");

		$this->engine->init();

		Controller::getInstance()->onBeforeHeadBodyTail = array($this,"save");

		$this->trigger("AfterInit",$this);
		register_shutdown_function(array($this,"save"));
	}
	//}}}

	//{{{ init
	/**
	 * Initialize session engine. Should be called just one time. Usually, it's called
	 * by the Boot class and it's not designed to be called from any other place.
	 *
	 * @return   SessionBase instance of the session
	 */
	public static function init()
	{
		if (is_object(self::$instance)) return;

		self::$instance = new self();
	}
	//}}}

	//{{{ find
	/**
	 * Tries to find session, basing on cookie data, DB (or other storage) data.
	 * To reduce DB searches of guest sessions, incoming cookie data may be signed to
	 * ensure, that session key wasn't changed by the user or on the way to server. 
	 * To switch on such functionality, define 
	 * <code>session.encrypt_guest_cookie.use=1</code>
	 *
	 * Additionally, custom method of session search could be implemented. Just 
	 * hang on the "BeforeSessionSearch" behavior. If session id isn't null, that's a
	 * sign, that session was properly initialized by such third-party module.
	 *
	 * There are some config flags, that defines behavior of session search.
	 * 
	 * <code>session.snap_to_ip</code>
	 * shows, that session is tied with IP address. If user's address changes, the 
	 * session will be lost.
	 * <code>session.check_cast</code>
	 * If defined, the additional data from user will be checked (user agent, preferred languages,
	 * accept charset). In case of incompatibility, session won't be found.
	 * <code>session.single_access.allowed</code> if true gives one time authorized access (e.g. for
	 * private, custom created RSS). It works, if single access token is pointed.
	 * <code>session.single_access.token</code> defines the name of GET parameter for the token
	 * (http://site.com/?token=123123123)
	 *
	 * Behaviors BeforeSessionSearch and AfterSessionSearch are defined.
	 *
	 * @return   SessionBase instance of the session
	 */
	public function find()
	{
		$config = Config::getInstance();

		$this->trigger("BeforeSessionSearch",$this);

		$this->ip  = $this->getFullIP();

		if($this->id === null && $this->user_id === null) //id or user_id can be set in the event handler
		{
			$cs = $this->getClientSession();
			$ss = array();

			//leave $ss empty (if verified_guest and cookie was marked) to setup guest session
			$this->verified_guest = $cs['verified_guest'];
			if($this->verified_guest && $config->session->encrypt_guest_cookie->use)
			{
				$this->setupGuest($cs['id']);
				$this->trigger("AfterSessionSearch",$this);
				return $this->user_id;
			}

			$ss = $this->getServerSession($cs['id']);

			$param = array();

			if($cs['id'] && $ss && $ss['id'] && $ss['id'] == $cs['id'] && 
				($config->session->snap_to_ip ? $this->ip == $ss['ip']:true) && 
				($config->session->check_cast ? $cs['cast'] ==  $ss['cast']:true))

			{
				foreach($this->params2save as $v)
					if(array_key_exists($v, $ss))
						$this->$v = $ss[$v];
			}
			else
				$this->setupGuest();
		}

		if($this->user_id == User::GUEST && !$this->verified_guest
			&& $config->session->single_access->allowed 
			&& isset(Controller::getInstance()->get->{$config->single_access->token})) //chek request type or accept params
		{
			$this->user_id = User::findBySingleAccessToken(
				Controller::getInstance()->get->{$config->single_access->token});
			$this->is_persistent = ($this->user_id != User::GUEST);
			$this->remember_me = 0;
		}

		$this->trigger("AfterSessionSearch",$this);

		if(!$this->id || !$this->user_id)
			throw new SessionException("Session id or user id not found");

		return $this->user_id;

	}// }}}

	//{{{ setupGuest
	/**
	 * Sets up the guest session, so the user with current session will be treated as guest.
	 *
	 * Behaviors BeforeSetupGuest and AfterSetupGuest are defined.
	 *
	 * @param  string optional id of the session. If passed, the given session will be marked as guest one
	 * @return int user id (guest :) )
	 */
	function setupGuest($sid = null)
	{
		$this->trigger("BeforeSetupGuest",$this);

		$this->user_id = User::GUEST;
		$this->remember_me = 0+Config::getInstance()->session->remember_me;
		$this->cast = $this->makeCast();
		$this->id =  $sid?$sid:@md5(uniqid(microtime()) . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'] . mt_rand(100000,999999));

		$this->trigger("AfterSetupGuest",$this);

		return $this->user_id;
	}
	//}}}

	//{{{ getInstance
	/**
	 * Returns instance of session object. If session id is not defined, 
	 * the {@link find} method will be called.
	 *
	 * Use it to get current session.
	 *
	 * @return   SessionBase
	 */
	public static function getInstance()
	{
		//always initialized first by the Boot.php
		if(is_null(self::$instance))
			throw new SessionException("Session subsystem wasn't initialized in proper way. Check session.enabled config variable.");

		if(self::$instance->getId() === null)
			self::$instance->find();
		//may return null in case if session_enbaled is false
		return self::$instance;
	}
	// }}}

	//{{{ kill 
	/**
	 * Kills the current session and make user as guest.
	 *
	 * Behaviors BeforeSessionKill and AfterSessionKill are defined.
	 */
	public function kill()
	{
		$config = Config::getInstance();
		$this->trigger("BeforeSessionKill");
		Controller::getInstance()->cookies[$config->session->cookie->name] = array(
			"value"=>null,
			"expire"=>time() - 1000,
		);
		$this->engine->kill($this->id);
		$this->setupGuest();
		$this->trigger("AfterSessionKill");
	}
	//}}}

	//{{{ deleteExpired
	/**
	 * This function deletes expired session.
	 *
	 * Behaviors BeforeDeleteExpired and AfterDeleteExpired are defined.
	 */
	function deleteExpired()
	{
		$this->trigger("BeforeDeleteExpired");
		$this->engine->deleteExpired();
		$this->trigger("AfterDeleteExpired");
	}
	// }}}

	//{{{ getFullIP
	/**
	 * Gets client ip.
	 * Return string like "151.2.41.55, 192.168.0.4". That means outer address and local 
	 * address after the comma, if NAT is used.
	 *
	 * Behavior AfterGetfullIP is defined.
	 *
	 * @return string
	 */
	private function getFullIP()
	{
		$strRemoteIP = $_SERVER['REMOTE_ADDR'];
		if (!$strRemoteIP) {
			$strRemoteIP = urldecode(getenv('HTTP_CLIENTIP'));
		}
		if (getenv('HTTP_X_FORWARDED_FOR')) {
			$strIP = getenv('HTTP_X_FORWARDED_FOR');
		}
		elseif (getenv('HTTP_X_FORWARDED')) {
			$strIP = getenv('HTTP_X_FORWARDED');
		}
		elseif (getenv('HTTP_FORWARDED_FOR')) {
			$strIP = getenv('HTTP_FORWARDED_FOR');
		}
		elseif (getenv('HTTP_FORWARDED')) {
			$strIP = getenv('HTTP_FORWARDED');
		} else {
			$strIP = $_SERVER['REMOTE_ADDR'];
		}
		if(ip2long($strRemoteIP) === false)
			throw new SessionException("Session: user ip is invalid");

		if ($strRemoteIP != $strIP && !empty($strIp)) {
			$strIP = $strRemoteIP . ', ' . $strIP;
		}
		$this->trigger("AfterGetfullIP", array(&$strIP));
		return Filter::apply($strIP,Filter::STRING_QUOTE_ENCODE);
	}
	//}}}

	// {{{ getIP
	/**
	 * Gets client ip.
	 * Return string like "151.2.41.55". ip2long of it !== false anytime 
	 * if correct env variables were passed
	 *
	 * @return string
	 */
	public function getIp()
	{
		$ip = $this->getFullIP();
		if(strpos($ip,",") !== false)
			list($ip) = explode(",",$ip);
		return trim($ip);
	}
	//}}}

	//{{{ getClientSession
	/**
	 * Returns array with info about user's session (but not the Session object).
	 * It's primarily for internal use. 
	 *
	 * It takes in consideration name of the cookie with the session information, 
	 * encrypt_guest_cookie setting, and hashing method.
	 *
	 * Behavior AfterGetClientSession is defined.
	 *
	 * @return   array
	 */
	protected function getClientSession()
	{
		$config = Config::getInstance();
		$cookie_name = $config->session->cookie->name;
		$verified_guest = false;

		$t_sid = Controller::getInstance()->cookies[$cookie_name];
		if($config->session->encrypt_guest_cookie->use && $t_sid && strpos($t_sid,":"))
		{
			Controller::getInstance()->cookies->bindRegexp($cookie_name,'/^[A-Za-z0-9]{32}:'.
				//base64 part
				'(?:[A-Za-z0-9+\/]{4})*(?:[A-Za-z0-9+\/]{2}==|[A-Za-z0-9+\/]{3}=)?$/');

			$cp = new CryptoProvider();
			@list($sid,$hash) = @explode(":",Controller::getInstance()->cookies->$cookie_name);
			if($hash && $hash == $this->getGuestHash($sid))
				$verified_guest = true;
		}
		else 
		{
			Controller::getInstance()->cookies->bindRegexp($cookie_name,'/^[A-Za-z0-9]{32}$/');
			$sid = Controller::getInstance()->cookies->$cookie_name;
		}
		$ret = array(
			'id' => $sid,
			'cast' => $this->makeCast(),
			'verified_guest' => $verified_guest && !empty($sid)
		);
		$this->trigger('AfterGetClientSession', array(&$ret));
		return $ret;
	}
	//}}}

	//{{{ save
	/**
	 * Saves session to storage (DB) and sends cookies. This function
	 * utilizes 
	 * <code>session.cookie.name</code> for cookie name to send,
	 * <code>session.encrypt_guest_cookie.use</code> to define whether to use 
	 * signing of guest cookie,
	 * <code>session.cookie.length</code> to define TTL of cookies,
	 * <code>session.remember_me</code> to extend TTL on the 
	 * <code>session.remember_me_for</code>.
	 *
	 * It's usually called by "register_shutdown_function".
	 *
	 * Behaviors BeforeSendCookieOnSave, AfterSendCookieOnSave, 
	 * BeforeSave, AfterSave are defined.
	 *
	 * @return   array
	 */
	function save()
	{
		$config = Config::getInstance();

		$this->trigger("BeforeSendCookieOnSave",$this);

		if(!$this->is_persistent) return;

		Controller::getInstance()->cookies[$config->session->cookie->name] = array(
			"value"=>($config->session->encrypt_guest_cookie->use && $this->user_id == User::GUEST ?
			($this->id.":".$this->getGuestHash($this->id)):$this->id), 

			"expire"=>($config->session->cookie->length == 0 && !$config->session->remember_me)?0: 
			(time() + $config->session->cookie->length + ($config->session->remember_me?$config->session->remember_me_for:0))
		);

		$this->trigger("AfterSendCookieOnSave",$this);

		//do not save session data to DB/storage/etc if verified guest
		if($config->session->encrypt_guest_cookie->use && $this->user_id == User::GUEST && $this->verified_guest) return;

		$params = array();
		foreach($this->params2save as $v)
			$params[$v] = $this->$v;

		$this->deleteExpired();

		$params['time'] = (time() + Config::getInstance()->session->length) +
			($this->remember_me?Config::getInstance()->session->remember_me_for:0);

		$this->trigger("BeforeSave",array(&$params));

		if($params)
			$this->engine->save($this->id,$params);

		$this->trigger("AfterSave",$this);
	}
	//}}}

	//{{{ makeCast
	/**
	 * Returns cast string, which builds basing on $_SERVER information. 
	 * It's usually takes part while checking the user's session information.
	 *
	 * Behavior AfterMakeCast is defined.
	 *
	 * @return   String
	 */
	protected function makeCast()
	{
		$cast = @md5($_SERVER['HTTP_USER_AGENT'].$_SERVER['HTTP_ACCEPT_LANGUAGE'].$_SERVER['HTTP_ACCEPT_CHARSET'].$_SERVER['HTTP_ACCEPT_ENCODING']);
		$this->trigger("AfterMakeCast", array(&$cast));
		return $cast;
	}
	//}}}

	//{{{ getServerSession
	/**
	 * Returns array with information about session from the storage (usually DB) to 
	 * make comparison with client session info.
	 * This method proxies call to the engine to achieve storage independence.
	 *
	 * Behavior AfterGetServerSession is defined.
	 *
	 * @return   array
	 */
	protected function getServerSession($sid)
	{
		$ss = $this->engine->getServerSession($sid);
		$this->trigger("AfterGetServerSession", array(&$ss));
		return $ss;
	}
	//}}}

	//{{{ getGuestHash
	/**
	 * Returns string, hashed by the 
	 * <code>session.encrypt_guest_cookie.hash</code> 
	 * to sign guest cookie. It uses session id and 
	 * <code>config.crypto.secret</code> to get required hash.
	 *
	 * Behavior AfterGetGuestHash is defined.
	 *
	 * @return   array
	 */
	private function getGuestHash($sid)
	{
		$cp = new CryptoProvider;
		$config = Config::getInstance();

		$hash = base64_encode($cp->hash($sid.$config->crypto->secret,
			$config->session->encrypt_guest_cookie->hash));
		$this->trigger("AfterGetGuestHash", array(&$sid));
		return $hash;
	}
	//}}}

	//{{{ setId
	/**
	 * Setter for session id.
	 *
	 * @param string session id
	 */
	function setId($id)
	{
		if(empty($id) || !is_string($id))
			throw new SessionException("Session id is empty");
		$this->id = $id;
	}
	//}}}

	//{{{ getId
	/**
	 * Getter for session id.
	 *
	 * @return string
	 */
	function getId() { return $this->id; }
	//}}}

	//{{{ setRememberMe
	/**
	 * Defines whether to use or not the "remember me"
	 * functionality.
	 *
	 * @param bool 
	 */
	function setRememberMe($remember_me)
	{
		$this->remember_me = 0+$remember_me;
	}
	//}}}

	//{{{ getRememberMe
	/**
	 * Getter for remember me.
	 *
	 * @return bool
	 */
	function getRememberMe() { return $this->remember_me; }
	//}}}

	//{{{ setCast
	/**
	 * Redefines server-side cast for the particular session.
	 *
	 * @param string cast 
	 */
	function setCast($cast) 
	{ 
		if(!is_scalar($cast))
			throw new SessionException("Wrong cast format");
		$this->cast = (string)$cast;
	}
	//}}}

	//{{{ getCast
	/**
	 * Returns server-side cast.
	 *
	 * @return string
	 */
	function getCast() { return $this->cast; }
	//}}}

	//{{{ setUserId
	/**
	 * Sets new user id for the whole session lifetime (not only for particular request).
	 *
	 * @param int id of user 
	 */
	function setUserId($user_id)
	{
		if(!is_numeric($user_id) && $user_id < 1)
			throw new SessionException("Wrong user_id. Use setupGuest instead");
		$this->user_id = (int)$user_id;
	}
	//}}}

	//{{{ getUserId
	/**
	 * Returns id of current session user.
	 *
	 * @return int
	 */
	function getUserId() { return $this->user_id; }
	//}}}

	//{{{ setPersistent
	/**
	 * Sets whether current session is persistent between the requests, or it will be lost
	 * during the next request.
	 *
	 * @param bool persistance of the current session.
	 */
	function setPersistent($is_persistent = true) { $this->is_persistent = (bool)$is_persistent; }
	//}}}
	
	//{{{ getPersistent
	/**
	 * Returns whether the session is persistent between requests
	 *
	 * @return bool
	 */
	function getPersistent() { return $this->is_persistent; }
	//}}}
}
//}}}
?>
