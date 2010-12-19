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
 * @package user
*/
class Session extends EventBehaviour
{
    /**
    * @var      SessionBase
    */
    protected static $instance = null;
	protected $engine = null;
    /**
    * @var      int
    */
    protected $ip;
    /**
    * Id сессси
    * @var      int
    */
    protected  $id = null;
    /**
    * @var      int
    */
	protected $user_id = null;

	protected $remebmer_me = false;


	protected $cast = null;

	protected $verified_guest = false;

	public $params2save = array();

	protected $is_persistent = true;
    
	
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

    //{{{ init
    /**
    * @return   SessionBase
    */
    public static function init()
    {
        if (is_object(self::$instance)) return;

		self::$instance = new self();
	}
	//}}}
	
	//{{{ find
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
			if(!($this->verified_guest = $cs['verified_guest']) || 
				($this->verified_guest && !$config->session->encrypt_guest_cookie->use))
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

    //{{{ get
    /**
    * Возвращает объект Сессии
    * @return   SessionBase
    */
    public static function getInstance()
	{
		//always initialized first by the Boot.php
		if(is_null(self::$instance))
			throw new SessionException("Session subsystem wasn't initialized in proper way. Check session.enabled config variable.");
		
		//may return null in case if session_enbaled is false
        return self::$instance;
    }// }}}
    
    //{{{ kill 
    /**
    * @return   
    */
    public function kill()
    {
		$config = Config::getInstance();
		Controller::getInstance()->cookies[$config->session->cookie->name] = array(
			"value"=>null,
			"expire"=>time() - 1000,
		);
		$this->engine->kill($this->id);
		$this->setupGuest();

    }// }}}

    //{{{ deleteExpired
    /**
    * @return   int
    */
	function deleteExpired()
	{
		$this->trigger("BeforeDeleteExpired");
		$this->engine->deleteExpired();
		$this->trigger("AfterDeleteExpired");
	}
    // }}}

   // {{{ getFullIP
    /**
    * get client ip.
    * Return string like "151.2.41.55, 192.168.0.4" 
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
        return Filter::apply($strIP,Filter::STRING_QUOTE_ENCODE);
    }// }}}

   // {{{ getIP
    /**
    * get client ip.
	* Return string like "151.2.41.55". ip2long of it !== false anytime 
	* if correct env variables was passed
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
        return array(
            'id' => $sid,
	 		'cast' => $this->makeCast(),
			'verified_guest' => $verified_guest && !empty($sid)
        );
    }// }}}

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

    //{{{ makeCast
    /**
    * @return   String
    */
    protected function makeCast()
    {
		return @md5($_SERVER['HTTP_USER_AGENT'].$_SERVER['HTTP_ACCEPT_LANGUAGE'].$_SERVER['HTTP_ACCEPT_CHARSET'].$_SERVER['HTTP_ACCEPT_ENCODING']);
    }// }}}

    //{{{ getServerSession
    /**
    * @return   Object
    */
	protected function getServerSession($sid)
	{
		return $this->engine->getServerSession($sid);
	}
	// }}}

	private function getGuestHash($sid)
	{
		$cp = new CryptoProvider;
		$config = Config::getInstance();

		return base64_encode($cp->hash($sid.$config->crypto->secret,
				$config->session->encrypt_guest_cookie->hash));
	}
	function setId($id)
	{
		if(empty($id))
			throw new SessionException("id is empty");
		$this->id = $id;
	}
	function getId() { return $this->id; }

	function setRememberMe($remember_me)
	{
		$this->remember_me = 0+$remember_me;
	}
	function getRememberMe() { return $this->remember_me; }

	function setCast($cast) 
	{ 
		if(!is_scalar($cast))
			throw new SessionException("Wrong cast format");
		$this->cast = (string)$cast;
	}
	function getCast() { return $this->cast; }
		
	//this cause using given user_id for a whole session lifetime, not only for paricular request
	function setUserId($user_id)
	{
		if(!is_numeric($user_id) && $user_id < 1)
			throw new SessionException("Wrong user_id. Use setupGuest instead");
		$this->user_id = (int)$user_id;
	}
	function getUserId() { return $this->user_id; }
	function getPersistent() { return $this->is_persistent; }
	function setPersistent($is_persistent = true) { $this->is_persistent = (bool)$is_persistent; }

}// }}}
?>
