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

/**
 * This file contains class for managing object dedicated
 * for retrieving data from models.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */

//{{{ DataSourceObject
/**
 *
 * Holds parameters and object that should be polled to gather data to full the 
 * page (with widgets) with data.
 * This class describes dataset-specific rules for checking data and
 * managing it. For base workflow of processing see {@DataObject}.
 *
 * @see DataObject
 * @see DataHandlerObject
 */
class DataSourceObject extends DataObject
{
	protected
		/**
		 * Methods of the user's model to be polled.
		 *
		 * @var  array
		 */
		$datasource_methods = array(),
		/**
		 * Parameters for each of polling methods.
		 *
	  	 * @var  array of DataObjectParams
		 */
		$datasource_params = array()
	;

	//{{{ parseParams
	/**
	 * Parse params, coming directly from WDataSet object.
	 *
	 * Nodes, other from "<datasource>" are ignored.
	 * 
	 * @param SimpleXMLElement instance of WDataSet node of
	 * the document tree.
	 * @return null
	 */
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
	//}}}

	//{{{ hasDatasourceParamFrom
	/**
	 * Looking up in the array of created dataobject params
	 * for the parameter with given "from" attribute.
	 *
	 * I.e. hasDatasourceParamFrom("p2") will return true if the page
	 * has DataSet similar to this:
	 * <pre><code>
	 * <WDataSet>
	 *  <datasource method="qwe">
	 *	  <param from="p2"/>
	 *	</datasource>
	 * </WDataSet>
	 * </code></pre>
	 *
	 * @param string what to search
	 * @return bool true if such "param" was founded.
	 */ 
	function hasDatasourceParamFrom($from)
	{
        if(empty($this->datasource_params)) return false;
        foreach($this->datasource_params as $dsp)
            foreach($dsp->getParamsFrom() as $v)
                if($v == $from) return true;
		return false;
	}
	//}}}

	//{{{ getData
	/**
	 * Trying to poll given datasource methods to get {@link ResultSet} data.
	 *
	 * If the object was created with $static flag with true value, 
	 * all datasource methods in target model class will be called 
	 * statically (if method in the desired class doesn't declare
	 * as static, it won't be called silently). Otherwise object of 
	 * particular class will be created.
	 *
	 * All parameters, declared via <param> tag will be passed 
	 * in the order of document.
	 * 
	 * All operations to search method in object/class are made by 
	 * Reflection mechanism and ReflectionExceptions are suppressed.
	 *
	 * If no datasource methods are passed (but "datasource" attribute was introduced), 
	 * system will try to find model's object/class methods basing on the id of the widget.
	 * See {@link findValueInStatic} for static type, and 
	 * {@link findValueInObject} for non-static case.
	 */
	function getData($w_id = null)
	{
		// if flag $static equals false
		if(!$this->is_static)
		{
			if(!isset($this->object) && !$this->createObject()) return null;
			// if "<datasource method='...'>" is present
            if(!empty($this->datasource_methods))
            {
				$ret = array();
                foreach($this->datasource_methods as $ind => $method)
				{
					$dsp = $this->datasource_params[$ind];
                    $dsp->replaceLimitParams();
					try{
						$r = new ReflectionObject($this->object);
						$ret[] = $r->getMethod($method)->invokeArgs($this->object,$dsp->getParams());
					}catch(ReflectionException $e){}
                }
                return $ret;
            }
			//otherwise trying to find in object methods
            else
            if($w_id !== null && ($v = $this->findValueInObject($w_id)) !== false)
                return $v;
		}
		//static case
		else
        {
			// if "<datasource method='...'>" is present
            if(!empty($this->datasource_methods))
            {
                $this->requireClasses();

				$ret = array();
                foreach($this->datasource_methods as $ind => $method)
                {
					$dsp = $this->datasource_params[$ind];
					$dsp->replaceLimitParams();
                    try
                    {
                        $r = new ReflectionClass($this->classname);
                        if(!$r->getMethod($method)->isAbstract() && $r->getMethod($method)->isStatic())
                            $ret[] = call_user_func_array($this->classname."::".$method,$dsp->getParams());
                    }
                    catch(ReflectionException $e){return null;}

                }
                return $ret;
            }
			//otherwise trying to find in class static methods
            elseif(($v = $this->findValueInStatic($w_id)) !== false)
                return $v;
		}
		return null;
	}
	//}}}
	
