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


class UserManagerException extends Exception {}


class UserManager
{
	static function get()
	{
		if(Config::getInstance()->user->authenticator == "cassea")
			return new CasseaUserManager();
	}
}


interface iUserManager
{

    const REGEXP_LOGIN ='#^[a-zA-Z0-9_\-\.]{3,20}$#';
    const REGEXP_EMAIL = '/^[a-z0-9&\'\.\-_\+]+@[a-z0-9\-]+\.([a-z0-9\-]+\.)*?[a-z]+$/is';
    const REGEXP_PASSWORD = '/^[a-zA-Z0-9#!@$%\\^&*()_\-+\.,]{5,20}$/';
    const ERROR_USER_NOT_EXIST = 1;
    const ERROR_PASSWORD_INCORRECT = 2;
    const ERROR_USER_BANNED = 3;
    const ERROR_USER_NOTACTIVE = 4;
    const ERROR_USER_DELETED = 5;

	function auth($login, $password);

	function isActive($uid);
	function isBanned($uid);
	function exists($uid);
	function existsLogin($login);
	function emailExists($email);
	function getEmail($uid);
	function getLogin($uid);
}
interface iRegistrableUserManager
{

	function addUser($login, $password, $email);
	function setPassword($uid, $password = null);
	function recoverPassword($email);

}
class AbstractUserManager
{
    const TABLE = 'user';
    const TABLE_REGISTRATION = 'user_registration';

	
	private $storage = null

	;
	function __construct()
	{
		$this->storage = Storage::create('__UserData',Config::getInstance()->user->proxy_time);
	}
	protected function storeUserData($uid, $data)
	{
		if(!is_numeric($uid) || $uid < 1) return; 
		$this->storage->set($uid, $data);
	}
	protected function flushUserData($uid)
	{
		if(!is_numeric($uid) || $uid < 1) return; 
		$this->storage->un_set($uid);
	}
	function getUserData($uid)
	{
		if(!is_numeric($uid) || $uid < 1) return; 
		$data = $this->storage->get($uid);

		if($data === false)
		{
			$r = DB::query("select * from ".self::TABLE." where id='".$uid."' limit 1");
			if(isset($r[0]))
			{
				$data = array();
				$data['login'] = $r[0]['login'];
				$data['email'] = $r[0]['email'];
				$data['last_login'] = $r[0]['last_login'];
				$data['date_joined'] = $r[0]['date_joined'];
				$this->storeUserData(0+$uid,$data);
			}
		}
		return $data;
	}

	function logout()
	{
		$this->flushUserData(User::get()->getId());
        Session::kill();
		Session::init();
		User::renew();
	}
	function checkLogin($login)
	{
        return preg_match(iUserManager::REGEXP_LOGIN, $login);
	}
	function checkPassord($password)
	{
		return preg_match(iUserManager::REGEXP_PASSWORD, $password);
	}
	function checkEmail($email)
	{
		return preg_match(iUserManager::REGEXP_EMAIL,$email);
	}

    // {{{ buildPasword
	protected function buildPassword($password, $dbSalt, $serverSalt)
	{
        return hash('md5', $dbSalt.$password.$serverSalt);
    }// }}}

    // {{{ generatePassword
    function generatePassword( $length = 8 )
    {
       $str='123456789QWERTYUIPASDFGHJKLZXCVBNM';
	   $len_1 = strlen($str)-1;
        $res='';
        for($i=0;$i<$length;$i++)
            $res.=$str[mt_rand(0,$len_1)];
        return $res;

    }//}}}

    // {{{ generateSalt
    protected function generateSalt(){
        return substr(md5(uniqid(rand(), true)),rand(0,15),16);
	}
	//}}}
}
class CasseaUserManager extends AbstractUserManager implements iUserManager,iRegistrableUserManager
{

    //{{{ auth
    function auth($login, $password)
    {
		//if (!preg_match(self::REGEXP_LOGIN, $login) || !preg_match(self::REGEXP_PASSWORD, $password))
		if(!$this->checkLogin($login) || !$this->checkPassord($password))
            return false;

        $r = DB::query('select *  from '.self::TABLE.' where login="'.$login.'" limit 1');
		if (count($r)!= 1 ) 
		{
			if(Config::getInstance()->user->registration_confirm)
				if(count(DB::query('select login from '.self::TABLE_REGISTRATION.' where login="'.$login.'" limit 1')))
					return self::ERROR_USER_NOTACTIVE;
			return self::ERROR_USER_NOT_EXIST;
		}
		$r = $r[0];
		if($r['id'] < 1) return self::ERROR_USER_NOTACTIVE;

        if ($r['state'] == 'ban') return self::ERROR_USER_BANNED;
        if ($r['state'] == 'delete') return self::ERROR_USER_DELETED;

        if ($r['password'] != $this->buildPassword($password, $r['salt'], Config::getInstance()->user->secret) )
            return self::ERROR_PASSWORD_INCORRECT;

		$store = array();
        $store['login'] = $r['login'];
        $store['email'] = $r['email'];
		$store['last_login'] = $r['last_login'];
		$store['date_joined'] = $r['date_joined'];
		$this->storeUserData(0+$r['id'],$store);

        Session::get()->setUserId(0+$r['id']);
		User::renew();
		DB::query("update ".self::TABLE." set last_login=now() where id='".$r['id']."' limit 1");

        return true;        
	}
	// }}}

