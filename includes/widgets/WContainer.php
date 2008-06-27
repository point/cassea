<?php

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
	// {{{ setData
	function setData(ResultSet $data)
	{
		parent::setData($data);
		$this->setChildData($data);
	}
	// }}}
	// {{{ setChildData
	function setChildData(ResultSet $data)
	{
		if($this->getId() != $data->getFor()) return;
		foreach($this->class_vars as $v)
			if($this->$v instanceof WidgetCollection)
				$this->$v->setData($data);
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
	function childPreRender()
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
	function childPostRender()
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
		$items = array()
		;
	
	// {{{ __construct
	function __construct($elem = null)
	{
		if(isset($elem))
			$this->init($elem);
	}
	// }}}

	// {{{ init
	function init(SimpleXMLElement $elem)
	{
		$controller = Controller::getInstance();
		foreach($elem as $v)
			$this->addItem(($id = $controller->buildWidget($v,1)));
	}
	// }}}

	// {{{ addItem
	function addItem($item_id)
    {
		if(!empty($item_id) && is_scalar($item_id))
			$this->items[] = $item_id;
    }
	// }}}
	
	// {{{ deleteItemById 
    function deleteItemById($id)
    {
		for($i = 0, $c = count($this->items);$i < $c; $i++)
			if($this->items[$i] == $id)
			{
				unset($this->items[$i]);
				break;
			}
		$this->items = array_values($this->items);
    }
    // }}}
    
    // {{{ deleteItemByPos 
    function deleteItemByPos($position)
    {
    	if($position < 0 || $position > count($this->items)) 
			return;

		if(isset($this->items[$position]))
			unset($this->items[$position]);
		$this->items = array_values($this->items);
    }
    // }}}

	// {{{ clear
	function clear()
	{
		$this->items = array();
	}
	// }}}
	
    // {{{ getItems 
    function getItems()
    {
		return $this->items;
    }
    // }}}
	
	// {{{ count
	function count()
	{
		return count($this->items);
	}
	// }}}

	// {{{ getItemId
	function getItemId($position = 0)
	{
    	if($position < 0 || $position > count($this->items)) 
			return;
		return $this->items[$position];
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
	function setData(ResultSet $data)
	{
		$child_data = $data->getAnonChild();
		if($this->count()  == 1 && isset($child_data))
		{
			if(isset($child_data))
			{
				$child_data->setForId($this->items[0]);
				$this->getItem(0)->setData($child_data);
			}
		}
		else
			for($i = 0, $c = count($this->items); $i < $c;$i++)
			{
				$child_data = $data->getChild($this->items[$i]);
				if(!isset($child_data)) continue;
				$this->getItem($i)->setData($child_data);
			}
	}
	// }}}
	// {{{ getItem
	function getItem($position = 0)
	{
		$w = null;
		if(!$this->exists($position)) return $w;
		$controller = Controller::getInstance();
		return $w = $controller->getWidget($this->items[$position]);
	}
	// }}}
	// {{{ exists
	function exists($position)
	{
		if(empty($this->items)) return 0;
	   	if($position < 0 || $position > count($this->items)) 
			return 0;
		return 1;
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
		for($i = 0, $c = count($this->items); $i < $c; $i++)
			if(in_array(get_class($this->getItem($i)),$class_names)) return 1;
		return 0;
	}
	// }}}
	// {{{ filter
	function filter($class_names = array())
	{
		if(!is_array($class_names) || empty($class_names)) return;
		/*for($i = 0, $c = count($class_names); $i < $c; $i++)
			$class_names[$i] = strtolower($class_names[$i]);*/

		for($i = 0, $c = count($this->items); $i < $c; $i++)
			if(!in_array(get_class($this->getWidget($i)),$class_names))
				unset($this->items[$i]);
		$this->items = array_values($this->items);
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
}
// }}}
?>
