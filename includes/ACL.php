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
/**
 * This file contains class for managing and check user's rights.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: ACL.php 163 2009-10-15 15:00:34Z point $
 * @package system
 * @since 
 */

//{{{ ACL
/** 
 * ACL is based on user and groups. Each user may consists in one or more groups. 
 * If user doesn't have any membership group, it will automatically droped into 
 * group "guest". 
 *
 * ACL can be applied to a single widget with "allow" or "deny" attribute:
 * <pre><code>
 * <WText allow="admin">Super secret text 1 </WText>
 * <WText deny="guest"> Super secret text 2 </WText>
 * <WText deny="user" allow="super">Super secret text 3 </WText>
 * </code></pre>
 * In theese cases, "text 1" would be shown, only if current user has membership in 
 * group "admin".
 * "text 2" would be shown only if current user doesn't belong to group "guest".
 * "text 3" would be shown only if current user doesn't belong to group "user" and 
 * belong to "super".
 *
 * This kind of checks may be applied not only for simple widgets, like WText, but 
 * for composite too. Like {@link WBlock}, {@link WRoll}, etc. All inner widgets 
 * won't be shown.
 *
 * Group name may contain alphnumeric characters only. By default 
 * they are stored in `acl` table, in comma-separated view per one user.
 *
 * Additionally, <code>allow</code> and <code>deny</code> contstructions are supported in
 * root section of a page, in included page, in extended page, 
 * in include block (in case of including not whole page).
 *
 * In order to have program access to groups checker, use {@link in} method.
 *
 * Groups are managing by direct `acl` table updates, or by console command family 
 * <code>console group</code>
 *
 * You can cache parsed groups in {@link Storage} by defining config fiag 
 * <code>acl.use = 0</code>
 *
 * You can switch off acl subsystem to eliminate overhead on using DB by defining config flag
 * <code>acl.cache_groups=0</code>
 */
class ACL
{
	/**
	 * Name of a table, where groups are stored
	 */
    const ACL_TABLE = "acl";

	/**
	 * @var array Parsed groups from DB
	 */
    static protected 
        $groups  = null
    ;
	/**
	 * @var string The name of a group for not-authenticated user
	 */
	static private $guest_group = "guest";
	
	//{{{ init
	/**
	 * Init method for lazy-loading groups.
	 * Called only if "acl.use" config flag has true value.
	 *
	 * To reduce DB calls set "acl.cache_groups" config flag to true value. 
	 * Groups for particular user will be strored in {@link Storage} with default ttl.
	 *
	 * @param null
	 * @return null
	 */
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
	//}}}

	//{{{ getGroups
	/**
	 * Returns sorted array of unique groups for current user. 
	 *
	 * Used for console subsystem to display groups.
	 *
	 * @param null
	 * @return array array of groups
	 */
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
	//}}}

