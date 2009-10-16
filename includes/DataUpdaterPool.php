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
 * This file contains class for managing callbacks for updating model.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id:$
 * @package system
 * @since 
 */

//{{{ DataUpdaterPool
/**
 * Holds all DataObjects, created by {@link WDataHandler} which
 * intended to update model's state with incoming POST data.
 *
 * Placed DataObjects are sorted by their priorities.
 */
class DataUpdaterPool
{
	/**
	 * @var array pool of DataHandlerObjects sorted by priority
	 */
	static $pool = array();

	//{{{ set
	/**
	 * Stores DataHandlerObject in the pool and sort it.
	 *
	 * @param DataHandlerObject object to store.
	 * @return null
	 */
	static function set(DataHandlerObject $dho, $priority = 0,$id = null,$form_ids = null)
	{
		self::$pool[] = array('priority'=>$priority,
            'data_handler_object'=>$dho,'id'=>$id,
            'form_ids' => $form_ids);

		usort(self::$pool,create_function('$a,$b',
			'return ($a["priority"] < $b["priority"])?-1:1;'));
	}
	//}}}

	//{{{ getById
	/**
	 * Returns stored DataHandlerObject by id of {@link WDataHandler}
	 *
	 * @param string id to search
	 * @return mixed it could be either DataHandlerObject or null 
	 * if nothing was found
	 */
    static function getById($id)
    {
        foreach(self::$pool as $o)
            if($o['id'] == $id)
                return $o['data_handler_object'];
        return null;
	}
	//}}}

	//{{{ callCheckers
	/**
	 * Calls checker methods of the model's object/class. 
	 *
	 * It walks through the pool and if checkers are defined, tries to
	 * call them.
	 *
	 * This kind of methods
	 * are useful to check incoming POST before any of datahandlers is
	 * called. 
	 *
	 * As datahandler could be snapped to the form, checkers are also could be snapped to 
	 * particular form.
	 *
	 * @param string optional id of the form. If passed, only checkers, snapped to this form
	 * will be called.
	 * @return null
	 * @see lookupDHs
	 * @see callHandlers
	 * @see callFinalize
	 */
	static function callCheckers($form_id = null)
	{
		$controller = Controller::getInstance();
		foreach(self::lookupDHs($form_id) as $dho)
			$dho->check($controller->post);
	}
	//}}}

	//{{{ callHandlers
	/**
	 * Calls handler methods of the model's object/class. 
	 *
	 * It walks through the pool and calls specified methods to handle 
	 * incoming POST data.
	 *
	 * As datahandler could be snapped to the form, handlers are also could be snapped to 
	 * particular form.
	 *
	 * @param string optional id of the form. If passed, only handlers, snapped to this form
	 * will be called.
	 * @return null
	 * @see callCheckers
	 * @see callFinalize
	 */
	static function callHandlers($form_id = null)
	{
		$controller = Controller::getInstance();
		foreach(self::lookupDHs($form_id) as $dho)
			$dho->handle($controller->post);

	}
	//}}}

	//{{{ callFinalize
	/**
	 * Calls finalizing methods of the model's object/class. 
	 *
	 * It walks through the pool and if finalizers are defined, tries to
	 * call them.
	 *
	 * This kind of methods are useful to make some actions when all datahandlers was called.
	 *
	 * As datahandler could be snapped to the form, finalizers are also could be snapped to 
	 * particular form.
	 *
	 * @param string optional id of the form. If passed, only finalizers, snapped to this form
	 * will be called.
	 * @return null
	 * @see lookupDHs
	 * @see callCheckers
	 * @see callHandlers
	 */
	static function callFinalize($form_id = null)
	{
		$controller = Controller::getInstance();
		foreach(self::lookupDHs($form_id) as $dho)
			$dho->finalize($controller->post);
	}
	//}}}

	//{{{ lookupDHs
	/**
	 * Internal helper method that tries to find DataHandlerObjects, snapped
	 * to the given form id.
	 * If no snapping is used, all DataHandlerObjects will be returned.
	 *
	 * @param string id of the form
	 * @return array of founded DataHandlerObjects
	 */
	private static function lookupDHs($form_id)
	{
		$dhls = array();
		// form_id doesn't set if request passed via ajax
		// also looking up datahandlers to check, handle or finalize POSTs
		// if no special DHs founded, calling all DHs
		if(isset($form_id))
			foreach(self::$pool as $p)
				if(!empty($p['form_ids']) && in_array($form_id,$p['form_ids']) && 
					($dho = $p['data_handler_object']))
					$dhls[] = $dho;

		if(empty($dhls)) 
			foreach(self::$pool as $p)
				if(($dho = $p['data_handler_object']) && empty($p['form_ids']))
					$dhls[] = $dho;

		return $dhls;
	}
	//}}}
}
// }}}
