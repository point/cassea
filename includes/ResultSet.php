<?php
// $Id:$
//

class ResultSetPool
{
	static $pool = array();

	static function set(Result $result,$priority = 0)
	{
		foreach($result as $r) //ResultSet
		{
			if($r->getFor() == null || !$r instanceof ResultSet) continue;
			if(!isset(self::$pool[$r->getFor()]))
			{
				self::$pool[$r->getFor()]['priority'] = array();
				self::$pool[$r->getFor()]['set'] = array();
			}
			self::$pool[$r->getFor()]['priority'][] = $priority;
			self::$pool[$r->getFor()]['set'][] = $r;
		}
	}
	static function get($w_id)
	{
		$res = new ResultSet();
		$res->setForId($w_id);
		if(!isset(self::$pool[$w_id])) return $res;
		$a = &self::$pool[$w_id];
		array_multisort($a['priority'],SORT_REGULAR,SORT_ASC,$a['set']);
		foreach($a['set'] as &$v)
			$res->merge($v);
		return $res;
	}
}
class Result implements IteratorAggregate
{
	private $result_sets = array();
	function forid($id = null)
	{
		if(!isset($id)) return;

		$rs = null;
		if(isset($this->result_sets[$id]))
			$rs = $this->result_sets[$id];
		else
		{
			$rs = new ResultSet();
			$rs->setForId($id);
			$rs->setParent($this);
			$this->result_sets[$id] = $rs;
		}
		return $rs;
	}
	function getIterator()
	{
		return t(new ArrayObject($this->result_sets))->getIterator();
	}


}
class ResultSet implements IteratorAggregate
{
	protected 
		$for_id = null,
		$def = null,
		$properties = array(),
		$parent = null,
		$anon_child = null,
		$children = array(),
		$iterative = array()
		;

	function __isset($prop)
	{
		return isset($this->properties[$prop]);
	}
	function setForId($id = null)
	{
		if(!isset($id)) return;
		$this->for_id = $id;
	}
	function forid($id)
	{
		if(!isset($id)) return;
		return $this->parent->forid($id);
	}
	function child($id = null)
	{
		$rs = new ResultSet();
		if(isset($id))
			$rs->setForId($id);
		$rs->setParent($this);
		if(isset($id))
			$this->children[$id] = $rs;
		else
			$this->anon_child = $rs;
		return $rs;
	}
	function each($ind )
	{
		if(!isset($ind) || !is_numeric($ind) || $ind < 0) return;

		$rs = new ResultSet();
		$rs->setForId($this->for_id);
		$rs->setParent($this);
		$this->iterative[$ind] = $rs;
		return $rs;
	}
	function setParent($parent)
	{
		if(!isset($parent)) return ;
		$this->parent = $parent;
	}
	function end()
	{
		return $this->parent;
	}
	function getFor()
	{
		return $this->for_id;
	}
	function def($v)
	{
		$this->def = $v;
		return $this;
	}
	function set($k, $v)
	{
		if(!isset($k,$v)) return;
		$this->properties[$k] = $v;
		return $this;
	}
	function get($k = null)
	{
		if(!isset($k,$this->properties[$k])) return null;
		return $this->properties[$k];
	}
	function getDef()
	{
		return $this->def;
	}
	function setAnonChild(ResultSet $child)
	{
		$this->anon_child = $child;
	}
	function getAnonChild()
	{
		return $this->anon_child;
	}

	function getChild($id)
	{
		if(!isset($this->children[$id])) return null;
		return $this->children[$id];
	}
	function getAllChildren()
	{
		if(empty($this->children)) return null;
		return $this->children;
	}
	function setChildren($children)
	{
		if(!isset($children) || !is_array($children)) return;
		$this->children = $children;
	}
	function getIterative($id)
	{
		if(isset($this->iterative[$id]))
			return $this->iterative[$id];
		return null;
	}
	function getIterativeCount()
	{
		return count($this->iterative);
	}
	function getAllIterative()
	{
		return $this->iterative;
	}
	function setIterative($a)
	{
		if(!is_array($a)) return;
		$this->iterative = $a;
	}
	function merge(ResultSet $r)
	{
		$this->def($r->getDef());
		foreach($r as $k => $v)
			$this->set($k,$v);
		if(($c = $r->getAnonChild()) !== null)
			$this->setAnonChild($c);
		if(($c = $r->getAllChildren()) !== null)
			$this->setChildren($c);
		if(($c = $r->getAllIterative()) !== null)
			$this->setIterative($c);
	}
	function getIterator()
	{
		return t(new ArrayObject($this->properties))->getIterator();
	}
}
?>
