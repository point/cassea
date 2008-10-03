<?php
/*- vim:expandtab:shiftwidth=4:tabstop=4: 
{{{ LICENSE  
* Copyright (c) 2008, Cassea Project
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

WidgetLoader::load("WComponent");

// {{{ interface Container
interface Container
{
	function setChildData(ResultSet $data);
	function childPreRender();
	function childPostRender();
}
// }}}

// {{{ WContainer
class WContainer extends WComponent 
{
	// {{{
	function __clone()
	{
		foreach($this->class_vars as $v)
			if($this->$v instanceof WidgetCollection)
				$this->$v = clone $this->$v;
	}
	// }}}
	// {{{ preRender
	function preRender()
	{
		$this->childPreRender();
		parent::preRender();
	}
	// }}}
	// {{{ childPreRender
	protected function childPreRender()
	{
		foreach($this->class_vars as $v)
			if($this->$v instanceof WidgetCollection)
				$this->$v->preRender();
	}
	// }}}
	// {{{ postRender
	function postRender()
	{
		$this->childPostRender();
		parent::postRender();
	}
	// }}}
	// {{{ childPostRender
	protected function childPostRender()
	{
		foreach($this->class_vars as $v)
			if($this->$v instanceof WidgetCollection)
				$this->$v->postRender();
	}
	// }}}
	
}
//}}}

// {{{ WidgetCollection
class WidgetCollection
{
	protected
		$item_ids = array(),
		$item_objs = array(),
		$parent_id =null
		;
	
	// {{{ __construct
	function __construct($parent_id,$elem = null)
	{
		if(!is_string($parent_id)) return;
		$this->parent_id = $parent_id;
		if(isset($elem))
			$this->init($elem);
	}
	// }}}

	// {{{ init
	protected function init(SimpleXMLElement $elem)
	{
		$controller = Controller::getInstance();
		foreach($elem as $v)
		{
			$this->addItem(($id = $controller->buildWidget($v,1)));
			$controller->getAdjacencyList()->add($id,$this->parent_id);
		}
	}
	// }}}

	// {{{ addItem
	function addItem($item_id)
    {
		if(!empty($item_id) && is_scalar($item_id))
		{
			$w = Controller::getInstance()->getWidget($item_id);
			if($w == null) return;
			$this->item_ids[] = $item_id;
			$this->item_objs[] = $w; 
		}
    }
	// }}}
	
	// {{{ deleteItemById 
    function deleteItemById($id)
    {
		for($i = 0, $c = count($this->item_ids);$i < $c; $i++)
			if($this->item_ids[$i] == $id)
			{
				unset($this->item_ids[$i]);
				unset($this->item_objs[$i]);

				$this->item_ids = array_values($this->item_ids);
				$this->item_objs = array_values($this->item_objs);
				break;
			}
    }
    // }}}
    
    // {{{ deleteItemByPos 
    function deleteItemByPos($position)
    {
    	if($position < 0 || $position > count($this->item_ids)) 
			return;

		if(isset($this->item_ids[$position]))
		{
			unset($this->item_ids[$position]);
			unset($this->item_objs[$position]);

			$this->item_ids = array_values($this->item_ids);
			$this->item_objs = array_values($this->item_objs);
		}
    }
    // }}}

	// {{{ clear
	function clear()
	{
		$this->item_ids = array();
		$this->item_objs = array();
	}
	// }}}
	
    // {{{ getItems 
    function getItemsIds()
    {
		return $this->item_ids;
    }
    // }}} 
	
	// {{{ count 
	function count()
	{
		return count($this->item_ids);
	}
	// }}}

	// {{{ getItemId
	function getItemId($position = 0)
	{
    	if($position < 0 || $position > count($this->item_ids)) 
			return;
		return $this->item_ids[$position];
	}
	// }}}
	// {{{ preReder
	function preRender()
	{
		for($i = 0, $c = $this->count(); $i < $c; $i++)
			$this->getItem($i)->messageInterchange();

		for($i = 0, $c = $this->count();$i < $c; $i++)
			$this->getItem($i)->preRender();
	}
	// }}}
	// {{{ generateHTML
	function generateHTML($pos = 0)
	{
		if(($w = $this->getItem($pos)) !== null)
			return $w->generateHTML();
		return "";
	}
	// }}}
	// {{{ generateAllHTML
	function generateAllHTML()
	{
		$ret = "";
		for($i = 0, $c = $this->count();$i < $c;$i++)
			if(($w = $this->getItem($i)) !== null)
				$ret .= $w->generateHTML();
		return $ret;
	}
	// }}}
	// {{{ postRender
	function postRender()
	{
		for($i = 0, $c = $this->count();$i < $c; $i++)
			$this->getItem($i)->postRender();
	} 
	// }}}

	// {{{ setData
	/*function setData(ResultSet $data)
	{
		$child_data = $data->getAnonChild();
		if($this->count()  == 1 && isset($child_data))
		{
			$child_data->setForId($this->getItemId(0));
			$this->getItem(0)->setDataset(new SurrogateDataSet($child_data));
		}
		else
		{
			for($i = 0, $c = $this->count(); $i < $c; $i++)
				if(($child_data = $data->getChild($this->getItemId($i))) != null)
					$this->getItem($i)->setDataset(new SurrogateDataSet($child_data));
		}
		for($i = 0, $c = $this->count(); $i < $c;$i++)
			if(($desc = $data->shiftDescent($this->getItemId($i))) != null)
				$this->getItem($i)->setDataset(new SurrogateDataSet($desc));

		
		if($data->hasDescent())
			for($i = 0, $c = $this->count(); $i < $c;$i++)
				if(($desc = $data->getDescentResultSet($this->getItemId($i))) != null)
					$this->getItem($i)->setDataset(new SurrogateDataSet($desc));
	}*/
	// }}}
	// {{{ getItem
	function getItem($position = 0)
	{
		if(!$this->exists($position)) return null;
		return $this->item_objs[$position];
	}
	// }}} 
	// {{{ exists
	function exists($position)
	{
		if(empty($this->item_ids)) return 0;
	   	if($position < 0 || $position > count($this->item_ids)) 
			return 0;
		if(!empty($this->item_ids[$position]) && !empty($this->item_objs[$position]))
			return 1;
		return 0;
	}
	// }}}
	// {{{ has
	function has($class_names = array())
	{
		if(!empty($class_names) && is_string($class_names))
			$class_names = array($class_names);
		if(!is_array($class_names) || empty($class_names)) return 0;

	/*	for($i = 0, $c = count($class_names); $i < $c; $i++)
		$class_names[$i] = strtolower($class_names[$i]);*/
		for($i = 0, $c = $this->count(); $i < $c; $i++)
			if(in_array(get_class($this->getItem($i)),$class_names)) return 1;
		return 0;
	}
	// }}} 
	// {{{ filter 
	function filter($class_names = array())
	{
		if(empty($class_names)) return;
		if(is_string($class_names))
			$class_names = array($class_names);
		if(!is_array($class_names) ) return;

		for($i = 0, $c = $this->count(); $i < $c; $i++)
			if(!in_array(get_class($this->getItem($i)),$class_names))
				$this->deleteItemByPos($i);
	}
	// }}}
	// {{{ truncate
	function truncate()
	{
		if($this->count() > 1)
		{
			$it = $this->getItemId(0);
			$this->clear();
			$this->addItem($it);
		}
	}
	// }}}
	// {{{ __clone
	function __clone()
	{
		for($i = 0, $c = $this->count(); $i < $c; $i++)
			$this->item_objs[$i] = clone $this->item_objs[$i];
	}
	// }}}
}
// }}}

