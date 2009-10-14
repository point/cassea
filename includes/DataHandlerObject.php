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

// $Id:$

/**
 * This file contains class for managing incoming POST data.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */

//{{{ DataHandlerObject
/**
 * Holds parameters and object that should be triggered to process data, coming
 * from POST data in the request.
 * This class describes datahandler-specific rules for checking data and
 * managing it. For base workflow of processing see {@DataObject}
 *
 * @see DataObject
 * @see DataSourceObject
 */
class DataHandlerObject extends DataObject
{ 
	protected
		/**
		 * Methods, that should be triggered in the model.
		 * For convenience several methods could be called to
		 * achieve more flexibility of processing incoming data.
		 *
		 * This methods are described in "method" section of {@link WDataHandler}
		 * @var  array
		 */
		$handler_methods = array(),
		/**
		 * Array of {@link DataObjectParams} with parameters to each of methods,
		 * that should be called to process incoming data.
		 * @var  array
		 */
		$handler_params = array(),
		/**
		 * Array of methods which should check incoming data before processing and
		 * before passing to handler methods.
		 *
		 * @var  array
		 */
		$checker_methods = array(),
		/**
		 * Array of {@link DataObjectParams} with parameters to each of methods,
		 * that should be called to check data.
		 * @var  DataObjectParams
		 */
		$checker_params = array()

	;

	//{{{ __construct
	/**
	 * Constructs instance
	 *
	 * @param bool if true, methods of the checker and handler 
	 * would be called statically.
	 * @return null
	 */
	function __construct($static = false)
	{
		parent::__construct($static);
	}
	//}}}

	//{{{ parseParams
	/**
	 * Parse params, coming directly from WDataHandler object.
	 *
	 * @param SimpleXMLElement instance of WDataHandler node of
	 * the document tree.
	 * @return null
	 */
	function parseParams(SimpleXMLElement $elem)
	{
		parent::parseParams($elem);

		// looking for <handler>
        foreach($elem->handler as $handler)
		{
			if(isset($handler['method']))
				$this->handler_methods[] = (string)$handler['method'];
			else
				throw new DataObjectException("Handler method was not found");

		    $this->handler_params[] = new DataObjectParams($handler);		
		}
        
		// looking for <checker>
        foreach($elem->checker as $checker)
		{
			if(isset($checker['method']))
				$this->checker_methods[] = (string)$checker['method'];
			else
				throw new DataObjectException("Checker method was not found");

			$this->checker_params[] = new DataObjectParams($checker);		
		}
	}
	//}}}

	//{{{ check
	/**
	 * It called when POST data has come and declared checkers should be called 
	 * with this data as an argument. POST data encapsulates in HTTPParamHolder 
	 * object.
	 *
	 * If this this object was created with $static flag with true value, 
	 * all checker methods will be called statically. If method doesn't declare
	 * as static, it won't be called silently.
	 *
	 * $post always will be passed to checker method as first argument.
	 * All other parameters, declared via <param> tag will be passed 
	 * in the order of document.
	 * 
	 * All operations to search method in object/class are made by 
	 * Reflection mechanism and ReflectionExceptions are suppressed.
	 *
	 * @param HTTPParamHolder post data to check
	 * @return null
	 * @see handle
	 */
	function check(HTTPParamHolder $post)
	{
		if(!$this->is_static)
		{
			// lazy creation of target model object. 
			// RequireClasses will be called in createObject
			if(!isset($this->object) && !$this->createObject()) return null;
			foreach($this->checker_methods as $ind => $checker)
			{
				try{
					$r = new ReflectionObject($this->object);
					$arr = $this->checker_params[$ind]->getParams();
					// shifting $post to the 0 index of array $arr
					array_unshift($arr,$post);
					$r->getMethod($checker)->invokeArgs($this->object,$arr);
				}catch(ReflectionException $e){ return ;}
			}
		}
		// if not static
		else
        {
            $this->requireClasses();
            foreach($this->checker_methods as $ind => $checker)
            {
                try
                {
                    $r = new ReflectionClass($this->classname);
                    $arr = $this->checker_params[$ind]->getParams();
					// shifting $post to the 0 index of array $arr
                    array_unshift($arr,$post);
                    if(!$r->getMethod($checker)->isAbstract())
                        call_user_func_array($this->classname."::".$checker,$arr);
                }
                catch(ReflectionException $e){return; }
            }
		}
		return ;
	}
	//}}}

