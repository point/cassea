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

// $Id: $
//


class DataObjectException extends Exception{}

//{{{ DataObjectParams
class DataObjectParams
{
	protected 
		/**
		 * @var  array
		 */
		$params = array(),
		$params_from = array()
		;
	function __construct(SimpleXMLElement $elem = null)
	{
		if(!isset($elem,$elem->param)) return;

		$controller = Controller::getInstance();
		foreach($elem->param as $param)
		{
			if($param['from'] == "p1")
			{
				$this->params_from[] = "p1";
				//$p = (isset($param['as']) && $param['as'] == "array")?array($controller->p1):$controller->p1;
				$p = $controller->p1;
				if(isset($param->filter))
					$p = Filter::filter($p,(string)$param->filter);
				if((isset($param['as']) && $param['as'] == "array"))
					$this->params[] = array($p);
				else $this->params[] = $p;
			}
			elseif($param['from'] == "p2")
			{
				$this->params_from[] = "p2";

				$c = count($controller->p2);
				if(isset($param['count']))
					$c = abs(0+$param['count']);

				$p = array();
				for($i = 0; $i < $c;$i++)
				{
					//if(!isset($controller->p2[$i])) continue;

					$p[$i] = isset($controller->p2[$i])?$controller->p2[$i]:null;
					if(isset($param->filter[$i]))
						$p[$i] = Filter::filter($p[$i],(string)$param->filter[$i]);
				}
				if(isset($param['as']) && $param['as'] == "array")
					$this->params[] = $p;
				else
					foreach($p as $_p)
						$this->params[] = $_p;
			}
			elseif($param['from'] == "p3" && isset($param['var']))
			{
				$this->params_from[] = "p3";

				/*$p = (isset($param['as']) && $param['as'] == "array")?array($controller->get->$param['var']):
					$controller->get->$param['var'];*/
				$p = $controller->get->$param['var'];
				if(isset($param->filter))
					$p = Filter::filter($p,(string)$param->filter);
				if(isset($param['as']) && $param['as'] == "array")
					$this->params[] = array($p);
				else $this->params[] = $p;
			}
			elseif(isset($param['constant']))
			{
				$this->params_from[] = "constant";

				$p = (string)$param['constant'];
				if(isset($param->filter))
					$p = Filter::filter($p,(string)$param->filter);
				$this->params[] = $p;

			}
			elseif($param['from'] == "limit")
			{
				$this->params_from[] = "limit";
				$this->params[] = array();
			}
		
		}
	}
	function getParams()
	{
		return $this->params;
	}
	function getParamsFrom()
	{
		return $this->params_from;
	}
	function replaceLimitParams()
	{
		$controller = Controller::getInstance();
		foreach($this->params_from as $k=>$v)
			if($v == "limit")
				$this->params[$k] = array(	'from'=>$controller->getDisplayModeParams()->predicted_from,
					'limit'=>$controller->getDisplayModeParams()->predicted_limit);
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
		$init_method = null,
		/**
		* @var  array
		*/
		$init_params = array(),
		/**
		* @var  string
		*/
		$finilize_method = null,
		/**
		* @var  array
		*/
		$finilize_params = array(),
		/**
		* @var  stdClass&
		*/
		$object = null
		;

	function __construct($is_static = false)
	{
		$this->is_static = 0+$is_static;
		$this->init_params = new DataObjectParams();		
		$this->finilize_params = new DataObjectParams();		
	}
	function parseParams(SimpleXMLElement $elem)
	{
		if(isset($elem->model))
			$this->model = (string)$elem->model;
		else throw new DataObjectException("Model name for data object does not set");

		if(isset($elem->classname))
			$this->classname = (string)$elem->classname;
		else throw new DataObjectException("Class name for data object does not set");

		if(isset($elem->static))
			$this->is_static = 0+(string)$elem->static;

		/*if(!$this->is_static && !isset($elem->init))
			throw new DataObjectException("Init section was not found");*/
	
		if(isset($elem->init))
		{
			if(isset($elem->init['method']))
				$this->init_method = (string)$elem->init['method'];

			$this->init_params = new DataObjectParams($elem->init);		
		}

		if(isset($elem->finilize))
		{
			if(isset($elem->finilize['method']))
				$this->finilize_method = (string)$elem->finilize['method'];

			$this->finilize_params = new DataObjectParams($elem->finilize);		
		}
	}
	function createObject()
	{
		require_once(Config::get('ROOT_DIR')."/models/".$this->model."/autoload.php");
		try{
			$r = new ReflectionClass($this->classname);
			if(!isset($this->init_method))
				if($this->init_params->getParams())
					$this->object = $r->newInstanceArgs($this->init_params->getParams());
				else 
					$this->object = $r->newInstance();
			else
			{	
				$this->object = $r->newInstance();
				if($this->init_params->getParams())
					call_user_func_array(array($this->object,$this->init_method),$this->init_params->getParams());
				else
					call_user_func_array(array($this->object,$this->init_method),array());
			}
		}catch(Exception $e){}
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
		$datasource_params = null
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
				$this->datasource_method = (string)$elem->datasource['method'];
			else
				throw new DataObjectException("Data source method was not found");

			$this->datasource_params = new DataObjectParams($elem->datasource);		
		}

	}
	function hasDatasourceParamFrom($from)
	{
		if(!isset($this->datasource_params)) return false;
		foreach($this->datasource_params->getParamsFrom() as $v)
			if($v == $from) return true;
		return false;
	}
	function getData($w_id = null)
	{
		if(!$this->is_static)
		{
			if(!isset($this->object))
				$this->createObject();
			if(isset($this->datasource_params))
				$this->datasource_params->replaceLimitParams();
			if(isset($this->datasource_method))
			{
				try{
					$r = new ReflectionObject($this->object);
					return $r->getMethod($this->datasource_method)->invokeArgs($this->object,$this->datasource_params->getParams());
				}catch(Exception $e){}
			}
			if($w_id !== null && ($v = $this->findValueInObject($w_id)) !== false && is_scalar($v))
				return $v;
		}
		else
		{
			if(isset($this->datasource_method))
			{
				try
				{
					$r = new ReflectionClass($this->classname);
					if($r->getMethod($this->datasource_method)->isAbstract())
						return call_user_func_array($this->classname."::".$this->datasource_method,$this->datasource_params->getParams());
					else return null;
				}
				catch(ReflectionException $e){return null;}
			}

			if(($v = $this->findVlaueInStatic($w_id)) !== false)
				return $v;
		}
		return null;
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
	function hasDatasourceMethod()
	{
		return $this->datasource_method !== null;
	}
}
//{{{ CheckerException
class CheckerException extends Exception
{
	protected 
			$widget_name = null,
			$additional_id = null
			;
	function __construct($message = null, $widget_name = null,$additional_id = null)
	{
		parent::__construct($message,1);
		$this->widget_name = $widget_name;
		$this->additional_id = $additional_id;
	}
	function getWidgetName()
	{
		return $this->widget_name;
	}
	function getAdditionalId()
	{
		return $this->additional_id;
	}
}
//}}}
//{{{ DataHandlerObject
class DataHandlerObject extends DataObject
{
	protected
		/**
		* @var  string
		*/
		$handler_method = null,
		/**
		* @var  DataObjectParams
		*/
		$handler_params = null,
		/**
		* @var  string
		*/
		$checker_method = null,
		/**
		* @var  DataObjectParams
		*/
		$checker_params = null

