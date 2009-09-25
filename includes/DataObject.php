<?php
/*- vim:noet:shiftwidth=4:tabstop=4: 
{{{ LICENSE  
* Copyright (c) 2008,2009 Cassea Project
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

//{{{ DataObject
abstract class DataObject
{
	protected 
		/**
		* @var  string
		*/
		$objectDir = null,
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
		$init_params = null,
		/**
		* @var  string
		*/
		$factory_method = null,
		/**
		* @var  boolean
		*/
		$factory_method_static = false,
		/**
		* @var  array
		*/
		$factory_params = null,
		/**
		* @var  string
		*/
		$finalize_method = null,
		/**
		* @var  array
		*/
		$finalize_params = null,
		/**
		* @var  stdClass&
		*/
		$object = null
		;
	private $classes_required = false;

	function __construct($is_static = false)
	{
		$this->is_static = 0+$is_static;
		$this->init_params = new DataObjectParams();		
		$this->factory_params = new DataObjectParams();		
		$this->finalize_params = new DataObjectParams();		
	}
	function parseParams(SimpleXMLElement $elem)
	{
		if(isset($elem->model))
			$this->objectDir = Config::get('ROOT_DIR')."/models/".(string)$elem->model;
		elseif(isset($elem->vendor)) 
			$this->objectDir = Config::get('ROOT_DIR').Config::get('vendors_dir')."/".(string)$elem->vendor;
		else
			throw new DataObjectException("Model name  or Vendor name for data object does not set");

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
        elseif(isset($elem->factory))
        {
			if(isset($elem->factory['method']))
				$this->factory_method = (string)$elem->factory['method'];
            if(isset($elem->factory['static']))
                $this->factory_method_static = (string)$elem->factory['static'];

			$this->factory_params = new DataObjectParams($elem->factory);		
        }

		if(isset($elem->finalize))
		{
			if(isset($elem->finalize['method']))
				$this->finalize_method = (string)$elem->finalize['method'];

			$this->finalize_params = new DataObjectParams($elem->finalize);		
        }
	}
    protected function requireClasses()
    {
		if($this->classes_required) return;

        if(file_exists($this->objectDir."/autoload.php"))
            require_once($this->objectDir."/autoload.php");

        if(!class_exists($this->classname, false) && file_exists($this->objectDir."/".$this->classname.".php"))
            require_once($this->objectDir."/".$this->classname.".php");

		$this->classes_required = true;
    }
	function createObject()
	{
        /*if(file_exists(Config::get('ROOT_DIR')."/models/".$this->model."/autoload.php"))
            require_once(Config::get('ROOT_DIR')."/models/".$this->model."/autoload.php");

        if(!class_exists($this->classname) && file_exists(Config::get('ROOT_DIR')."/models/".$this->model."/".$this->classname.".php"))
            require_once(Config::get('ROOT_DIR')."/models/".$this->model."/".$this->classname.".php");*/
        $this->requireClasses();

		try{
			$r = new ReflectionClass($this->classname);
			if(isset($this->init_method))
			{	
				$this->object = $r->newInstance();
				if($this->init_params->getParams())
					call_user_func_array(array($this->object,$this->init_method),$this->init_params->getParams());
				else
					call_user_func_array(array($this->object,$this->init_method),array());
            }
            elseif(isset($this->factory_method))
            {
                if($this->factory_params->getParams())
                    if($this->factory_method_static && $r->getMethod($this->factory_method)->isStatic())
                        $this->object = call_user_func_array($this->classname."::".$this->factory_method,$this->factory_params->getParams());
                    else
                        $this->object = call_user_func_array(array($r->newInstance(),$this->factory_method),$this->factory_params->getParams());
                else 
                    if($this->factory_method_static && $r->getMethod($this->factory_method)->isStatic())
                        $this->object = call_user_func($this->classname."::".$this->factory_method);
                    else
                        $this->object = call_user_func(array($r->newInstance(),$this->factory_method));
                if(!is_object($this->object))
                {$this->object = null;return false;}
            }
            else
				if($this->init_params->getParams())
                    $this->object = $r->newInstanceArgs($this->init_params->getParams());
				else 
                    $this->object = $r->newInstance();
        }catch(ReflectionException $e){ return false;}
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
					if(!$r->getMethod($this->finalize_method)->isAbstract())
						call_user_func_array($this->classname."::".$this->finalize_method,$this->finalize_params->getParams());
				}
				catch(ReflectionException $e){return; }
		}
		return ;
	}
}
// }}} 

