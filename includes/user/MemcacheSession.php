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


//{{{ MemcacheSession
/**
* @author       billy
*/
class MemcacheSession extends SessionBase
{

    private $storage = null; 
    
    //{{{ getServerSession
    /**
    * @param    string Client Sid 
    * @return   mixed Bool false is session no exists, else array with session params;
    */
    public function getServerSession($sid)
    {
        if(empty($sid)) return false;
        $this->storage = Storage::create($this->getStorageName($sid), 0+Config::getInstance()->session->length);
        if (!isset($this->storage['cast']))
            $ss = false;
        else{
            $ss = array();
            $ss['ip'] = $this->storage['ip'];
            $ss['cast'] = $this->storage['cast'];
            $ss['user'] = $this->storage['user'];
        }
        unset($this->storage);
        return $ss;
    }// }}}
    
    //{{{ updateSession
    /**
    * @param    array $param
    * @return   void
    */
    public function updateSession($param)
    {
        parent::updateSession($param);
        $this->storage = Storage::create($this->getStorageName($this->id), 0+Config::getInstance()->session->length);
        foreach($param as $k => $v)
            $this->storage[$k] = $v;
        unset($this->storage);
    }// }}}
    
    //{{{ setUserId
    /**
    * @return   boolean
    */
    public function setUserId($id)
    {
        if (!parent::setUserId($id)) return false;
        $this->storage['user'] = $this->userId;
    }// }}}
    
    //{{{ kill
    /**
    * @return   void
    */
    public function kill()
    {
        unset($this->storage['cast']);
        parent::kill();
    }// }}}
    
    private function getStorageName($sid){
        return 'session:'.$sid;
    }
}// }}}

?>
