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
 * This file contains class for storing pool of 
 * ResultSet objects, sorted by the WDataSet's priority.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id:$
 * @package system
 * @since 
 */

//{{{ ResultSetPool
/**
 * All ResultSet are sorted by priority.
 * ResultSet with greater priority will be polled for data later.
 *
 * Priorities range is bounded above with SYSTEM_PRIORITY constant.
 *
 */
class ResultSetPool
{
	/**
	 * Pool of objects. It's index-based array of arrays
	 * with "priority" and "result_set" keys.
	 * @var array
	 */
	static $pool = array();	

	/**
	 * Priority for the system-created ResultSets.
	 */
    const SYSTEM_PRIORITY = 1000;
	
	//{{{ set
	/**
	 * Adds given ResultSet to the pool.
	 * if priority wasn't defined, default (10) will be taken.
	 *
	 * @param ResultSet object to store
	 * @param numeric priority of particular ResultSet
	 * @return null
	 */
	static function set(ResultSet $rs,$priority = 10)
	{
		self::$pool[] = array('priority'=>min($priority,self::SYSTEM_PRIORITY-1),
			'result_set'=>$rs);
		usort(self::$pool,create_function('$a,$b',
			'return ($a["priority"] < $b["priority"])?-1:1;'));
	}
	//}}}

	//{{{ findMatched
	/**
	 * Finds ResultSet, matched for the given widget, extracts 
	 * WidgetResultSet and assigns it to the widget data-setter method.
	 *
	 * If no matched ResultSet was found or error occurred, false will be returned.
	 * 
	 * @param string id of the widget for which system should find matches
	 * @return bool 
	 * @see ResultSet::findMatched
	 */
	static function findMatched($widget_id)
	{
        $flag = false;
		if(($widget = Controller::getInstance()->getWidget($widget_id)) === null
		|| $widget instanceof iNotSelectable) return false;

        if(($data_setter = $widget->getDataSetterMethod()) === null) return false;
		foreach(self::$pool as $v)
        {
			$rs = $v['result_set'];
            $wrs = $rs->findMatched($widget);
            if($wrs->isEmpty()) continue;
            $widget->{$data_setter}($wrs);
            $flag = true;
		}
		return $flag;
	}
	//}}}
}
//}}}

