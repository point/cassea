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

// $Id$
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
		$finalize_method = null,
		/**
		* @var  array
		*/
		$finalize_params = array(),
		/**
		* @var  stdClass&
		*/
		$object = null
		;

	function __construct($is_static = false)
	{
		$this->is_static = 0+$is_static;
		$this->init_params = new DataObjectParams();		
		$this->finalize_params = new DataObjectParams();		
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

		if(isset($elem->finalize))
		{
			if(isset($elem->finalize['method']))
				$this->finalize_method = (string)$elem->finalize['method'];

			$this->finalize_params = new DataObjectParams($elem->finalize);		
		}
	}
	function createObject()
	{
        if(file_exists(Config::get('ROOT_DIR')."/models/".$this->model."/autoload.php"))
            require_once(Config::get('ROOT_DIR')."/models/".$this->model."/autoload.php");

        if(!class_exists($this->classname) && file_exists(Config::get('ROOT_DIR')."/models/".$this->model."/".$this->classname.".php"))
            require_once(Config::get('ROOT_DIR')."/models/".$this->model."/".$this->classname.".php");

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
        }catch(Exception $e){ return false;}
        return $this->object !== null;
    }
    function getObject()
    {
        return $this->object;
    }

	function finalize()
	{
		if(!$this->is_static)
		{
			if(!isset($this->object) && !$this->createObject()) return;
			if(isset($this->finalize_method))
			{
				try{
					$r = new ReflectionObject($this->object);
					$r->getMethod($this->finalize_method)->invokeArgs($this->object,$this->finalize_params->getParams());
				}catch(ReflectionException $e){ return ;}
			}
		}
		else
		{
			if(isset($this->finalize_method))
				try
				{
					$r = new ReflectionClass($this->classname);
					if($r->getMethod($this->finalize_method)->isAbstract())
						call_user_func_array($this->classname."::".$this->finalize_method,$this->finalize_params->getParams());
				}
				catch(ReflectionException $e){return; }
		}
		return ;
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
		$datasource_methods = array(),
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
        foreach($elem->datasource as $ds)
        {
            if(isset($ds['method']))
                $this->datasource_methods[] = (string)$ds['method'];
            else
                throw new DataObjectException("Data source method was not found");

            $this->datasource_params[] = new DataObjectParams($ds);		
        }
	}
	function hasDatasourceParamFrom($from)
	{
        if(empty($this->datasource_params)) return false;
        foreach($this->datasource_params as $dsp)
            foreach($dsp->getParamsFrom() as $v)
                if($v == $from) return true;
		return false;
	}
	function getData($w_id = null)
	{
		if(!$this->is_static)
		{
			if(!isset($this->object) && !$this->createObject()) return null;
            $ret = array();
            if(!empty($this->datasource_methods))
            {
                foreach($this->datasource_params as $ind => $dsp)
                {
                    $dsp->replaceLimitParams();
                    if(isset($this->datasource_methods[$ind]))
                    {
                        try{
                            $r = new ReflectionObject($this->object);
                            $ret[] = $r->getMethod($this->datasource_methods[$ind])->invokeArgs($this->object,$dsp->getParams());
                        }catch(Exception $e){}
                    }
                }
                return $ret;
            }
            else
            if($w_id !== null && ($v = $this->findValueInObject($w_id)) !== false)
                return $v;
		}
		else
        {
            if(!empty($this->datasource_methods))
            {
                $ret = array();
                foreach($this->datasource_methods as $ind => $method)
                {
                    try
                    {
                        $r = new ReflectionClass($this->classname);
                        if($r->getMethod($method)->isAbstract())
                            $ret[] = call_user_func_array($this->classname."::".$method,$this->datasource_params[$ind]->getParams());
                    }
                    catch(ReflectionException $e){return null;}

                }
                return $ret;
            }
            elseif(($v = $this->findVlaueInStatic($w_id)) !== false)
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
        if(method_exists($this->object,"__get"))
            return $this->object->$w_id;
        if(method_exists($this->object,"__call"))
            return call_user_func(array($this->object,strtolower($w_id)));
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
		return count($this->datasource_methods);
	}
}
// }}}
//{{{ DataHandlerObject
class DataHandlerObject extends DataObject
{
	protected
		/**
		* @var  string
		*/
		$handler_methods = array(),
		/**
		* @var  DataObjectParams
		*/
		$handler_params = array(),
		/**
		* @var  string
		*/
		$checker_methods = array(),
		/**
		* @var  DataObjectParams
		*/
		$checker_params = array()

	;