	//{{{ handle
	/**
	 * It called when POST data has come and it should be processed. 
	 * POST data encapsulates in HTTPParamHolder object.
	 *
	 * If this this object was created with $static flag with true value, 
	 * all handler methods in target model object will be called 
	 * statically. If given method doesn't declare
	 * as static, it won't be called silently.
	 *
	 * $post always will be passed to handler method as first argument.
	 * All other parameters, declared via <param> tag will be passed 
	 * in the order of document.
	 * 
	 * All operations to search method in object/class are made by 
	 * Reflection mechanism and ReflectionExceptions are suppressed.
	 *
	 * If no handler methods are passed, we will try to handle 
	 * post data basing on the POST fields names and 
	 * methods names in the models object/class. 
	 * See {@link handleInStatic} for static type, and 
	 * {@link handleInObject} for non-static.
	 *
	 * @param HTTPParamHolder post data to process
	 * @return null
	 * @see check
	 */
	function handle($post)
	{
		// if flag $static is false
		if(!$this->is_static)
		{
			// lazy creation of target model object. 
			// RequireClasses will be called in createObject
			if(!isset($this->object) && !$this->createObject()) return null;

			// processing at usual way
            if(!empty($this->handler_methods))
                foreach($this->handler_methods as $ind => $handler)
                    try{
                        $r = new ReflectionObject($this->object);
                        $arr = $this->handler_params[$ind]->getParams();
                        array_unshift($arr,$post);
                        $r->getMethod($handler)->invokeArgs($this->object,$arr);
                    }catch(ReflectionException $e){ return ;}
			// trying ot guess handler's methods names
			elseif(!empty($post))
				foreach($post as $name=>$value)
					$this->handleInObject($name,$value);
		}
		// static
		else
		{
            $this->requireClasses();
			// processing at usual way
            if(!empty($this->handler_methods))
                foreach($this->handler_methods as $ind => $handler)
                    try
                    {
                        $r = new ReflectionClass($this->classname);
                            $arr = $this->handler_params[$ind]->getParams();
                            array_unshift($arr,$post);
                        if(!$r->getMethod($handler)->isAbstract())
                            call_user_func_array($this->classname."::".$handler,$arr);
                    }
                    catch(ReflectionException $e){return; }
			// trying ot guess handler's methods names
			elseif(!empty($post))
				foreach($post as $name=>$value)
					$this->handleInStatic($name,$value);
		}
		return;
	}
	//}}}

	//{{{ handleInObject
	/**
	 * If no handler methods was found, it  will be called.
	 * This method will search "set_smth" or "setSmth" methods to
	 * handle "smth" field, coming with the POST. 
	 *
	 * Particular method called when DataHandlerObject is used in 
	 * non-static context.
	 *
	 * @param string name of the filed in POST
	 * @param mixed value of that field. In most cases it will be
	 * string or array.
	 * @return null
	 * @see handle
	 * @see handleInStatic
	 */
	protected function handleInObject($name,$value)
	{
		if(!isset($name) || !is_object($this->object)) return;

		if(method_exists($this->object,"set".strtolower($name)))
			return call_user_func(array($this->object,"set".strtolower($name)),$value);
		if(method_exists($this->object,"set_".strtolower($name)))
			return call_user_func(array($this->object,"set_".strtolower($name)),$value);
	}
	//}}}
	
	//{{{ handleInStatic
	/**
	 * As a {@link handleInObject} it will be called, if 
	 * no handler methods was found. 
	 * This method will search "set_smth" or "setSmth" methods to
	 * handle "smth" field, coming with the POST. 
	 *
	 * Particular method called when DataHandlerObject is used in 
	 * static context.
	 *
	 * @param string name of the filed in POST
	 * @param mixed value of that field. In most cases it will be
	 * string or array.
	 * @return null
	 * @see handle
	 * @see handleInStatic
	 */
	protected function handleInStatic($name,$value)
	{
		if(!isset($name) || !$this->is_static) return;

		if(method_exists($this->classname,"set".strtolower($name)))
			return call_user_func($this->classname."::set".strtolower($name),$value);
		if(method_exists($this->classname,"set_".strtolower($name)))
			return call_user_func($this->classname,"::set_".strtolower($name),$value);
		return;

	}
	//}}}
}
//}}}
