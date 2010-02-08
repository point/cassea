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
 * This file contains set of classes to maintaining information
 * about widgets nesting and ascending/pretending.
 *
 * They are used by the selectors mechanism and designed for internal
 * use only.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */

//{{{ AdjacencyListCell
/**
 * Element of AdjacencyList internal list. 
 * It's value object, that holds info about widget_id and his
 * parent_id if exists.
 */
class AdjacencyListCell
{
	public
		/**
		 * Id of the widget
		 * @var sting
		 */
		$widgetId = null,
		/**
		 * Id of parent widget, respectively to widget_id
		 * @var string
		 */
		$parent = null;

	//{{{ __construct 
	/**
	 * @param string id of the widget
	 * @param string parent's id
	 */
	function __construct($widget_id,$parent = null)
	{
		$this->widgetId = $widget_id;
		$this->parent = $parent;
	}
	//}}}
}
//}}}

//{{{ AdjacencyListMark
/**
 * Class, representing a "marker" object. It holds no data,
 * but could be used for take place and fill it with
 * information later.
 */
class AdjacencyListMark extends AdjacencyListCell 
{
	function __construct()
	{ parent::__construct(null,null);}
}
//}}}

//{{{ WidgetsAdjacencyList
/**
 * Holds linear representation of widgets tree hierarchy.
 * This structure could answer very efficiently if one widget stands 
 * next to other. Or if one widget has another widget as one of the
 * parents. 
 * This class used by the {@link SelectorMatcher} class to 
 * test whenever widget is suitable for current selector.
 */
class WidgetsAdjacencyList
{
	/**
	 * Array of AdjacencyListCell objects
	 * @var array
	 */
	private $list = array();
	/**
	 * Internal cache. Used to memorize frequently asked requests for 
	 * 1st nearest parent instance of iIterableContainer widget. 
	 * @var array of $widget_id=>$parent_iterable_id
	 */
	private $parent_cache = array();
	/**
	 * Internal cache. Holds parent id for frequently asked widgets.
	 * @var array if $widget_id=>$parent_widget_id
	 */
	private $parent_cache2 = array();

	//{{{ mark
	/**
	 * Placing a marker to the list to fill information later.
	 * This function is heavily used by the IterableContainer 
	 * in time of depth-first tree traverse. 
	 * It returns position for {@link addAtMark} function to
	 * place {@link AdjacencyListCell}.
	 *
	 * @param null
	 * @return int value for addAtMark function
	 * @see addAtMark
	 */
	function mark()
	{
		$mark = count($this->list);
		$this->list[] = new AdjacencyListMark();
		return $mark;
	}
	//}}}

	//{{{ addAtMark
	/**
	 * Creates AdjacencyListCell at the place of early created mark.
	 * Used primarily in IterableContainer.
	 *
	 * @param string id of current widget to store
	 * @param string id of it's parent widget
	 * @param int position, returned by {@link mark} function.
	 * @return null
	 * @see mark
	 */
	function addAtMark($widget_id,$parent,$mark_pos)
	{
		$flag = 0;
		foreach($this->list as &$v)
			if($v->widgetId == $widget_id)
				$v->parent = $parent and $flag = 1;
		if(!$flag && isset($this->list[$mark_pos]) && $this->list[$mark_pos] instanceof AdjacencyListMark) 
			$this->list[$mark_pos] = new AdjacencyListCell($widget_id,$parent);
	}
	//}}}

	//{{{ add
	/**
	 * Adds information of parent id for specified widget id to the 
	 * internal list.
	 *
	 * @param string id of the widget
	 * @param string id of parent widget
	 * @return null
	 */
	function add($widget_id, $parent )
	{
		$flag = 0;
		foreach($this->list as &$v)
			if($v->widgetId == $widget_id)
				$v->parent = $parent and $flag = 1;
		if(!$flag) $this->list[] = new AdjacencyListCell($widget_id,$parent);
	}
	//}}}

