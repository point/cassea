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

class Profile
{
	static $loadedProfiles = array();

	static function get($user_id = null)
	{
		if($user_id === null)
			$user_id = User::get()->getId();
		if (isset(self::$loadedProfiles[$user_id])) return self::$loadedProfiles[$user_id];

		$profile_classname = self::getProfileClass();
		return self::$loadedProfiles[$user_id] = new $profile_classname($user_id);
	}

	static function addUser($user_id, $param=null){
		return call_user_func( self::getProfileClass().'::addUser',$user_id, $param);
	}

	static private function getProfileClass(){
		$profile_classname = null;
		try {
			$profile_classname = Config::getInstance()->profile->name;
		}
		catch(ConfigException $e){	$profile_classname = null; }

		if($profile_classname === null || $profile_classname == "cassea")
			return 'CasseaProfile';
		else
		{
			//Autoload::addVendorDir('profile',nameToClass($profile_classname));
			//return $profile_classname;
			Autoload::addVendor($profile_classname);
			return nameToClass($profile_classname);
		}

	}
}