	//{{{getUsers
	/**
	 * Returns associative array of array("group"=>array("login1","login2")),
	 * ordered by groups
	 * Used for console subsystem.
	 *
	 * @return array
	 */
    static function getUsers()
    {
        $ret= array();
        $r = DB::query("select ".self::ACL_TABLE.".groups,".AbstractUserManager::TABLE.".login 
            from ".self::ACL_TABLE." left join ".AbstractUserManager::TABLE." 
            on ".self::ACL_TABLE.".user_id=".AbstractUserManager::TABLE.".id order by ".self::ACL_TABLE.".groups"); 

        foreach(self::getGroups() as $group)
            foreach($r as $l)
                if(preg_match('/(^|:)'.$group.'($|:)/', $l['groups']))
                    $ret[$group][]= $l['login'];
        return $ret;
    }//}}}

	//{{{ addUser
	/**
	 * Adds given user id  to given group.
	 *
	 * Used by console subsystem.
	 *
	 * @param int user id 
	 * @param string group name. Alphanumeric symbols only. Case sensitive.
	 * @return null
	 * @throws ACLException if 'group' or 'id' parameter has incorrect format.
	 * @throws ACLException if trying to add group for nonexistent user.
	 */
    static function addUser($id,$group)
    {
        $group=trim(Filter::apply($group,'string_quote_encode'));
        $id = Filter::apply($id,Filter::INT);

		if(empty($group))
			throw new ACLException("Parameter 'group' has incorrect format");

		if(empty($id))
			throw new ACLException("Parameter 'id' has incorrect format");

		//check whenever to insert or update. Caching the result to catch selected gropus
        if(!count($res = DB::query("select user_id,groups from ".self::ACL_TABLE." where user_id=".$id)))
            DB::query("insert into ".self::ACL_TABLE." set user_id='".(int)$id."', groups='".$group."'");
        else
			try{
            DB::query("update ".self::ACL_TABLE." set groups='".(empty($res[0]['groups'])?$group:($res[0]['groups'].":".$group)).
                "' where user_id='".$res[0]['user_id']."' and groups not REGEXP '(^|:)".$group."($|:)'");
			}
		catch(DBException $e)
		{	throw new ACLException("Trying to add group for nonexistent user or unrecoverable DB error"); }
            
		self::flushCache($id);
	}
	//}}}

	//{{{ delUser
	/**
	 * Delete given group from the list of user's groups. 
	 *
	 * Used by console subsystem.
	 *
	 * @param int user id 
	 * @param string group name. Alphanumeric symbols only. Case sensitive.
	 * @return null
	 * @throws ACLException if 'group' or 'id' parameter has incorrect format.
	 */
	static function delUser($id,$group)
    {
		$group=Filter::apply($group,'string_quote_encode');
        $id = Filter::apply($id,Filter::INT);

		if(empty($group))
			throw new ACLException("Parameter 'group' has incorrect format");

		if(empty($id))
			throw new ACLException("Parameter 'id' has incorrect format");

		if(count($res = DB::query("select user_id, groups from ".self::ACL_TABLE." where user_id=".$id.
                " and groups regexp '(^|:)".$group."($|:)'"))){
            $groups = implode(":",array_diff(explode(":",$res[0]['groups']),array($group)));
            if (!empty($groups)) $sql = "update ".self::ACL_TABLE." set groups='".$groups."' where user_id='".$id."' limit 1";
            else $sql ="delete from ".self::ACL_TABLE." where user_id='".$id."' limit 1";
            DB::query($sql);
        }
        ACL::flushCache($id);
    }
	//}}}

	//{{{ check
	/**
	 * Check whenever to allow or deny access to some resource. 
	 * Most frequently this method is used to define to show or not the widget.
	 * Example of using:
	 * <pre><code>
	 * ACL::check("admin,  authors","guest")
	 * </code></pre>
	 * Or in XML file
	 * <pre><code>
	 * <WBlock allow="admin, authors" deny="guest"/>
	 * </code></pre>
	 * If current user belongs to group guest return false without other checks.
	 * If not, checking if user belongs to one of groups: "admin" or "authors".
	 *
	 * @param string with groups, for which access allowed. Groups are coma-separated.
	 * @param string with groups, for which access is prohibited. Theese groups are also coma-separated.
	 * @param string optional delimeter of groups in $allows and $denies parameters.
	 * @return boolean true in case of access allowed or not using acl or $allows or $denies is empty. False if access denied.
	 * @see in
	 * @see ACL
	 */
    static function check($allows = "", $denies = "", $delimiter = ",")
    {
        if(!Config::getInstance()->acl->use) return true;
        if( self::$groups === null)
            self::init();

        if(empty($denies) && empty($allows))
            return true;

        if(!empty($denies))
            if(($_d = explode($delimiter,$denies)) && count(array_intersect($_d,self::$groups))) return false;
			//else return true;

        if(!empty($allows))
            if(($_a = explode($delimiter,$allows)) && count(array_intersect($_a,self::$groups))) return true;
            else return false;

        return true;

    }
	//}}}
	
	//{{{ in
	/**
	 * Check if current user belong to one of given groups
	 * Example of using:
	 * <pre><code>
	 * ACL::in("admin");
	 * </code></pre>
	 * Or 
	 * <pre><code>
	 * ACL::in(array("admin","admin2"));
	 * </code></pre>
	 *
	 * @param mixed string or array of groups to check
	 * @return boolean true if user is in given group, false otherwise
	 * @see check
	 * @see ACL
	 */
	static function in($group) // in('admin') or in(array('admin','admin2'))
	{
        if( self::$groups === null)
            self::init();

		if(empty($group)) return false;
		
		if(is_string($group)) $group = array($group);

		return (bool)count(array_intersect($group,self::$groups));
	}
	//}}}

	//{{{ ==deprecated==
	/* Deprecated. Use addUserToGroup() and delUserFromGroup() instead */
	
	/*static function add($user_id = null, $groups = array())
    {
        if(!is_numeric($user_id) || empty($groups) || !is_array($groups)) return;
        
        DB::query("insert into ".self::ACL_TABLE." set id='".(int)$user_id."', groups='".implode(":",$groups)."' on duplicate key update groups='".implode(":",$groups)."'");
        if(Config::getInstance()->acl->cache_groups)
            Storage::create('acl_groups')->un_set($user_id);

    }
     */
    //}}}
    
    //{{{delete
    static function delete($user_id = null)
    {
        if(!is_numeric($user_id)) return;

		DB::query("delete from ".self::ACL_TABLE." where id='".(int)$user_id."' limit 1");
        ACL::flushCache($user_id);
	}
	//}}}

	//{{{ flushCache
	/**
	 * Flushes cached groups for given user. 
	 * Used only if acl.use and acl.cache_groups config flags are set to true values
	 *
	 * @param int id of user to flush cache
	 * @return null
	 */
	static function flushCache($user_id)
	{
		if(!isset($user_id) || !is_numeric($user_id))
			throw new ACLException("Parameter 'user_id' has incorrect format");
        if(Config::getInstance()->acl->use && Config::getInstance()->acl->cache_groups)
            Storage::create('acl_groups')->un_set($user_id);
	}
	//}}}
}
// }}}
?>
