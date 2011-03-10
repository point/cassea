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
 * This file contains root class for the whole widgets hierarchy.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */

//{{{ WObject
/**
 * It provides basic methods for widget naming, property listing, etc.
 */
abstract class WObject extends EventBehavior
{
	/**
	 * @var int counter for widgets w/o id of with malformed ids.
	 * As it's static, it shares between all of the widgets.
	 */
	private static $s_counter = 0;
	/**
	 * @var string id of the widget
	 */
	protected $id = null;

	//{{{ __construct
	/**
	 * @param string|null id of the widget
	 */
	function __construct($id = null)
	{
		$this->setID($id);
		parent::__construct();
	}
	//}}}

	//{{{ getID 
	/**
	 * Returns id of the widget
	 *
	 * @return   string
	 */
	function getID()
	{
		return $this->id;
	}
	//}}}

	//{{{ setID 
	/**
	 * Sets id for the widget. It's strongly recommended to setup
	 * id only in constructor and keep without changes during the lifetime.
	 * That's because many parts of the system rely on that id.
	 *
	 * Allowed characters in id attribute in HTML5 spec: 
	 * http://mathiasbynens.be/notes/html5-id-class
	 *
	 * @param    string id of the widget
	 * @return   void
	 */
	protected function setID($id = null)
	{
		$this->id = empty($id)? ("__s".(self::$s_counter++)):
			str_replace(array("\r", "\r\n", "\n", "\t"),"",(string)$id);
	}
	//}}}

	//{{{ getProperties
	/**
	 * Returns public and protected properties of the widget
	 *
	 * @param null
	 * @return array with properties names
	 */
	function getProperties()
	{
		$class = get_class($this);
		$ret_prop = array();
		foreach($this as $k=>$v)
		{
			try{
				$prop = new ReflectionProperty($class, $k);
				if($prop->isPublic() || $prop->isProtected())
					$ret_prop[] = $k;
			}catch(Exception $e){}
		}
		return $ret_prop;
	}
	//}}}
}
//}}}