// {{{ MixedCollection
class MixedCollection extends WidgetCollection
{
	private $str = null;
	// {{{ init
	protected function init(SimpleXMLElement $elem )
	{
		if(!count($elem->children()))
			$this->str = trim((string)$elem);
		else
			parent::init($elem);
	}
	 // }}}
	// {{{ generateHTML
	function generateHTML($pos = 0)
	{
		if(isset($this->str))
			return $this->str;
		return parent::generateHTML($pos);
	}
	// }}}
	// {{{ generateAllHTML
	function generateAllHTML()
	{
		if(isset($this->str))
			return $this->str;
		return parent::generateAllHTML();
	}
	// }}}

}
// }}}

// {{{ IterableCollection
class IterableCollection extends WidgetCollection
{
	private $i_elem = null;
	// {{{ init
	protected function init(SimpleXMLElement $elem )
	{
		if(!count($elem->children())) return;
		parent::init($elem);
	}
	// }}}
	// {{{ preReder
	function preRender()
	{
		$controller = Controller::getInstance();
		for($i = 0, $c = $controller->getDisplayModeParams()->getLimit($this->parent_id);$i < $c; $i++)
		{
			$controller->getDispatcher()->notify(
				new Event("increment_id",null,null,array('do_increment'=>1)));
			parent::preRender();

			for($j = 0, $c2 = $this->count();$j < $c2; $j++)
				$this->i_elem[$i][$j] = clone $this->getItem($j);

			$controller->getDispatcher()->notify(
				new Event("increment_id",null,null,array('do_increment'=>0)));

			$controller->getDisplayModeParams()->incCurrent($this->parent_id);
		}
		$controller->getDisplayModeParams()->resetCurrent($this->parent_id);

	}
	// }}}
	// {{{ generateHTML
	function generateHTML($pos = 0)
	{
		if(!count($this->i_elem))
			return parent::generateHTML($pos);
		return "";
	}
	// }}}
	// {{{ generateAllHTML
	function generateAllHTML()
	{
		$ret = "";
		//if(isset($this->i_elem))
			for($j = 0, $c2 = count($this->i_elem); $j < $c2; $j++)
			{
				for($i = 0, $c = $this->count(); $i < $c; $i++)
					$ret .= $this->i_elem[$j][$i]->generateHTML();
				$ret .= "\n";
			}
		/*else
			$ret = parent::generateAllHTML();*/

		return $ret;
	}
	// }}}
	// {{{ postRender
	function postRender()
	{
		if(count($this->i_elem))
			for($j = 0, $c2 = count($this->i_elem); $j < $c2; $j++)
				for($i = 0, $c = $this->count();$i < $c; $i++)
					$this->i_elem[$j][$i]->postRender();
		else 
			parent::postRender();

		$controller = Controller::getInstance();
	} 
	// }}}

}
// }}}
?>
