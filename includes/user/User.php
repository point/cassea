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

require_once("Profile.php");
//{{{ User
/**
* @author       billy
*/
class User
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


    //{{{ __construct
    /**
     *
     *
     */
    private function __construct( ){
        if ( !is_int($uid =  Session::get()->getUserId()) || $uid <= 0 ) return;
        $this->id = $uid;

		if(($data = UserManager::get()->getUserData($this->id)) === false)
			throw new Exception("User data for uid '".$this->id."' not found");
		
		$this->login = $data['login'];
		$this->email = $data['email'];

		$this->profile = new Profile($this->id);
    }//}}}

	static function renew()
	{
		self::$instance = null;
		User::get();
	}

    // }}}

    //{{{ get
    /**
    * @return   User
    */
    public static function get()
    {
        if (!is_object(self::$instance))
                self::$instance = new User();
        return self::$instance;
    }// }}}

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
		return $this->profile;
	}
	// }}}

	public function isGuest()
	{
		return $this->id == self::GUEST;
	}

}// }}}

?>