	;

	function __construct($static = false)
	{
		parent::__construct($static);
	}
	function parseParams(SimpleXMLElement $elem)
	{
		parent::parseParams($elem);

		if(isset($elem->handler))
		{
			if(isset($elem->handler['method']))
				$this->handler_method = (string)$elem->handler['method'];
			else
				throw new DataObjectException("Handler method was not found");
		}
		$this->handler_params = new DataObjectParams($elem->handler);		

		if(isset($elem->checker))
		{
			if(isset($elem->checker['method']))
				$this->checker_method = (string)$elem->checker['method'];
			else
				throw new DataObjectException("Checker method was not found");
		}
			$this->checker_params = new DataObjectParams($elem->checker);		
	}

	function check(HTTPParamHolder $post)
	{
		if(!$this->is_static)
		{
			if(!isset($this->object))
				$this->createObject();
			if(isset($this->checker_method))
			{
				try{
					$r = new ReflectionObject($this->object);
					$arr = $this->checker_params->getParams();
					array_unshift($arr,$post);
					$r->getMethod($this->checker_method)->invokeArgs($this->object,$arr);
				}catch(ReflectionException $e){ return ;}
			}
		}
		else
		{
			try
			{
				$r = new ReflectionClass($this->classname);
				$arr = $this->checker_params->getParams();
				array_unshift($arr,$post);
				if($r->getMethod($this->checker_method)->isAbstract())
					call_user_func_array($this->classname."::".$this->checker_method,$arr);
			}
			catch(ReflectionException $e){return; }
		}
		return ;
	}

