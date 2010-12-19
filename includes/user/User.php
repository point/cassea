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

	/**
    * @var      int
    */
    private $id = User::GUEST;
    /**
    * @var      String
    */
    private $login = 'Guest';
    /**
    * @var      int
    */
    private $email = 'guest@example.com';
    /**
    * @var      Profile
    */
    private $profile;
    /**
    * @var      User
    */
    private static $instance;

	protected 
		$last_login = null,
		$date_joined = null,
		$single_access_token = null, //for auth at RSS/Atom request
		$one_time_token = null //password-changer, etc
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
		if((int)$user_id < 1)
			throw new UserException("User id could not be negative");
		return new self($user_id);
    }// }}}


    //{{{ __construct
    /**
     *
     *
     */
	protected function __construct( $user_id = null)
	{
		if($user_id === null) //init session and find user
			$this->id = Session::init();
    }//}}}

	function loadUser($user_id)
	{
		$this->login = $data['login'];
		$this->email = $data['email'];
		$this->last_login = $data['last_login'];
		$this->date_joined = $data['date_joined'];
	}
	
	// {{{
	static function renew()
	{
		self::$instance = null;
		User::get();
	}
    // }}}


    //{{{ getId
    /**
    * @return   int
    */
    public function getId()
    {
        return $this->id;

    }// }}}
    
    //{{{ getLogin
    /**
    * @return   string
    */
    public function getLogin()
    {
        return $this->login;
    }// }}}

    //{{{ getEmail
    /**
    * @return   string
    */
    public function getEmail()
    {
        return $this->email;
    }// }}}
    
    //{{{ getProfile
    /**
    * @return   Profile
    */
    public function getProfile()
    {
		if(isset($this->profile))
			return $this->profile;
		else return $this->profile = Profile::get($this->id);
	}
	// }}}

	public function isGuest()
	{
		return $this->id == self::GUEST;
	}

	function getLastLogin()
	{
		return $this->last_login;
	}
	function getDateJoined()
	{
		return $this->date_joined;
	}
	function findBySingleAccessToken($token_key){}
}// }}}

?>
