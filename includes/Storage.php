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
 * @author billy <a.mirniy@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id:$
 * @package system
 * @since 
 */

//{{{ Storage
/**
 * This class introduce factory method for creating persistent storage.
 * It hides implementation and initialization behind create and
 * createWithSession methods.
 */
class Storage 
{
	/**
	 * Cached name of the class to instantiate.
	 */
	private static $classname = null;

	//{{{ init
	/**
	 * Initialize storage-subsystem. Class to be used as
	 * a storage engine is choosed on the base of config file.
	 *
	 * So, if in config string <code>storage_engine='foo'</code> 
	 * exists, file/class FooStorage will be looked up 
	 * int the vendors/storage directory.
	 *
	 * @param null
	 * @return null
	 */
	static function init(){
		$storageEngine = Config::get('STORAGE_ENGINE');
		self::$classname = nameToClass($storageEngine).'Storage';
		Autoload::addVendor('storage', $storageEngine);
	}
	//}}}

	//{{{ create
	/**
	 * Creates new instance of specified storage. 
	 *
	 * If storage-subsystem wasn't inited, it will be lazy loaded.
	 *
	 * @param string unique storage name. Used to prevent mixning of 
	 * keys, that stored in the storage.
	 * @param int time-to-live for the keys and values.
	 * @return iStorageEngine 
	 * @see createWithSession
	 */
	static function create($storage_name,$ttl = null)
	{
		if(!isset(self::$classname))
			self::init();
		$o = new self::$classname($storage_name, $ttl);
		if(!$o instanceof iStorageEngine)
			throw new CasseaException("Select proper storage engine using storage_engine variable at config.ini");
		return $o;
	}
	//}}}

	//{{{ createWithSession
	/**
	 * Same as {@link create} but storage with given name will be unique 
	 * for each user. It's achieving by mixnig session id to the storage name.
	 * @param string unique storage name. Used to prevent mixning of 
	 *
	 * keys, that stored in the storage.
	 * @param int time-to-live for the keys and values.
	 * @return iStorageEngine 
	 * @see create
	 */
	static function createWithSession($storage_name,$ttl = null)
	{
		return self::create($storage_name.Session::getId(),$ttl);
	}
	//}}}
}
//}}}
?>
