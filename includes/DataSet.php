<?php
//
// $Id:$
//

//{{{ DataSet
interface DataSet
{
	function prepareData($w_id);
	function getData($w_id);
	function getName();
}

class SurrogateDataSet implements DataSet
{
	protected 
		$id = "",
		$rs = null
	;
	function __construct(ResultSet $rs)
	{
		$this->rs = $rs;
		$this->id = "__d".substr(md5(rand()*time()),0,10);
	}
	function prepareData($w_id)
	{
		$result = new Result();
		$result->addResultSet($this->rs);
		ResultSetPool::set($result,$this->rs->getPriority());
	}
	function getData($w_id)
	{
		$this->prepareData($w_id);
		return ResultSetPool::get($w_id);
	}
	function getName()
	{
		return $this->id;
	}
}

class DataSetAggregator implements DataSet
{
	protected 
		$id = "",
		$datasets = array()
	;

	function __construct($existent_ds = null)
	{
		if(isset($existent_ds))
			$this->addDataset($existent_ds);
		$this->id = "__d".substr(md5(rand()*time()),0,10);
	}
	function prepareData($w_id)
	{
		foreach($this->datasets as &$ds)
			$ds->prepareData($w_id);
	}
	function getData($w_id)
	{
		$this->prepareData($w_id);
		return ResultSetPool::get($w_id);
	}
	function getName()
	{
		return $this->id;
	}
	function addDataSet(DataSet $ds)
	{
		$this->datasets[] = $ds;
	}
}
?>
