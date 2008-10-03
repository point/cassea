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


//TODO: STUB!
//TODO: DELETE $_GET!!!!!!!!
/*
* 
* Session class
* This class realised work with sessions through cookies if user accept last, or GET vars otherwise. 
*
* @author billy
*
*/
// $Id: Session.php 465 2007-11-22 17:46:33Z billy $
//

define ('SESSION_NAME', "SID");

define ('SESSION_TABLE', "user_session");

/**
* 
* Session class
* 
* This class realised work with sessions through cookies if user accept last, or GET vars otherwise. 
*
* Ќа будущее. ѕараметр cast служит дл€ того чтобы делать "слепок" броузера пользовател€.
* <code>
*   $unsec_cast =   HTTP_accept . HTTP_ACCEPT_chatset . HTTP_ACCEPT_language.
*                   HTTP_USER_AGENT;
*   $c1 = ..;// половина полуившейс€ строки
*   $c2 = ..;// втора€ половина получившейс€ строки
*   $cast = md5($c1).$md5($c2);
* </code>
* Ёто может быть дл€ повышенной безопасности или дл€ пользователей без кукисов.
*
*  роме того сохран€етс€ переменна€ REMOTE_PORT дл€ случа€ чтобы каждый раз провер€ть,
* что порт пользовател€ увеличиваетс€.
*
* @package Core
* @subpackage user
* @author       billy
* @version $Rev: 465 $
* @since 0.2 2007-03-14
* @link http://webaroundyou.com Home page
*/
class Session
{

    /**
    * ƒлительность сесси
    * @var      int
    */
    const LENGTH = 3600;

	protected
        /**
        * id текущей сессии
        * @var      int
        */
        $id = null,
        /**
        * ip пользовател€
        * @var      int
        */
        $remote_ip,
        /**
        * id пользовател€ движка
        * @var      int
        */
        $user_id,

        /**
        * ¬рем€ начала сесси
        * @var      int
        */
        $start,


        /**
        * ¬рем€ последнего обновлени€
        * @var      int
        */
		$time;

	private static $instance = null;

       

    /** {{{ get_instance
    * @return   object
    */
    static function getInstance()
    {
		if(!isset(self::$instance))
		{
			self::$instance = new Session();
			self::$instance->init();
		}
		return self::$instance;
    }// }}}
    

    /** {{{ init
    * »нициализирует сессию.
    * 
    * ¬осстанавливает если есть. —оздает естли нет.
    * 
    * —оздает пользовател€.
    * @return   boolean
    */
    function init()
    {
        $this->remote_ip = Session::_get_ip();
        $user_sid = $this->get_client_session();
        
        $core_session = $this->get_core_session($user_sid);

        // сесси€ есть в куках и есть в базе
        if ( is_array($core_session) && $this->remote_ip == $core_session['user_ip'])
            $this->update_session($core_session);
        else 
            //new session
            $this->update_session();

        $this->delete_expired();
    }// }}}


    /** {{{ getId
    */
    function getId()
	{
		return $this->id;
	}
	// }}}

    /** {{{ get_client_session
    * Get current Session id from COOKIE or GET user
    * 
    * @return   mixed session id if set , else null
    */
    function get_client_session()
    {
        $sid = null;
	if(isset($_COOKIE[SESSION_NAME]))
            $sid = $_COOKIE[SESSION_NAME];
		
        elseif(!$this->is_accept_cookies() && isset($_GET[SESSION_NAME]))
            $sid = $_GET[SESSION_NAME];
        
        return   preg_match('/^[A-Za-z0-9]{32}$/',$sid) ? $sid : null;
        
    }// }}}
    

    /** {{{ get_core_session
    * Return information about session 
    *  
    * Search information about session with given id.
    * Return null if session not found
    *
    * @return   string
    */
    function get_core_session( $user_sid )
    {
        if ( $user_sid === null ) return;

        $sql = "select * from " . SESSION_TABLE . " where id='" . $user_sid . "' LIMIT 1" ;
        $res = DB::query($sql);
        return (count($res) == 1)?$res[0]:null;
          
    }// }}}
    