	function __construct($static = false)
	{
		parent::__construct($static);
	}
	function parseParams(SimpleXMLElement $elem)
	{
		parent::parseParams($elem);

        foreach($elem->handler as $handler)
		{
			if(isset($handler['method']))
				$this->handler_methods[] = (string)$handler['method'];
			else
				throw new DataObjectException("Handler method was not found");

		    $this->handler_params[] = new DataObjectParams($handler);		
		}
        
        foreach($elem->checker as $checker)
		{
			if(isset($checker['method']))
				$this->checker_methods[] = (string)$checker['method'];
			else
				throw new DataObjectException("Checker method was not found");

			$this->checker_params[] = new DataObjectParams($checker);		
		}
	}

	function check(HTTPParamHolder $post)
	{
		if(!$this->is_static)
		{
			if(!isset($this->object) && !$this->createObject()) return null;
			foreach($this->checker_methods as $ind => $checker)
			{
				try{
					$r = new ReflectionObject($this->object);
					$arr = $this->checker_params[$ind]->getParams();
					array_unshift($arr,$post);
					$r->getMethod($checker)->invokeArgs($this->object,$arr);
				}catch(ReflectionException $e){ return ;}
			}
		}
		else
        {
            foreach($this->checker_methods as $ind => $checker)
            {
                try
                {
                    $r = new ReflectionClass($this->classname);
                    $arr = $this->checker_params[$ind]->getParams();
                    array_unshift($arr,$post);
                    if($r->getMethod($checker)->isAbstract())
                        call_user_func_array($this->classname."::".$checker,$arr);
                }
                catch(ReflectionException $e){return; }
            }
		}
		return ;
	}

	function handle($post)
	{
		if(!$this->is_static)
		{
			if(!isset($this->object) && !$this->createObject()) return null;
            if(!empty($this->handler_methods))
                foreach($this->handler_methods as $ind => $handler)
                    try{
                        $r = new ReflectionObject($this->object);
                        $arr = $this->handler_params[$ind]->getParams();
                        array_unshift($arr,$post);
                        $r->getMethod($handler)->invokeArgs($this->object,$arr);
                    }catch(ReflectionException $e){ return ;}
			elseif(!empty($post))
				foreach($post as $name=>$value)
					$this->handleInObject($name,$value);
		}
		else
		{
            if(!empty($this->handler_methods))
                foreach($this->handler_methods as $ind => $handler)
                    try
                    {
                        $r = new ReflectionClass($this->classname);
                            $arr = $this->handler_params[$ind]->getParams();
                            array_unshift($arr,$post);
                        if($r->getMethod($handler)->isAbstract())
                            call_user_func_array($this->classname."::".$handler,$arr);
                    }
                    catch(ReflectionException $e){return; }
			elseif(!empty($post))
				foreach($post as $name=>$value)
					$this->handleInStatic($name,$value);
		}
		return null;
	}

	protected function handleInObject($name,$value)
	{
		if(!isset($name) || !is_object($this->object)) return;

		if(method_exists($this->object,"set".ucfirst(strtolower($name))))
			return call_user_func(array($this->object,"set".ucfirst(strtolower($name))),$value);
		if(method_exists($this->object,"set_".strtolower($name)))
			return call_user_func(array($this->object,"set_".strtolower($name)),$value);
	}
	protected function handleInStatic($name,$value)
	{
		if(!isset($name) || !$this->is_static) return;

		if(method_exists($this->classname,"set".ucfirst(strtolower($name))))
			return call_user_func($this->classname."::set".ucfirst(strtolower($name)),$value);
		if(method_exists($this->classname,"set_".strtolower($name)))
			return call_user_func($this->classname,"::set_".strtolower($name),$value);
		return;

	}
}
// }}}

//{{{ PageHandlerObject
class PageHandlerObject extends DataObject
{
	protected
		/**
		* @var  string
		*/
		$handler_method = null,
		/**
		* @var  DataObjectParams
		*/
		$handler_params = null

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
	}

	function handle()
	{
		if(!$this->is_static)
		{
			if(!isset($this->object) && !$this->createObject()) return null;
            
			if(isset($this->handler_method))
			{
				try{
					$r = new ReflectionObject($this->object);
					return $r->getMethod($this->handler_method)->invokeArgs($this->object,$this->handler_params->getParams());
                }catch(ReflectionException $e){ return ;}
			}
		}
		else
		{
			if(isset($this->handler_method))
				try
				{
					$r = new ReflectionClass($this->classname);
					if($r->getMethod($this->handler_method)->isAbstract())
						return call_user_func_array($this->classname."::".$this->handler_method,$this->handler_params->getParams());
				}
				catch(ReflectionException $e){return; }
		}
		return null;
	}
}
// }}}
?>
