<?php
// $Id:$
//
class ResultSetException extends Exception {}
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
			foreach(self::$pool[$r->getFor()]['set'] as &$er)
				if($r === $er) continue;
			$r->setPriority($priority);
			self::$pool[$r->getFor()]['priority'][] = $priority;
			self::$pool[$r->getFor()]['set'][] = $r;
		}
	}
	static function get($w_id)
	{
		$res = new ResultSet();//must return instance if ResultSet
		if(!isset(self::$pool[$w_id])) return $res;
		$res->setForId($w_id);
		$a = &self::$pool[$w_id];
		array_multisort($a['priority'],SORT_NUMERIC,SORT_ASC,$a['set']);
		foreach($a['set'] as &$v)
			$res->merge($v);
		if($res->getIterativeCount() && Controller::getInstance()->getDisplayMode() == Controller::DISPLAY_ITERATIVE)
		{
			$controller = Controller::getInstance();
			$controller->getDisplayModeParams()->updateIterativeCount($res->getIterativeCount());
			$it = $res->getIterative($controller->getDisplayModeParams()->iterative_current);
			if($it instanceof ResultSet)
				$it->setForId($w_id);
			return $it;
		}
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
	function addResultSet(ResultSet $rs)
	{
		if($rs->getFor() == null) return;
		$this->result_sets[$rs->getFor()] = $rs;
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
		$iterative = array(),
		$descent = array(),
		$ds_priority = 0
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
	function setPriority($p)
	{
		if(!isset($p) || !is_numeric($p) || $p < 0) return;
		$this->ds_priority = $p;
	}
	function getPriority()
	{
		return $this->ds_priority;
	}
	function forid($id)
	{
		if(!isset($id)) return;
		return $this->parent->forid($id);
	}
	function child($id = null)
	{
		$rs = new ChildResultSet();
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

		$rs = new IterativeResultSet();
		$rs->setForId($this->for_id);
		$rs->setParent($this);
		$this->iterative[$ind] = $rs;
		return $rs;
	}
	function descent($id )
	{
		$rs = new DescentResultSet();
		$rs->setForId($id);
		$rs->setParent($this);
		if(isset($id))
			$this->descent[$id] = $rs;
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
	function setDescent($descent)
	{
		if(!isset($descent) || !is_array($descent)) return;
		$this->descent = $descent;
	}
	function hasDescent()
	{
		return (bool)count($this->descent);
	}
	function getDescent($id)
	{
		if(!isset($this->descent[$id])) return null;
		return $this->descent[$id];
	}
	function shiftDescent($id)
	{
		if(($d = $this->getDescent($id)))
		{
			unset($this->descent[$id]);
			return $d;
		}
		return null;
	}
	function getDescentResultSet($to_id)
	{
		if(!count($this->descent)) return null;
		$rs = new ResultSet();
		$rs->setForId($to_id); 
		$rs->setDescent($this->getAllDescent());
		return $rs;
	}
	function getAllDescent()
	{
		return $this->descent;
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
		if(($c = $r->getAllDescent()) !== null)
			$this->setDescent($c);
	}
	function __toString()
	{
		$ret = "<pre>";
		foreach($this->properties as $k=>$v)
			$ret .= $k." = ".$v."\n<br>\n";
		$ret .= "</pre>";
	}
	function getIterator()
	{
		return t(new ArrayObject($this->properties))->getIterator();
	}
}
class CustomResultSet extends ResultSet 
{
	function forid($id)
	{
		if(!isset($id)) return;
		return $this->parent->forid($id);
	}
	function child($id = null)
	{
		return $this->parent->child($id);
	}
	function end()
	{
		return $this->parent->end();
	}
	function getPriority()
	{
		return $this->parent->getPriority();
	}
	function descent($id)
	{
		return $this->parent->descent($id);
	}
}
class ChildResultSet extends CustomResultSet
{ }
class DescentResultSet extends ChildResultSet
{ }
class IterativeResultSet extends CustomResultSet
{
	function each($ind)
	{
		return $this->parent->each($ind);
	}
}
?>
