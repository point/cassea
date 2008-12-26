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


// $Id$
// {{{ ACL
class ACL
{
    const ACL_TABLE = "acl";

    static protected 
        $groups  = array()
    ;

    static function init()
    {
        if(Config::getInstance()->acl->cache_groups && $g = Storage::create('acl_groups')->get(User::get()->getId()))
            self::$groups = $g;
        else
        {
            $res = DB::query("select groups from ".self::ACL_TABLE." where user_id='".User::get()->getId()."' limit 1");
            if(count($res))
                self::$groups = array_map('trim',explode(":",$res[0]['groups']));

            if(Config::getInstance()->acl->cache_groups)
                Storage::create('acl_groups')->set(User::get()->getId(),self::$groups);
        }
    }
    static function check($allows = "", $denies = "", $delimiter = ",")
    {
        if(!Config::getInstance()->acl->use_acl) return true;
        if(empty(self::$groups))
            self::init();
        //var_dump(self::$groups);

        if(empty($denies) && empty($allows))
            return true;

        if(!empty($denies) &&
           ($_d = explode($delimiter,$denies)) && count(array_intersect(array_map('trim',$_d),self::$groups))) return false;

        if(!empty($allows) &&  
           ($_a = explode($delimiter,$allows)) && count(array_intersect(array_map('trim',$_a),self::$groups))) return true;

        if(!empty($denies) || !empty($allows))
            return false;
        return true;

    }
    static function add($user_id = null, $groups = array())
    {
        if(!is_numeric($user_id) || empty($groups) || !is_array($groups)) return;
        
        DB::query("insert into ".self::ACL_TABLE." set id='".(int)$user_id."', groups='".implode(":",$groups)."' on duplicate key update groups='".implode(":",$groups)."'");
        if(Config::getInstance()->acl->cache_groups)
            Storage::create('acl_groups')->un_set($user_id);

    }
    static function delete($user_id = null)
    {
        if(!is_numeric($user_id)) return;

        DB::query("delete from ".self::ACL_TABLE." where id='".(int)$user_id."' limit 1");
        if(Config::getInstance()->acl->cache_groups)
            Storage::create('acl_groups')->un_set($user_id);
    }
}
// }}}
?>
