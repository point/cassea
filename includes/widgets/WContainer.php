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
	protected function setChildData(ResultSet $data)
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
	protected function init(SimpleXMLElement $elem)
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
		if(empty($class_names)) return;
		if(is_string($class_names))
			$class_names = array($class_names);
		if(!is_array($class_names) ) return;

		for($i = 0, $c = count($this->items); $i < $c; $i++)
			if(!in_array(get_class($this->getItem($i)),$class_names))
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
	// {{{ setData
	function setData(ResultSet $data)
	{
		$controller = Controller::getInstance();
		$child_data = $data->getAnonChild();
		if($this->count()  == 1 && isset($child_data))
		{
			for($i = 0, $c = $child_data->getIterativeCount();$i < $c; $i++)
			{
				$i_data = $child_data->getIterative($i);
				if(!isset($i_data)) {$this->i_elem = clone $this->getItem(0);continue;}

				$i_data->setForId($this->items[0]);

				$controller->getDispatcher()->notify(
					new Event("increment_id",null,$this->getItem(0)->getId(),
					array('do_increment'=>1)));

				$this->i_elem[$i][0] = clone $this->getItem(0);
				$this->i_elem[$i][0]->setData($i_data);
			}
		}
		else
		{
			$max = 0;
			for($i = 0, $c = count($this->items); $i < $c;$i++)
			{
				$child_data = $data->getChild($this->items[$i]);
				if(isset($child_data))
				{
					$cnt = $data->getChild($this->items[$i])->getIterativeCount();
					if($cnt > $max)
						$max = $cnt;
				}
			}
			for($j = 0; $j < $max; $j++)
				for($i = 0, $c = count($this->items); $i < $c;$i++)
				{
					$child_data = $data->getChild($this->items[$i]);

					$controller->getDispatcher()->notify(
						new Event("increment_id",null,$this->getItem($i)->getId(),
						array('do_increment'=>1)));
					if(!isset($child_data) || $child_data->getIterative($j) == null) 
					{
						$this->i_elem[$j][$i] = clone $this->getItem($i);
					}
					else
					{
						$this->i_elem[$j][$i] = clone $this->getItem($i);
						$this->i_elem[$j][$i] ->setData($child_data->getIterative($j));
					}
				}
		}
	}
	// }}}

	// {{{ preReder
	function preRender()
	{
		for($j = 0, $c2 = count($this->i_elem); $j < $c2; $j++)
		{
			for($i = 0, $c = $this->count(); $i < $c; $i++)
				$this->i_elem[$j][$i]->messageInterchange();

			for($i = 0, $c = $this->count();$i < $c; $i++)
				$this->i_elem[$j][$i]->preRender();
		}
	}
	// }}}
	// {{{ generateHTML
	function generateHTML($pos = 0)
	{
		return "";
	}
	// }}}
	// {{{ generateAllHTML
	function generateAllHTML()
	{
		$ret = "";
		if(isset($this->i_elem))
			for($j = 0, $c2 = count($this->i_elem); $j < $c2; $j++)
			{
				for($i = 0, $c = $this->count(); $i < $c; $i++)
					$ret .= $this->i_elem[$j][$i]->generateHTML();
				$ret .= "\n";
			}
		else
			$ret = parent::generateAllHTML();

		return $ret;
	}
	// }}}
	// {{{ generateAllHTMLByLines
	function generateAllHTMLByLines($line = null)
	{
// todo: output content if no data was settted
		if(!isset($line) || $line < 0 || $line > count($this->i_elem))
		{
			$ret_a = array();
			for($j = 0, $c2 = count($this->i_elem); $j < $c2; $j++)
			{
				$ret_s = "";
				for($i = 0, $c = $this->count(); $i < $c; $i++)
					$ret_s .= $this->i_elem[$j][$i]->generateHTML();
				$ret_a[] = $ret_s;
			}
			return $ret_a;
		}
		$ret_s = "";
		for($i = 0, $c = $this->count(); $i < $c; $i++)
			$ret_s .= $this->i_elem[$line][$i]->generateHTML();
		return $ret_s;
	}
	// }}}
	// {{{ postRender
	function postRender()
	{
		for($j = 0, $c2 = count($this->i_elem); $j < $c2; $j++)
			for($i = 0, $c = $this->count();$i < $c; $i++)
				$this->i_elem[$j][$i]->postRender();
	} 
	// }}}

}
// }}}
?>
