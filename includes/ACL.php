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


// $Id$
// {{{ ACL
class ACL
{
    const ACL_TABLE = "acl";

    static protected 
        $groups  = null
    ;
	static private $guest_group = "guest";

    static function init()
    {
        if(Config::getInstance()->acl->cache_groups && is_array($g = Storage::create('acl_groups')->get(User::get()->getId())) &&!empty($g))
            self::$groups = $g;
        else
		{
			if(User::get()->getId() == User::GUEST)
				self::$groups[] = self::$guest_group;
			else
			{
				$res = DB::query("select groups from ".self::ACL_TABLE." where user_id='".User::get()->getId()."' limit 1");
				if(count($res))
					self::$groups = array_filter(array_map('trim',explode(":",$res[0]['groups'])));
				else 
					// there's no group for user.
					self::$groups = array();
			}

            if(Config::getInstance()->acl->cache_groups)
                Storage::create('acl_groups')->set(User::get()->getId(),self::$groups);
        }
    }

    static function getGroups()
    {
        $groups=array();
        $res = DB::query("select groups from ".self::ACL_TABLE);
        for($i=0;$i<count($res);$i++)
            $groups =array_merge($groups,array_map('trim',explode(":",$res[$i]['groups'])));
		$groups  = array_unique($groups);
		sort($groups);
		return $groups;
    }
    
    static function getUserByGroups($group=null)
    {
        $group=Filter::filter($group,'string_quote_encode');
        if($group===null)
            return $res = DB::query("select ".self::ACL_TABLE.".groups,".AbstractUserManager::TABLE.".login from ".self::ACL_TABLE." left join ".AbstractUserManager::TABLE." on ".self::ACL_TABLE.".user_id=".AbstractUserManager::TABLE.".id order by ".self::ACL_TABLE.".groups");
        else
            return $res = DB::query("select user_id from ".self::ACL_TABLE." where groups REGEXP  '(^|:)".$group."($|:)'");
    }
    
    static function addUserToGroup($id,$group)
    {
        $group=Filter::filter($group,'string_quote_encode');
        if(!count($res = DB::query("select user_id,groups from ".self::ACL_TABLE." where user_id=".$id)))
            return DB::query("insert into ".self::ACL_TABLE." set user_id='".(int)$id."', groups='".$group."'");
        else
            DB::query("update ".self::ACL_TABLE." set groups='".(empty($res[0]['groups'])?$group:($res[0]['groups'].":".$group)).
                "' where user_id='".$res[0]['user_id']."' and groups not REGEXP '(^|:)".$group."($|:)'");
            
    }
    
    static function delUserFromGroup($id,$group)
    {
        $group=Filter::filter($group,'string_quote_encode');
        $id = Filter::filter($id,Filter::INT);

        if(count($res = DB::query("select user_id, groups from ".self::ACL_TABLE." where user_id=".$id." and groups regexp '(^|:)".$group."($|:)'")))
            DB::query("update ".self::ACL_TABLE." set groups='".implode(":",array_diff(explode(":",$res[0]['groups']),array($group))).
            "' where user_id='".$id."' limit 1");
    }



    static function check($allows = "", $denies = "", $delimiter = ",")
    {
        if(!Config::getInstance()->acl->use_acl) return true;
        if( self::$groups === null)
            self::init();

        if(empty($denies) && empty($allows))
            return true;

        if(!empty($denies))
            if(($_d = explode($delimiter,$denies)) && count(array_intersect($_d,self::$groups))) return false;
            else return true;

        if(!empty($allows))
            if(($_a = explode($delimiter,$allows)) && count(array_intersect($_a,self::$groups))) return true;
            else return false;

        /*if(!empty($denies) || !empty($allows))
            return false;*/
        return true;

    }
	static function in($group) // in('admin') or in(array('admin','admin2'))
	{
		if(empty($group)) return false;
		
		if(is_string($group)) $group = array($group);

		return (bool)count(array_intersect($group,self::$groups));
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
	static function flushCache($user_id)
	{
        if(Config::getInstance()->acl->cache_groups)
            Storage::create('acl_groups')->un_set($user_id);
	}
}
// }}}
?>
