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
		$parent = null
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
	function merge(ResultSet $r)
	{
		$this->def($r->getDef());
		foreach($r as $k => $v)
			$this->set($k,$v);
	}
	function getIterator()
	{
		return t(new ArrayObject($this->properties))->getIterator();
	}
}
?>
