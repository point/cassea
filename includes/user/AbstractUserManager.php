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

// $Id:$

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

	// {{{
	function comparePassword($uid,$password)
	{
        $r = DB::query('select  password, salt from '.self::TABLE.' where id="'.$uid.'"');
		if(!isset($r[0])) return false;
        $r =$r[0];
		return $r['password'] == $this->buildPassword($password, $r['salt'], Config::getInstance()->user->secret); 
    } // }}}

	// Need for console functions
	function getUsersList(){
		return DB::query('select * from '.self::TABLE.'');
    }
    function getNotConfirmed(){
       return DB::query('select * from '.self::TABLE_REGISTRATION.' order by expires');
	}

}
