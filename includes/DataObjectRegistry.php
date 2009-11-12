
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
 * This file contains class for storing DataObjects.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: ACL.php 163 2009-10-15 15:00:34Z point $
 * @package system
 * @since 
 */

//{{{ DataObjectRegistry
/**
 * You may reduce number of created objects by the reusing
 * of already created objects with the same arguments.
 * This could be acheived by defining <code>registry</code>
 * attribute to the DataSet or DataHandler.
 */
class DataObjectRegistry
{
	/**
	 * Array stored objects with the keys as registry name
	 * @var array
	 */
	static protected $data_objects = array();

	//{{{ exists
	/**
	 * Checks whenever object with such registry name already exists.
	 *
	 * @param string name of the registry
	 * @return bool
	 */
	static function exists($registry_name)
	{
		if(!isset($registry_name) || !is_scalar($registry_name)) return false;

		return isset(self::$data_objects[$registry_name]);
	}
	//}}}

	//{{{ set
	/**
	 * Storing given object with specified registry name
	 *
	 * @param string registry name
	 * @param stdObject object to store or null if it doesn't exists
	 * @return null
	 */
	static function set($registry_name, &$object)
	{
		if(!isset($registry_name) || !is_scalar($registry_name)) return ;
		if(!is_object($object) || isset($data_objects[$registry_name])) return;

		self::$data_objects[$registry_name] = $object;
	}
	//}}}

	//{{{ get
	/**
	 * Retrieves object with the given registry name if exists.
	 *
	 * @param string registry name
	 * @return stdObject stored object or null if it doesn't exists
	 */
	static function &get($registry_name)
	{
		if(!isset($registry_name) || !is_scalar($registry_name)
			|| !isset(self::$data_objects[$registry_name])) return null;

		return self::$data_objects[$registry_name];
	}
	//}}}
}
//}}}