	function handle($post)
	{
		if(!$this->is_static)
		{
			if(!isset($this->object))
				$this->createObject();
			if(isset($this->handler_method))
			{
				try{
					$r = new ReflectionObject($this->object);
					$arr = $this->checker_params->getParams();
					array_unshift($arr,$post);
					$r->getMethod($this->handler_method)->invokeArgs($this->object,$arr);
				}catch(ReflectionException $e){ return ;}
			}
			else
				foreach($post as $name=>$value)
					$this->handleInObject($name,$value);
		}
		else
		{
			if(isset($this->handler_method))
				try
				{
					$r = new ReflectionClass($this->classname);
						$arr = $this->handler_params->getParams();
						array_unshift($arr,$post);
					if($r->getMethod($this->handler_method)->isAbstract())
						call_user_func_array($this->classname."::".$this->handler_method,$arr);
				}
				catch(ReflectionException $e){return; }
			else
				foreach($post as $name=>$value)
					$this->handleInStatic($name,$value);
		}
		return null;
	}

	function handleInObject($name,$value)
	{
		if(!isset($name) || !isset($this->object)) return;

		if(method_exists($this->object,"set".ucfirst(strtolower($name))))
			return call_user_func(array($this->object,"set".ucfirst(strtolower($name))),$value);
		if(method_exists($this->object,"set_".strtolower($name)))
			return call_user_func(array($this->object,"set_".strtolower($name)),$value);
	}
	function handleInStatic($name,$value)
	{
		if(!isset($name) || !$this->is_static) return;

		if(method_exists($this->classname,"set".ucfirst(strtolower($name))))
			return call_user_func($this->classname."::set".ucfirst(strtolower($name)),$value);
		if(method_exists($this->classname,"set_".strtolower($name)))
			return call_user_func($this->classname,"::set_".strtolower($name),$value);
		return;

	}
	function finilize()
	{
		if(!$this->is_static)
		{
			if(!isset($this->object))
				$this->createObject();
			if(isset($this->finilize_method))
			{
				try{
					$r = new ReflectionObject($this->object);
					$r->getMethod($this->finilize_method)->invokeArgs($this->object,$this->finilize_params->getParams());
				}catch(ReflectionException $e){ return ;}
			}
		}
		else
		{
			if(isset($this->finilize_method))
				try
				{
					$r = new ReflectionClass($this->classname);
					if($r->getMethod($this->finilize_method)->isAbstract())
						call_user_func_array($this->classname."::".$this->finilize_method,$this->finilize_params->getParams());
				}
				catch(ReflectionException $e){return; }
		}
		return ;
	}
}
?>