	function addUser($login, $password, $email)
	{
		//if (!preg_match(self::REGEXP_LOGIN, $login) || !preg_match(self::REGEXP_PASSWORD, $password))
		if(!$this->checkLogin($login) || !$this->checkPassord($password))
			throw new UserManagerException('User '.$login.' has incorrect login or password');

		if(!$this->checkEmail($email))
			throw new UserManagerException('Email '.$email.' has incorrect email');

		if($this->existsLogin($login))
			throw new UserManagerException('User '.$login.' already exists');

		if($this->emailExists($email))
			throw new UserManagerException('Email '.$email.' already exists');

		// confirmation first
		if(Config::getInstance()->user->registration_confirm)
		{
			$regkey = md5(rand()*time());
			$config = Config::getInstance();
			DB::query('insert into '.self::TABLE_REGISTRATION.' (regkey, expires, login, email, password) values '.
				'("'.$regkey.'",adddate(now(), interval '.(0+$config->user->confirmation_ttl).' second),"'.$login.'","'.$email.'", "'.$password.'")');

			// message prepare
			$tpl_path = $config->get('root_dir').$config->user->mail_template_path;
			if(Language::currentName())
				$tpl_file = 'user_registration.'.Language::currentName().'.tpl';
			else 
				$tpl_file = 'user_registration.tpl';
			$subject = Language::getConst('emall_subject_registration','user');
			
			$p = new TemplateParams();
			$p->login = $login;
			$p->password = $password;
			$p->link = Controller::getInstance()->makeURL(
				Config::getInstance()->user->activation_page,
				array($regkey), 
				Config::getInstance()->user->activation_controller	); 
			$t = new Template($tpl_path, $tpl_file);
			$t->setParams($p);
			$body = $t->getHTML();


			$a=Mail::CreateMail();
			$a->setSubject( $subject );
			$a->setFromname($config->mail->default_from_name);
			$a->setFrom($config->mail->default_from);
			$a->toAdd($email);
			$a->Message( $body );
			
			$r = $a->send();
		}
		else
		{
			// add user instantly w/o confirmation
		
			$salt = $this->generateSalt();
			DB::query("insert into ".self::TABLE." set login = '".$login."', 
				email = '".$email."', 
				password = '".$this->buildPassword($password,$salt,Config::getInstance()->user->secret)."', 
				salt = '".$salt."',
				date_joined = now()	");
		}
	}

	function activate($regkey)
	{
        if (!$this->validateRegKey($regkey))
            new UserManagerException('Bad regkey format');

		$info = DB::query('select * from '.self::TABLE_REGISTRATION.' where regkey="'.$regkey.'" and expires >= now()');
        if (count($info) != 1)
			throw new UserManagerException('Wrong reg key');
		
		$info = $info[0];

		DB::query("insert into ".self::TABLE. " (login, email, password, salt, date_joined) values ('".
			$info['login']."', '".$info['email']."','".self::buildPassword($info['password'],($salt = $this->generateSalt()),
				Config::getInstance()->user->secret)."', '".$salt."', now())");

		DB::query("delete from ".self::TABLE_REGISTRATION." where regkey='".$regkey."' or expires < now()");

    }// }}}

    // {{{ setPassword
	function setPassword($uid, $password = null)
	{
		
		if(!is_numeric($uid) || $uid < 1) return;
		$password = Filter::filter($password,Filter::STRING_QUOTE_ENCODE);

        if ($password === null) $password = $this->generatePassword();
		
		$salt = $this->generateSalt();
		DB::query('update '.self::TABLE.' set password="'.
			$this->buildPassword($password, $salt, Config::getInstance()->user->secret).
			'", salt="'.$salt.'" where id='.$uid.' limit 1');
    }// }}}


    
	function ban($uid)
	{
		if(!is_numeric($uid)) return;
		DB::query('update '.self::TABLE.' set state="ban" where id="'.(0+$uid).'"');
    }
    
    /**
     * Разбанить пользователя по id  
     *
    */
	function unban($uid)
	{
		if(!is_numeric($uid)) return;
		DB::query('update '.self::TABLE.' set state="active" where id="'.(0+$uid).'"');
    }

     /**
     * Возвращаем забанен пользователь или нет  
     *
    */
	function isBanned($uid)
	{
		if(!is_numeric($uid)) return;
        $r = DB::query('select `state` from '.self::TABLE.' where id='.(0+$uid));
        return (isset($r[0]) && $r[0]['state'] == 'ban')?1:0;

    }
	function isActive($uid)
	{
		if(!is_numeric($uid)) return;
        $r = DB::query('select `state` from '.self::TABLE.' where id='.(0+$uid));
        return (isset($r[0]) && $r[0]['state'] == 'active')?1:0;

    }
	function exists($uid)
	{
		if(!is_numeric($uid)) return;

		$data = $this->getUserData($uid);
		if(isset($data['exists'])) return $data['exists'];

        $r  = DB::query('select id from '.self::TABLE.' where id="'.(0+$uid).'"');
		$data['exists'] = isset($r[0]);
		$this->storeUserData($uid,$data);
		return $data['exists'];
    }

	function existsLogin($login)
	{
		$login = Filter::filter($login,Filter::STRING_QUOTE_ENCODE);
        $r  = DB::query('select id from '.self::TABLE.' where login="'.$login.'" limit 1');
		if(count($r) != 1)
			return (bool)count(DB::query('select login from '.self::TABLE_REGISTRATION.' where login="'.$login.'"'));
        return true;
    }
	function emailExists($email)
	{
		$email = Filter::filter($email,Filter::STRING_QUOTE_ENCODE);
        $r  = DB::query('select id from '.self::TABLE.' where email="'.$email.'"');
		if(!count($r))
			return (bool)count(DB::query('select login from '.self::TABLE_REGISTRATION.' where email="'.$email.'"'));
        return true;
	}
	function getLogin($uid)
	{
		if(!is_numeric($uid)) return;

		$data = $this->getUserData($uid);
		if(isset($data['login'])) return $data['login'];


        $r  = DB::query('select login from '.self::TABLE.' where id="'.(0+$uid).'"');
		$data['login'] = isset($r[0])?$r[0]['login']:null;
		$this->storeUserData($uid,$data);
		return $data['login'];

	}
	function getIdByLogin($login)
	{
		if(!is_string($login)) return;
		$login = Filter::filter($login,Filter::STRING_QUOTE_ENCODE);
		$r = DB::query("select id from ".self::TABLE." where login='".$login."' limit 1");
		return isset($r[0])?$r[0]['id']:"";
	}
	function getLastLogin($uid)
	{
		if(!is_numeric($uid)) return;

		$data = $this->getUserData($uid);
		if(isset($data['last_login'])) return $data['last_login'];


        $r  = DB::query('select last_login from '.self::TABLE.' where id="'.(0+$uid).'"');
		$data['last_login'] = isset($r[0])?$r[0]['last_login']:null;
		$this->storeUserData($uid,$data);
		return $data['last_login'];
	}

	function getDateJoined($uid)
	{
		if(!is_numeric($uid)) return;

		$data = $this->getUserData($uid);
		if(isset($data['date_joined'])) return $data['date_joined'];


        $r  = DB::query('select date_joined from '.self::TABLE.' where id="'.(0+$uid).'"');
		$data['date_joined'] = isset($r[0])?$r[0]['date_joined']:null;
		$this->storeUserData($uid,$data);
		return $data['date_joined'];
	}
	function getEmail($uid)
	{
		if(!is_numeric($uid)) return;

		$data = $this->getUserData($uid);
		if(isset($data['email'])) return $data['email'];

        $r  = DB::query('select email from '.self::TABLE.' where id="'.(0+$uid).'"');
		$data['email'] = isset($r[0])?$r[0]['email']:null;
		$this->storeUserData($uid,$data);
		return $data['email'];
	}
	function setEmail($uid,$email)
	{
		if(!is_numeric($uid) || $uid < 1) return;

		$data = $this->getUserData($uid);

        $r  = DB::query('update '.self::TABLE.' set email="'.$email.'" where id="'.(0+$uid).'" limit 1');
		$data['email'] = $email;
		$this->storeUserData($uid,$data);
		
	}

	function recoverPassword($email)
	{

		$r = DB::query("select id,login from ".self::TABLE." where email='".$email."' limit 1");
		if(count($r) != 1)
			throw new UserManagerException("User with email '".$email."' not found");
		$uid = $r[0]['id'];
		$login = $r[0]['login'];

        $new_password = $this->generatePassword(8);
		$config = Config::getInstance();

        // message prepare
		$tpl_path = $config->get('root_dir').$config->user->mail_template_path;
		if(Language::currentName())
			$tpl_file = 'user_password_recovery.'.Language::currentName().'.tpl';
		else 
			$tpl_file = 'user_password_recovery.tpl';
        $subject = Language::getConst('emali_subject_password_recovery','user');

        $p = new TemplateParams();
		// TODO: $p->greating = $this->firstname.' '.$this->lastname;
		$p->new_password = $new_password;
		$p->login = $login;
        $t = new Template($tpl_path, $tpl_file);
        $t->setParams($p);
        $body = $t->getHTML();

        

        $a=Mail::CreateMail();
        $a->setSubject( $subject);
        $a->setFromname($config->mail->default_from_name);
        $a->setFrom($config->mail->default_from);
        $a->toAdd($email);
        $a->Message( $body );

        $r = $a->send();
        $this->setPassword($uid, $new_password);

    }

    // {{{ validateRegKey
    private function validateRegKey($str){
        return preg_match('#^[a-zA-Z0-9]{32}$#', $str);
    }// }}}
}
?>