	//{{ getParentForId
	/**
	 * Return parent's id for specified widget id
	 *
	 * @param string id of the widget
	 * @return mixed string - id of the parent widget of null
	 * if parent wasn't found or widget is situating at the top
	 * of the hierarchy.
	 */
	function getParentForId($id)
	{
		if(isset($this->parent_cache2[$id])) 
			return $this->parent_cache2[$id];
		foreach($this->list as $v)
			if($v->widgetId == $id)
				return $this->parent_cache2[$id] = $v->parent;
		return null;
	}
	//}}}

	//{{{ getParentIterableForId
	/**
	 * Returns cached info about 1st parent with
	 * type iInterableContainer respecting to 
	 * the specified widget. If null is returned,
	 * {@link setParentIterableForIdCache} should be called 
	 * with appropriate values.
	 *
	 * @param string widget's id
	 * @return id of the iterable or null if id wasn't found in the cache
	 * @see setParentIterableForIdCache
	 */
	function getParentIterableForId($id)
	{
		if(isset($this->parent_cache[$id])) 
			return $this->parent_cache[$id];
		else return null;
	}
	//}}}

	//{{{ setParentIterableForIdCache
	/**
	 * Fills the cache for found iIterableContainer for the specified widget.
	 * To increase performance, logic of initializing of the cache has been moved to
	 * SelectorMatcher.
	 *
	 * @param string id of the widget
	 * @param string id of 1st parent iIterableContainer widget
	 * @return null
	 */
	function setParentIterableForIdCache($id,$widget_id)
	{
		if(!isset($this->parent_cache[$id]))
			$this->parent_cache[$id] = $widget_id;
	}
	//}}}

	//{{{ getPrevUntil
	/**
	 * Returns previous widget in the list only if it's id not equals to 
	 * $until parameter.
	 *
	 * @param string id of the widget
	 * @param string id of the "stopper" widget
	 * @return string id of the previous widget
	 */
	function getPrevUntil($id,$until = null)
	{
		for($i = 0, $c = count($this->list); $i < $c; $i++)
			if($this->list[$i]->widgetId == $id && $i > 0)
				if($until !== null)
					return ($this->list[$i-1]->widgetId != $until)?$this->list[$i-1]->widgetId:null;
				else return $this->list[$i-1]->widgetId;
		return null;
	}
	//}}}

	//{{{
	/**
	 * Detects if widget has a parent or it's root element.
	 * 
	 * @param string id of the widget
	 * @return bool
	 */
	function hasParent($id)
	{
		if(isset($this->parent_cache2[$id])) 
			return true;
		foreach($this->list as $v)
			if($v->widgetId == $id)
				if($v->parent !== null) { $this->parent_cache2[$id] = $v->parent; return true; }
		return false;
	}
	//}}}

	//{{{ getByInd
	/**
	 * Returns widget id, located at the $ind position in the list. 
	 * Or null if such element wasn't found.
	 *
	 * @param int index int the list
	 * @return mixed string or null if nothing was found
	 */
	function getByInd($ind = 0)
	{
		if($ind < 0 || $ind > count($this->list)) return null;
		return $this->list[$ind]->widgetId;
	}
	//}}}

	//{{{ getChildren
	/**
	 * Returns numeric array of all children's widget ids 
	 * if specified widget has any.
	 *
	 * @param string id of the widget
	 * @return array
	 * @see checkIndex
	 */
	function getChildren($parent)
	{
		$list = array();
		foreach($this->list as $v)
			if($v->parent == $parent)
				$list[] = $v->widgetId;
		return $list;
	}
	//}}}

	//{{{
	/**
	 * Returns position of the element in the list, returned by
	 * {@link getChildren} method.
	 *
	 * @param array of children elements
	 * @param string widget's id to search
	 * @return mixed numeric if element was found 
	 * @see getChildren
	 */
	function checkIndex($list, $id)
	{
		if(!is_array($list)) return null;
		return array_search($id,$list,true);
	}
	//}}}
}
//}}}
?>
