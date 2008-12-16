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


//{{{ SessionBase
/**
* Базовый Класса для Сессий
* @author       billy
*/
class SessionBase
{
   
    /**
    * Id сессси
    * @var      int
    */
    protected  $id;
    /**
    * @var      int
    */
    protected $userId;
    /**
    * @var      int
    */
    private $remoteIP;
    /**
    * @var      int
    */
    //private $cast;
    /**
    * @var      int
    */
    //private $time;
    
    //{{{ init
    /**
    * Инициализирует сесссию
    * @return   SessionBase
    */
    public function init()
    {
        $this->deleteExpired();
        $this->remoteIP  = $this->getUserIP();
        $cs = $this->getClientSession();
        $ss = $this->getServerSession($cs['sid']);


        $param = array();
        if (is_array($ss) && $this->remoteIP == $ss['ip'] && $cs['cast'] =  $ss['cast'] ){
            $param = $ss;
            $this->id = $cs['sid'];
        }
        else{
            $param['ip'] = $this->remoteIP;
            $param['cast'] = $cs['cast'];
            $param['user'] = User::GUEST;
            @$this->id =  md5(uniqid(microtime()) . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'] . mt_rand(100000,999999));
            
        }

        $this->updateSession($param);

        //echo 'Session id:'.$this->id;
        //print_r($param);
    }// }}}
    
    //{{{ getClientSession
    /**
    * @return   array
    */
    private function getClientSession()
    {
        $sid = isset($_COOKIE[Session::COOKIE_NAME]) && preg_match('/^[A-Za-z0-9]{32}$/',$_COOKIE[Session::COOKIE_NAME])? $_COOKIE[Session::COOKIE_NAME]:null;
        $client = array(
            'sid' => $sid,
            'cast' => $this->makeCast()
        );

        return $client;
    }// }}}
    
   // {{{ getUserIP
    /**
    * get client ip.
    * Return string like "151.2.41.55, 192.168.0,4" 
    *
    * @return string
    */
    private function getUserIP(){
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
        if(ip2long($strIP) === false)
            throw new Exception("Session: user ip is invalid");
        return $strIP;
    }// }}}
    
    //{{{ makeCast
    /**
    * @return   String
    */
    private function makeCast()
    {
        @$str = $_SERVER['HTTP_USER_AGENT'].$_SERVER['HTTP_ACCEPT'].$_SERVER['HTTP_ACCEPT_LANGUAGE'].$_SERVER['HTTP_ACCEPT_CHARSET'].$_SERVER['HTTP_ACCEPT_ENCODING'];
        return md5($str);
    }// }}}
    
    //{{{ getServerSession
    /**
    * @return   Object
    */
    protected function getServerSession($param)
    {
    }// }}}
    
    //{{{ updateSession
    /**
    * @param    String $sid
    * @return   void
    */
    protected function updateSession( $param)
    {
        $succ =  setcookie(Session::COOKIE_NAME, $this->id, time() + Config::getInstance()->session->cookie_length,"/");// ,$_SERVER['HTTP_HOST']);
        if ( !$succ )throw new Exception('COOKIE:Unable set Session ID. Probably  headers already sent.');
        $this->userId = 0 + $param['user'];
    }//}}}

    //{{{ kill
    /**
    * @return   void
    */
    public function kill(){
        // set cookie expired time in past
        setcookie(Session::COOKIE_NAME, $this->id, time() - Config::getInstance()->session->cookie_length,"/");
        $this->id = null;
        $this->userId = User::GUEST;
        $this->remoteIP = null;        
    }
    // }}}
    
    //{{{ deleteExpired
    /**
    * @return   int
    */
    protected function deleteExpired()
    {
    }// }}}
    
    //{{{ getSessionId
    /**
    * @return   String
    */
    public function getSessionId()
    {
        return $this->id;
    }// }}}
    
    //{{{ getUserId
    /**
    * @return   int
    */
    public function getUserId()
    {
        return $this->userId;
    }// }}}
    
    //{{{ setUserId
    /**
    * @return   boolean
    */
    public function setUserId( $id)
    {
        // проверить вызывающего.

        $this->userId = (int)$id;
        return true;
    }// }}}

}// }}}

?>