	//{{{ findValueInObject
	/** 
	 * Trying to find data in the object when no datasource method is specified.
	 * It useful when model's object has various "getters" or direct property access and
	 * it's enough to poll these "getters" to fill-in widgets with data.
	 *
	 * For example, there is widget with id="tiT_le" on the page.
	 * Than system will be looking for methods:
	 * <ol>
	 * <li>Property <code>$tit_le</code></li>
	 * <li><code>public function get_tit_le();</code></li>
	 * <li><code>public function gettitle();</code></li>
	 * <li><code>public function tit_le();</code></li>
	 * <li>Property tit_le, hidden behind __get magick function (if it exists)</li>
	 * <li>Method tit_le, hidden behind __call magick function</li>
	 * </ol>
	 *
	 * Widgets, id of which begins with "__" considered as system and they will be skipped.
	 *
	 * @param string id of the widget
	 * @return mixed output of the method
	 * @see findValueInStatic
	 */
    function findValueInObject($w_id)
    {
		if(!isset($w_id) || ($w_id[0] === "_" && $w_id[1] === "_") || !isset($this->object)) return false;
        try
        {
            $w_id = strtolower($w_id);
            $ro = new ReflectionObject($this->object);
            if($ro->hasProperty($w_id) && @t($p = $ro->getProperty($w_id))->isPublic())
                return $p->getValue($this->object);
            if($ro->hasMethod("get".$w_id) && @t($m = $ro->getMethod("get".$w_id))->isPublic())
                return $m->invoke($this->object);
            if($ro->hasMethod("get".str_replace("_","",$w_id)) && @t($m = $ro->getMethod("get".str_replace("_","",$w_id)))->isPublic())
                return $m->invoke($this->object);
            if($ro->hasMethod("get_".$w_id) && @t($m = $ro->getMethod("get_".$w_id))->isPublic())
                return $m->invoke($this->object);
            if($ro->hasMethod($w_id) && @t($m = $ro->getMethod($w_id))->isPublic())
                return $m->invoke($this->object);
            if($ro->hasMethod("__get"))
                return $ro->getMethod("__get")->invoke($this->object,$w_id);
            if($ro->hasMethod("__call"))
                return $ro->getMethod("__call")->invoke($this->object,$w_id);
        }
        catch(ReflectionException $e){ return false;}

        return false;
	}
	//}}}

	//{{{ findValueInObject
	/** 
	 * Trying to find data in the class when no datasource method is specified.
	 * It useful when model's object has various "getters" or direct property access and
	 * it's enough to poll these "getters" to fill-in widgets with data.
	 *
	 * For example, there is widget with id="tiT_le" on the page.
	 * Than system will be looking for methods:
	 * <ol>
	 * <li>Property <code>static $tit_le</code></li>
	 * <li><code>public static function get_tit_le();</code></li>
	 * <li><code>public static function gettitle();</code></li>
	 * <li><code>public static function tit_le();</code></li>
	 * </ol>
	 *
	 * Widgets, id of which begins with "__" considered as system and they will be skipped.
	 *
	 * @param string id of the widget
	 * @return mixed output of the method
	 * @see findValueInObject
	 */
    function findValueInStatic($w_id)
    {
		if(!isset($w_id) || ($w_id[0] === "_" && $w_id[1] === "_") || !isset($this->object)) return false;
        try
        {
            $w_id = strtolower($w_id);
            $ro = new ReflectionClass($this->classname);
            if($ro->hasProperty($w_id) && @t($p = $ro->getProperty($w_id))->isPublic() && $p->isStatic())
                return $p->getValue(null);
            if($ro->hasMethod("get".$w_id) && @t($m = $ro->getMethod("get".$w_id))->isPublic() && $m->isStatic())
                return $m->invoke(null);
            if($ro->hasMethod("get".str_replace("_","",$w_id)) && @t($m = $ro->getMethod("get".str_replace("_","",$w_id)))->isStatic())
                return $m->invoke($this->object);
            if($ro->hasMethod("get_".$w_id) && @t($m = $ro->getMethod("get_".$w_id))->isPublic() && $m->isStatic())
                return $m->invoke(null);
            if($ro->hasMethod($w_id) && @t($m = $ro->getMethod($w_id))->isPublic() && $m->isStatic())
                return $m->invoke(null);
        }
        catch(ReflectionException $e){ return false;}

        return false;
    }
	//}}}

	//{{{ hasDatasourceMethod
	/**
	 * Determines whenever current datasource object has 
	 * datasource methods.
	 *
	 * @param null
	 * @return bool true, if it has at least one datasource.
	 */
	function hasDatasourceMethod()
	{
		return (bool)count($this->datasource_methods);
	}
	//}}}
}
//}}}