    /** {{{  update_session
    * 
    *
    * @return   void
    */
    function update_session($param = null)
    {
        // session update time
        $port = $_SERVER['REMOTE_PORT'];
        $cast = $this->make_cast();
        $this->time = time();

        //create new session
        if($param === null){
            $this->id = md5(uniqid(microtime()) . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'] . mt_rand(100000,999999));		
            $this->user_id = -2;
            $this->start = time();
        }
        // update exists session
        else{
            $this->id = $param['id'];
            $this->user_id = $param['user_id'];
            $this->start = $param['start'];
        }

        if ($this->is_accept_cookies() || 1){
            $succ =  setcookie(SESSION_NAME, $this->id, time() + Session::LENGTH*100,"/");// ,$_SERVER['HTTP_HOST']);
            if ( !$succ ) print_r('header already modified');
            //print_r($_SERVER['HTTP_HOST']);
            //print_r($this->id);

        }

        else
            $_GET[SESSION_NAME] = $this->id;

        $sql = 'replace into '. SESSION_TABLE.'( id, user_id, user_ip, user_port, cast, start, time) values '.
            '( "'.$this->id.'", '.$this->user_id.', "'.$this->remote_ip.'", "'.$port.'", "'.$cast.'", '.$this->start.', '.$this->time.' )'; 
        DB::query($sql);        

    }// }}}
    

     /** {{{ delete
    * @return   boolean
    */
    function kill()
    {
        // set cookie expired time in past
        setcookie(SESSION_NAME, "", time() - $this->session_length , "/");

        // delete session fron database
        $db = CDBFactory::get_instance();
        $sql = "delete from " . SESSION_TABLE . ' where id = "'.$this->id.'"';
        $db->query($sql);

        $this->id = null;
        $this->remote_ip = null;
        $this->user_id = null;
        return true;
    }// }}}
    

    /** {{{ sync
    * @return   void
    */
    function sync()
    {
        // TODO: implement
        // проверить вызывающего
        $u = &User::get();
        $db = &DB::get();       
        $sql = 'update '.SESSION_TABLE.' set user_id = '.$u->get_id().' where id ="'. $this->id.'"';
        $db->query($sql);
          
     }// }}}
    

     /** {{{ delete_expired
    *
    * Function delete expired session 
    *
    * @return  int count of deleted session
    */
    function delete_expired()
    {
        $expiry_time = time() - Session::LENGTH;

        $sql = 'delete from ' . SESSION_TABLE . ' where time < '.$expiry_time;

        DB::query($sql);

        return DB::getMysqli()->affected_rows;
    }// }}}
    

    /** {{{ is_accept_cookies
    * Check if user accept cookie
    * 
    * @return   boolean
    */
    function is_accept_cookies()
    {
    	$r = (isset($_SERVER['HTTP_COOKIE2']) || isset($_SERVER['HTTP_COOKIE']));
//    	if (!$r) print_pre($_SERVER);
    	return true;
     }// }}}


    /** {{{ make_cast
    * make cast from $_SERVER variables
    * 
    * @return   string(64)
    */
    function make_cast()
    {
        // TODO:implement
    }// }}}

     
    // {{{ _get_ip
    /**
    * get client ip.
    * Return string like "151.2.41.55, 192.168.0,4" 
    *
    * @return string
    */
    function _get_ip(){
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
        if ($strRemoteIP != $strIP) {
            $strIP = $strRemoteIP . ', ' . $strIP;
        }
        return $strIP;
    }// }}}


    // {{{ get_online_users
    /**
     * Return array of users id witch online now
     *
     * @return array
     */
    function get_online_users(){
        $sql = 'select user_id from '.SESSION_TABLE.' where user_id != '.ANONYMOUS;
        $res = DB::query( $sql );
        $r = array();
        for ($i = 0; $i < count($res); $i++)
            $r[] = $res[$i]['user_id'];
        return $r;
    }// }}}
}
?>
