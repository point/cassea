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
 * This file contains factory class, that instantiates and returns 
 * class with profile information for particular user.
 * 
 * By default, built-in CasseaProfile is used.
 *
 * But this behavior could be changed with config option
 * <pre><code>
 * profile.name = "..."
 * </code></pre>
 *
 * In order to define new profile class, it should be placed to the <code>/vendors/_profile_name_</code> dir and
 * this name should be pointed in <code>config.ini</code>. 
 * All profile classes should implement <code>iProfile</code> interface.
 *
 * For example, <code>my_profile</code> profile will be looks so:
 *
 * <pre><code>
 * config.ini:
 * profile.name=my_profile
 *
 *
 * /vendors/my_profile/MyProfile.php with class
 *
 * class MyProfile implements iProfile {}
 *
 * </code></pre>
 *
 * Name of the class will be obtained with <code>nameToClass()</code> function (one of common functions in functios.php).
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id:$
 * @package system
 * @since 
 */

//{{{ Profile
class Profile
{
	/**
	 * @var array $loadedProfiles memorize already created objects for given user_id.
	 */
	private static $loadedProfiles = array();
	/**
	 * @var string $profile_classname memorize obtained from config class name for profile object
	 */
	private static $profile_classname = "CasseaProfile";

	//{{{ get
	/**
	 * Main function which returns instance of profile class, selected in config file.
	 *
	 * <code>$user_id</code> parameter is used to define which info should contain profile object.
	 * If this parameter is omitted, profile for current user will be returned.
	 *
	 * For performance reasons, objects are saved to reduce DB queries. If calling this method 
	 * not for the first time, previously created object will be returned.
	 *
	 * @param mixed $user_id it could be either null or int with id of user to retrieve data.
	 * @return iProfile initialized object of profile class.
	 * @throws ProfileException if class isn't implements iProfile interface.
	 */
	static function get($user_id = null)
	{
		if($user_id === null)
			$user_id = User::get()->getId();
		if (isset(self::$loadedProfiles[$user_id])) return self::$loadedProfiles[$user_id];

		$profile_classname = self::getProfileClass();
		$o = new $profile_classname($user_id);
		if(!$o instanceof iProfile)
			throw new ProfileException("Profile must be instance of iProfile interface");
		return self::$loadedProfiles[$user_id] = $o;
	}
	//}}}

	//{{{ addUser
	/**
	 * This method called when new user is creating. 
	 * <code>addUser</code> is calling statically because there is no profile for 
	 * new user and we couldn't call {@link get} before.
	 *
	 * This method simply proxies call to the specified class.
	 *
	 * @param int $user_id user id of the newly created member.
	 * @param mixed $param could be either null or array with specific parameters.
	 * @return null
	 */
	static function addUser($user_id, $param=null){
		return call_user_func( self::getProfileClass().'::addUser',$user_id, $param);
	}
	//}}}

	//{{{ getProfileClass
	/**
	 * Tries to calculate name of the class from config and add to vendor lookup
	 * paths list retrieved directory.
	 *
	 * For performance reasons, obtained classname is memorized in internal private variable.
	 *
	 * @param null
	 * @return string class name of the profile.
	 */
	static private function getProfileClass(){
		if(self::$profile_classname) return self::$profile_classname;

		try {
			if($_t = Config::getInstance()->profile->name != "cassea") //for compatibility
				self::$profile_classname = $_t;
		}
		catch(ConfigException $e){
			//just normal. Using default profile class
		}

		Autoload::addVendor(self::$profile_classname);
		return self::$profile_classname = nameToClass($profile_classname);
	}
	//}}}
}

