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

// $Id: DataObject.php 117 2009-06-14 15:14:12Z billy $

//{{{ DataObject
/**
 * Abstract class, parent for {@link DataSourceObject} and 
 * {@link DataHandlerObject}. It declares base facilities for those
 * classes. 
 * This class parses base params from XML node, passing by 
 * {@link WDataSet} or {@link WDataHandler}, manages the search
 * for a file to require and creates object
 * upon given parameters.
 */
abstract class DataObject
{
	protected 
		/**
		 * Holds full path to the directory where system should search 
		 * files to require. 
		 * @var  string
		 */
		$object_dir = null,
		/**
		 * Name of the class for current dataobject.
		 * @var  string
		 */
		$classname = null,
		/**
		 * Defines whenever methods should be called statically in the 
		 * kept model object.
		 * @var  boolean
		 */
		$is_static = false,
		/**
		 * Holds name of the method, that should be called after 
		 * constructing the object. If methods are calling statically, 
		 * it will be called first of all.
		 * @var  string
		 */
		$init_method = null,
		/**
		 * Parameters for passing to the init method, if it exists.
		 * @var  DataObjectParams
		 */
		$init_params = null,
		/**
		 * Holds name of the method that will return desired model object.
		 * It might be used to give control over the process of creating an object to 
		 * userland method.
		 * @var  string
		 */
		$factory_method = null,
		/**
		 * Defines whenever factory method should be called statically.
		 * @var  boolean
		 */
		$factory_method_static = false,
		/**
		 * Parameters for passing to the factory method if it exists.
		 * @var  DataObjectParams
		 */
		$factory_params = null,
		/**
		 * Name of the method that should be called to finalize all 
		 * changes, made by DataSourceObject or DataHandlerObject.
		 * @var  string
		 */
		$finalize_method = null,
		/**
		 * Parameter for passing to the finalize method if it exists.
		 * @var  DataObjectParams
		 */
		$finalize_params = null,
		/**
		 * Created object to manipulate with.
		 * @var  stdClass&
		 */
		$object = null
		;
	/**
	 * Need to indicated whenever classes have already been required.
	 */
	private $classes_required = false;

	//{{{ __construct
	/**
	 * Constructs this class and initialize init_params, factory_params, factory_params
	 *
	 * @param bool defines whenever methods should be called statically in the 
	 * kept model object.
	 * @return null
	 */
	function __construct($is_static = false)
	{
		$this->is_static = 0+$is_static;
		$this->init_params = new DataObjectParams();		
		$this->factory_params = new DataObjectParams();		
		$this->finalize_params = new DataObjectParams();		
	}
	//}}}

	//{{{ parseParams
	/**
	 * Parse parameters received from WDataSet or WDataHandler.
	 *
	 * It trying to find directory with the model's files to include. If the <model> section is defined
	 * 'models_dir' will be explored.
	 * If <vendor> section is defined, 'vendor_dir' will be explored.
	 *
	 * If <init> section is defined, parameters inside this section will be passed to constructor or
	 * method, specified by 'method' parameter. In all cases, __construct() method of the target object
	 * will be called.
	 *
	 * If you want to use factory method design pattern, use <factory> section instead. It defines
	 * 'method' attribute that will be called in order to retrieve object. 'static' attribute points
	 * to necessity to call 'method' statically. Parameters to this method are defined by the body of the section.
	 * 
	 * @param SimpleXMLElement params to parse.
	 * @return null
	 * @throws DataObjectException with explanations about the causes of errors.
	 */
	function parseParams(SimpleXMLElement $elem)
	{
		if(isset($elem->model))
			$this->object_dir = Config::get('ROOT_DIR').Config::get('models_dir')."/".(string)$elem->model;
		elseif(isset($elem->vendor)) 
			$this->object_dir = Config::get('ROOT_DIR').Config::get('vendors_dir')."/".(string)$elem->vendor;
		else
			throw new DataObjectException("Model name  or Vendor name for data object does not set");

		if(!is_dir($this->object_dir) || !is_readable($this->object_dir))
			throw new DataObjectException("Directory ".$this->object_dir." doesn't exists or not readable");

		if(isset($elem->classname))
			$this->classname = (string)$elem->classname;
		else throw new DataObjectException("Class name for data object does not set");

		if(isset($elem->static))
			$this->is_static = 0+(string)$elem->static;

		if(isset($elem->init))
		{
			if(isset($elem->init['method']))
				$this->init_method = (string)$elem->init['method'];

			$this->init_params = new DataObjectParams($elem->init);		
		}
		// if init section does not exists
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
	//}}}

	//{{{ requireClasses
	/**
	 * It tries to require model's files to find class, specified at the <classname>
	 * section.
	 *
	 * In trivial cases you should give name to file as the class has and place it 
	 * to the <model> directory. 
	 * For example
	 * <code>
	 * <model>news</model>
	 * <classname>NewsList</classname>
	 * </code>
	 * System will tries to find file NewsList.php in the "/models/news/" folder.
	 *
	 * If you want to use more complicated files layout, define autoload.php at the root 
	 * of the folders hierarchy (ie /models/news). Then the system will require this file and
	 * no other lookups will be occurred.
	 *
	 * @param null
	 * @return null
	 */
    protected function requireClasses()
    {
		if($this->classes_required) return;

        if(file_exists($this->object_dir."/autoload.php"))
            require_once($this->object_dir."/autoload.php");

        if(!class_exists($this->classname, false) && file_exists($this->object_dir."/".$this->classname.".php"))
            require_once($this->object_dir."/".$this->classname.".php");

		$this->classes_required = true;
    }
	//}}}

	//{{{ createObject
	/**
	 * On the basis of specified parameters this method will create an object.
	 *
	 * All introspection are made with ReflectionClass class.
	 *
	 * @param null
	 * @retrun bool It could be either true if object was created, or false if error occurred.
	 * @see parseParams
	 * @see requireClasses
	 */
	function createObject()
	{
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
	//}}}
	
	//{{{ getObject
	/**
	 * Returns object, created by the {@link createObject} method.
	 *
	 * @params null
	 * @return mixed It could be either instance of class, specified during the 
	 * setup in {@link parseParams} or null, if object have not been created.
	 */
    function getObject()
    {
        return $this->object;
    }
	//}}}

	//{{{ finalize
	/**
	 * This method will be called when all operations with the model's object
	 * completed. Finalize method could be called statically or dynamically,
	 * depending on the "static" flag.
	 *
	 * @param null
	 * @return null
	 */
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
	//}}}
}
// }}} 

