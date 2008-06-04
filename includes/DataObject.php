<?php
// $Id: $
//


class DataObjectException extends Exception{}

//{{{ DataObject
class DataObjectParams
{
	protected 
		/**
		 * @var  array
		 */
		$params = array()
		;
	function __construct(SimpleXMLElement $elem)
	{
		if(!isset($elem->param)) return;

		$controller = Controller::getInstance();
		foreach($elem->param as $param)
		{
			if($param['from'] == "p1")
			{
				$p = (isset($param['as']) && $param['as'] == "array")?array($controller->p1):$controller->p1;
				if(isset($param->filter))
					$p = Filter::filter($p,(string)$param->filter);
				$this->params[] = $p;
			}
			elseif($param['from'] == "p2")
			{
				$c = count($controller->p2);
				if(isset($param['count']))
					$c = abs($param['count']);

				$p = array();
				for($i = 0,$j = 0; $i < $c;$i++)
				{
					if(!isset($controller->p2[$i])) continue;

					$p[$j] = $controller->p2[$i];
					if(isset($param->filter[$i]))
						$p[$j] = Filter::filter($p[$j],(string)$param->filter[$i]);
					$j++;
				}
				if(isset($param['as']) && $param['as'] == "array")
					$this->params[] = $p;
				else
					foreach($p as $_p)
						$this->params[] = $_p;
			}
			elseif($param['from'] == "p3" && isset($param['var']))
			{
				$p = (isset($param['as']) && $param['as'] == "array")?array($controller->get->$param['var']):
					$controller->get->$param['var'];
				if(isset($param->filter))
					$p = Filter::filter($p,(string)$param->filter);
				$this->params[] = $p;
			}
			elseif(isset($param['constant']))
			{
				$this->params[] = $param['constant'];
			}
		
		}
	}
	function getParams()
	{
		return $this->params;
	}
		

}
//}}}

//{{{ DataObject
abstract class DataObject
{
	protected 
		/**
		* @var  string
		*/
		$model = null,
		/**
		* @var  string
		*/
		$classname = null,
		/**
		* @var  boolean
		*/
		$is_static = false,
		/**
		* @var  string
		*/
		$init_method = "__construct",
		/**
		* @var  array
		*/
		$init_params = array(),
		/**
		* @var  stdClass&
		*/
		$object = null
		;

	function __construct($is_static = false)
	{
		$this->is_static = $is_static;
	}
	function parseParams(SimpleXMLElement $elem)
	{
		if(isset($elem->model))
			$this->model = $elem->model;
		else throw new DataObjectException("Model name for data object does not set");

		if(isset($elem->classname))
			$this->classname = $elem->classname;
		else throw new DataObjectException("Class name for data object does not set");

		if(isset($elem->static))
			$this->is_static = 0+(string)$elem->static;

		if(!$this->is_static && !isset($elem->init))
			throw new DataObjectException("Init section was not found");
	
		if(isset($elem->init))
		{
			if(isset($elem->init['method']))
				$this->init_method = $elem->init['method'];

			$this->init_params = new DataObjectParams($elem->init);		
		}
	}
	function createObject()
	{
		require_once(Config::get('ROOT_DIR')."/models/".$this->model."/autoload.php");
		$r = new ReflectionClass($this->classname);
		$this->object = $r->newInstanceArgs($this->init_params->getParams());
	}
}
// }}}

//{{{ DataSourceObject
class DataSourceObject extends DataObject
{
	protected
		/**
		* @var  string
		*/
		$datasource_method = null,
		/**
		* @var  array
		*/
		$datasource_params = array()
	;
	private
		$cache = null
		;
	function __construct($static = false)
	{
		parent::__construct($static);
	}
	function parseParams(SimpleXMLElement $elem)
	{
		parent::parseParams($elem);

		if(isset($elem->datasource))
		{

			if(isset($elem->datasource['method']))
				$this->datasource_method = $elem->datasource['method'];
			else
				throw new DataObjectException("Data source method was not found");

			$this->datasource_params = new DataObjectParams($elem->datasource);		
		}

	}
	function getData($w_id)
	{
		if(!$this->is_static)
		{
			if(!isset($this->object))
			{
				$this->createObject();
				if(($v = $this->findVlaueInObject($w_id)) !== false)
					return $v;
				if(isset($this->datasource_method))
					return call_user_func_array(array($this->object,$this->datasource_method),$this->datasource_params->getParams());
			}
		}
		else
		{
			if(($v = $this->findVlaueInStatic($w_id)) !== false)
				return $v;
			if(isset($this->datasource_method))
				return call_user_func_array($this->classname."::".$this->datasource_method,$this->datasource_params->getParams());
		}
	}
	function findValueInObject($w_id)
	{
		if(!isset($w_id) || !isset($this->object)) return false;

		if(property_exists($this->object,$w_id))
			return $this->object->$w_id;
		if(method_exists($this->object,"get".ucfirst(strtolower($w_id))))
			return call_user_func(array($this->object,"get".ucfirst(strtolower($w_id))));
		if(method_exists($this->object,"get_".strtolower($w_id)))
			return call_user_func(array($this->object,"get_".strtolower($w_id)));
		if(method_exists($this->object,$w_id))
			return call_user_func(array($this->object,$w_id));
		if(method_exists($this->object,ucfirst(strtolower($w_id))))
			return call_user_func(array($this->object,ucfirst(strtolower($w_id))));
		return false;
	}
	function findValueInStatic($w_id)
	{
		if(!isset($w_id) || !$this->is_static) return false;

		if(property_exists($this->object,$w_id))
			return $this->object->$w_id;
		if(method_exists($this->classname,"get".ucfirst(strtolower($w_id))))
			return call_user_func($this->classname."::get".ucfirst(strtolower($w_id)));
		if(method_exists($this->classname,"get_".strtolower($w_id)))
			return call_user_func($this->classname,"::get_".strtolower($w_id));
		if(method_exists($this->classname,$w_id))
			return call_user_func($this->classname."::".$w_id);
		if(method_exists($this->classname,ucfirst(strtolower($w_id))))
			return call_user_func($this->classname."::".ucfirst(strtolower($w_id)));
		return false;

	}
}
?>
