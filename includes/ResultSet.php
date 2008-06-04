<?php
// $Id:$
//

class ResultSetPool
{
	static $cache = array();

	static function set(ResultSet $r,$priority = 0)
	{
		if($r->getFor() !== null) return;
		if(!isset(self::$cache[$r->getFor()]))
		{
			self::$cache[$r->getFor()]['priority'] = array();
			self::$cache[$r->getFor()]['set'] = array();
		}
		self::$cache[$r->getFor()]['priority'][] = $priority;
		self::$cache[$r->getFor()]['set'][] = $r;
	}
	static function get($w_id)
	{
		if(!isset(self::$cache[$w_id])) return null;
		$a = &self::$cache[$w_id];
		array_multisort($a['priority'],SORT_REGULAR,SORT_ASC,$a['set']);
		$res = new ResultSet();
		foreach($a as &$v)
			$res->merge($v);
		return $res;
	}
}
class ResultSet implements IteratorAggregate
{
	protected 
		$for_id = null,
		$def = null,
		$properties = array()
		;

	function forid($id = null)
	{
		if(!isset($id)) return;
		$this->for_id = $id;
		return $this;
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
		if(!isset($k,$this->attributes[$k])) return;
		return $this->attributes[$k];
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
