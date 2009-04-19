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

// $Id$
//
class AdjacencyListCell
{
	public
		$widgetId = null,
		$parent = null;
	function __construct($widget_id,$parent = null)
	{
		$this->widgetId = $widget_id;
		$this->parent = $parent;
	}
}
class WidgetAdjacencyList
{
	public $list = array();
	private $parent_cache = array();

	function add($widget_id, $parent = null)
	{
		$flag = 0;
		foreach($this->list as $v)
			if($v->widgetId == $widget_id)
				$v->parent = $parent and $flag = 1;
		if(!$flag) $this->list[] = new AdjacencyListCell($widget_id,$parent);
	}
	function getParentForId($id)
	{
		if(isset($this->parent_cache[$id])) 
			return $this->parent_cache[$id];

		foreach($this->list as $v)
			if($v->widgetId == $id)
				return $v->parent;
		return null;
	}
	function setParentForIdCache($id,$widget)
	{
		if(is_string($widget))
			$this->parent_cache[$id] = $widget;
	}
	function getPrevUntil($id,$until = null)
	{
		for($i = 0, $c = count($this->list); $i < $c; $i++)
			if($this->list[$i]->widgetId == $id && $i > 0)
				if($until !== null)
					return ($this->list[$i-1]->widgetId != $until)?$this->list[$i-1]->widgetId:null;
				else return $this->list[$i-1]->widgetId;
		return null;
	}
	function hasParent($id)
	{
		foreach($this->list as $v)
			if($v->widgetId == $id)
				if($v->parent !== null) return true;
		return false;
	}
	function getByInd($ind = 0)
	{
		if($ind < 0 || $ind > count($this->list)) return null;
		return $this->list[$ind]->widgetId;
	}
	function getChildren($parent)
	{
		$list = array();
		foreach($this->list as $v)
			if($v->parent == $parent)
				$list[] = $v->widgetId;
		return $list;
	}
	function checkIndex($list, $id)
	{
		if(!is_array($list)) return null;
		foreach($list as $k => $v)
			if($v == $id) return $k;
		return null;
	}
}
?>
