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


//{{{ DatabaseSession
/**
* @author       billy
*/
class DatabaseSession extends SessionBase
{

    const TABLE = 'user_session';

    //{{{ getServerSession
    /**
    * @return   Object
    */
    public function getServerSession($sid)
    {
        $sql = "select user_id as user, user_ip as ip, cast  from " . self::TABLE . " where id='" . $sid . "' LIMIT 1" ;
        $res = DB::query($sql);
        return (count($res) == 1)?$res[0]:null;
    }// }}}
    
    //{{{ updateSession
    /**
    * @param    String $someparam    
    * @return   void
    */
    public function updateSession($param)
    {
        parent::updateSession($param);
        $sql = 'replace into '. self::TABLE.'( id, user_id, user_ip,  cast, time) values '.
            '( "'.$this->id.'", '.$param['user'].', "'.$param['ip'].'", "'.$param['cast'].'", "'.time().'" )'; 
        DB::query($sql);        
    }// }}}
    
    //{{{ setUserId
    /**
    * @return   boolean
    */
    public function setUserId($id)
    {
        if (!parent::setUserId($id)) return false;
        $sql = 'update '.self::TABLE.' set user_id = '.$id.' where id ="'. $this->id.'"';
        DB::query($sql);
    }// }}}
    
    //{{{ kill
    /**
    * @return   void
    */
    public function kill()
    {
        $r = DB::query( "delete from " . self::TABLE . ' where id = "'.$this->id.'"');
        parent::kill();
    }// }}}
    
    //{{{ deleteExpired
    /**
    * @return   int
    */
    public function deleteExpired()
    {
        $expiry_time = time() - Config::getInstance()->session->length;
        $sql = 'delete from ' . self::TABLE . ' where time < '.$expiry_time;
        DB::query($sql);
        return DB::getMysqli()->affected_rows;
    }// }}}
    
    //{{{ getOnlineUsers
    /**
    * @return   array
    */
    public function getOnlineUsers( )
    {
        $r = DB::query('select distinct user_id from '.self::TABLE.' where user_id != '.User::GUEST );
        for ($i =0, $c = count($r); $i < $c; $i++)
            $r[$i] = $r[$i]['user_id'];
        return $r;
    }// }}}
}// }}}
