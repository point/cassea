<?php
/*- vim:expandtab:shiftwidth=4:tabstop=4: 
{{{ LICENSE  
* Copyright (c) 2008, Cassea Project
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
class User
{
    const GUEST = -1;

    const TABLE = 'user';
    const TABLE_BAN = 'user_ban';
    const TABLE_REGISTRATION = 'user_registration';

    //const REGEXP_LOGIN ='#^[a-zA-Z0-9_\\-.]{5,20}$#';
    const REGEXP_LOGIN ='/^[a-z0-9&\'\.\-_\+]+@[a-z0-9\-]+\.([a-z0-9\-]+\.)*?[a-z]+$/is'; 
    const REGEXP_EMAIL = '/^[a-z0-9&\'\.\-_\+]+@[a-z0-9\-]+\.([a-z0-9\-]+\.)*?[a-z]+$/is';
    const REGEXP_PASSWORD = '/^[a-zA-Z0-9#!@$%\\^&*()_\\-+.,]{5,20}$/';
    const ERROR_USER_NOT_EXIST = 1;
    const ERROR_PASSWORD_INCORRECT = 2;
    const ERROR_USER_BANNED = 3;
    const ERROR_USER_NOTACTIVE = 4;
    const ERROR_USER_DELETED = 5;

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
    private static $user;

    private $storage;

    //{{{ __construct
    /**
     *
     *
     */
    private function __construct( ){
        if ( !is_int($uid =  Session::get()->getUserId()) || $uid <= 0 ) return;
        $this->id = $uid;
        
        if (Config::getInstance()->user->proxy_time){
            $this->storage = Storage::create('__UserData'.$this->id,Config::getInstance()->user->proxy_time);

            if (!$this->restoreUserData()){
                $d = $this->getUserData();
                $this->login = $d['login'];
                $this->email = $d['email'];
                $this->storeUserData($d);
            }
        }
        else{
            $d = $this->getUserData();
            $this->login = $d['login'];
            $this->email = $d['email'];
        }
    }//}}}

    // {{{  ===User Proxy===

    //{{{ restoreUserData
    /**
     *
     * @return bool
     */
    private function restoreUserData(){
        if (!isset($this->storage['login'])) return false;

        $this->login = $this->storage['login'];
        $this->email = $this->storage['email'];    
//        print_pre('USer data from Storage:');
//        print_pre($this->login);
//        print_pre($this->email);
         
        return true;
    }// }}}

    private function getUserData(){
        $res= DB::query('select login, email from '.User::TABLE. ' where id='.$this->id);
        $res = array_pop($res);
        if (!is_Array($res)) throw new Exception('Unable find user with id '. $this->id);
//        print_pre('USer data from DB:');
//        print_pre($res);
        return $res;
    }

    private function storeUserData($arr){
//        print_pre('Store data in Storage');
//        print_pre($arr);
        foreach ($arr as $k => $v){
            $this->storage->set( $k, $v);
        }
    }

    // }}}

    //{{{ get
    /**
    * @return   User
    */
    public static function get()
    {
        if (!is_object(self::$user))
                self::$user = new User();
        return self::$user;
    }// }}}

     //{{{ dropUser
    /**
    * @return   void
    */
    public static function dropUser()
    {
        // проверить вызывающего. 
        // Функция должна вызыватся тoлько из User:auth()
        $ar = debug_backtrace();$caller = $ar[1];
        if ( strtoupper($caller['class']) == 'SESSIONBASE' && strtoupper($caller['function']) == 'RESTORESESSION') self::$user = null;
        else throw new Exception('Bad Caller');
    }// }}}
    
    //{{{ auth
    /**
    * @return   boolean
    */
    public function auth($login, $password)
    {
        if (!preg_match(User::REGEXP_LOGIN, $login) && !preg_match(User::REGEXP_PASSWORD, $password))
            return false;
        // nonactive
        $sql = 'select login from '.User::TABLE_REGISTRATION.' where login="'.$login.'"';
        $r = DB::query($sql);
        if (count($r) == 1) return User::ERROR_USER_NOTACTIVE;
        $r = DB::query('select id, login, email, password, sold, state from '.User::TABLE.' where login="'.$login.'"');
        if (count($r)!= 1 ) return User::ERROR_USER_NOT_EXIST;
        $r = $r[0];

        if ($r['state'] == 'ban') return User::ERROR_USER_BANNED;
        if ($r['state'] == 'delete') return User::ERROR_USER_DELETED;

        if ($r['password'] != self::buildPasword($password, $r['sold'], Config::getInstance()->user->secret) )
            return User::ERROR_PASSWORD_INCORRECT;

        $this->id = 0+$r['id'];
        $this->login = $r['login'];
        $this->email = $r['email'];

        Session::kill();
        Session::init();
        Session::get()->setUserId($this->id);
        return true;        
    }// }}}

    // {{{ buildPasword
    static function buildPasword($password, $dbSold, $serverSold){
        $needed = hash('md5', $dbSold.$password.$serverSold);
        return $needed; 
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
       // TODO: implement

    }// }}}


    // {{{ checkPassword
    /**
     *
     *
     */
    public static function checkPassword($user_id, $password){
        $r = DB::query('select  password, sold from '.User::TABLE.' where id="'.$user_id.'"');
        $r =$r[0];
        $dbPassword = $r['password'];
        $dbSold = $r['sold'];
        $serverSold = Config::getInstance()->user->secret;
        
        //echo 
        $neededPassword = hash('md5', $dbSold.$password.$serverSold);
        

        return $dbPassword == $neededPassword;
    } // }}}

}// }}}

?>
