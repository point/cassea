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
 * This file contains class for storing DataObjects created by WDataSet.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */

//{{{ DataObjectPool
/**
 * Holds all DataObjects created by {@link WDataSet} and
 * sorting them by priority.
 * Also it managing default data for widget, if no result set was provided
 * by the model.
 */
class DataObjectPool
{
	/**
	 * @var array pool of dataobject, sorted by priority
	 */
	static $pool = array();

	//{{{ set
	/**
	 * Stores dataobject in the pool and sort it.
	 *
	 * @param DataObject object to store.
	 * @return null
	 */
	static function set(DataSourceObject $do, $priority = 0)
	{
		self::$pool[] = array('priority'=>$priority,
			'data_object'=>$do);

		usort(self::$pool,create_function('$a,$b',
			'return ($a["priority"] < $b["priority"])?-1:1;'));
	}
	//}}}

	//{{{ findDefault
	/**
	 * Tries to find default data, composing it to the 
	 * {@link WidgetResultSet} and injecting this result set 
	 * to the data-setter method of particular widget, specified
	 * by widget_id parameter.
	 *
	 * If no data was found, empty {@link WidgetResultSet}
	 * will be passed to widget.
	 *
	 * @param string id of the widget for which to make a lookup
	 * @return WidgetResultSet with default data
	 */
	static function findDefault($widget_id)
	{
		$wrs = new WidgetResultSet;
		if(($widget = Controller::getInstance()->getWidget($widget_id)) === null) return $wrs;

        if(($data_setter = $widget->getDataSetterMethod()) === null) return $wrs;
		foreach(self::$pool as $v)
		{
			$d_o = $v['data_object'];
			if(($def = $d_o->getData($widget_id)) !== null && !$def instanceof WidgetResultSet)
		        $wrs->setDef($def);
		}
        $widget->{$data_setter}($wrs);
	}
	//}}
}
// }}}
