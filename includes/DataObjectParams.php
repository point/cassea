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
 * This file contains class which retrieves and passes parameters
 * to the user's model.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */

//{{{ DataObjectParams
/**
 * Encapsulates logic of data retrieval from
 * "p1", "p2" and GET parameters. It also 
 * filters and re-arrange data in convenience way for
 * particular model and it's methods arguments.
 */
class DataObjectParams
{
	protected 
		/**
		 * Collected parameters.
		 *
		 * @var  array
		 */
		$params = array(),
		/**
		 * Cache information about the source of the data.
		 * @var array
		 */
		$params_from = array()
		;
	//{{{ __construct
	/**
	 * Parses given XML node and extracting info about from where to
	 * take parameters for user's models methods.
	 * Additionally, it filters and re-arrange this data to give more
	 * flexibility to the models design.
	 *
	 * Base syntax is 
	 * <pre><code>
	 * <param from="p2" count="2" offset="1">
	 *		<filter>int</filter>
	 * </param>
	 * </code></pre>
	 *
	 * It takes two parameters ("count" attribute), starting from 
	 * the second ("offset" attribute). Indexing is zero-based.  
	 * Then, received values  might be passed to, for example, dataset as first and second parameters.
	 * Besides, {@link Filter} int applies to each parameter. If parameters were 
	 * strings, after applying they becomes NULL values.
	 *
	 * Offset and count attributes are valid only while retrieving "p2" parameters.
	 *
	 * Another example:
	 * <pre><code>
	 * <param from="p2" count="2" offset="1" as="array">
	 *		<filter>array_int</filter>
	 * </param>
	 * </code></pre>
	 *
	 * Here "as" attribute was presented. If "as" equals to "array", than collected data will be
	 * passed to the model's method as single parameter as int-indexed array. "array_int" 
	 * filter will be applied to the obtained array. Applying "int" filter int this case will cause
	 * to receiving a NULL value.
	 *
	 * To receive "p1" parameter, you should use this syntax:
	 * <pre><code>
	 * <param from="p1">
	 *		<filter>int</filter>
	 * </param>
	 * </code></pre>
	 *
	 * Specifying "count" and "offset" attributes will give no effect. There is single "p1" parameter.
	 * It also may be filtered.
	 *
	 * System allows you to retrieve certain $_GET parameters. To do so use such syntax:
	 * <pre><code>
	 * <param from="p3" var="q">
	 *		<filter>int</filter>
	 * </param>
	 * </code></pre>
	 *
	 * For example, if current url is <code>http://example.com/index.html?q=123</code>, value "123" will be
	 * passed to the model's method.
	 *
	 * If you want to pass some constant value to the method (ie some flag), use syntax:
	 * <pre><code>
	 * <param constant="1">
	 * </param>
	 * </code></pre>
	 *
	 * Internal pagination system could pass predicted 'from' and 'limit' values to the model.
	 * Use:
	 * <pre><code>
	 * <param from="limit">
	 * </param>
	 * </code></pre>
	 * In this case, <code>array("from"=>$from, "limit"=>$limit)</code> will be passed.
	 *
	 * @param SimpleXMLElement XML document node with parameters.
	 * @return null
	 */
	function __construct(SimpleXMLElement $elem = null)
	{
		if(!isset($elem,$elem->param)) return;

		$controller = Controller::getInstance();
		$p2_cursor = 0;
		foreach($elem->param as $param)
		{
			if($param['from'] == "p1")
			{
				$this->params_from[] = "p1";
				$p = $controller->p1;
				if((isset($param['as']) && $param['as'] == "array"))
					$p = array($p); //slightly idiotic usage
				if(isset($param->filter))
					$p = Filter::apply($p,(string)$param->filter);
				$this->params[] = $p;
			}
			elseif($param['from'] == "p2")
			{
				$total_count = count($controller->p2);
				if(isset($param['offset']))
					if((int)$param['offset'] > 0)
						$p2_cursor = (int)$param['offset'];
					elseif((int)$param['offset'] < 0)
						$p2_cursor = max(0,$total_count - abs((int)$param['offset']));

				if(isset($param['regexp']) && preg_match($param['regexp'],$controller->p2[$p2_cursor],$matches))
				{
					$p = null;
					if(isset($matches['param']))
						$p  = $matches['param'];
					elseif(isset($matches[1]))
						$p = $matches[1];
					
					$this->params[] = isset($param->filter)?Filter::apply($p,(string)$param->filter):$p;
					$this->params_from[] = "p2";
				}
				else
				{
				$c = 1;
				if(isset($param['count']))
					$c = abs(0+$param['count']);

				$p = array();
				for($i = 0; $i < $c;$p2_cursor++,$i++)
					$p[$i] = isset($controller->p2[$p2_cursor])?$controller->p2[$p2_cursor]:null;

				if(isset($param['as']) && $param['as'] == "array")
				{
					$this->params[] = isset($param->filter)?Filter::apply($p,(string)$param->filter):$p;
					$this->params_from[] = "p2";
				}
				else
					foreach($p as $_p)
					{
						$this->params[] = isset($param->filter)?Filter::apply($_p,(string)$param->filter):$_p;
						$this->params_from[] = "p2";
						}
					}
			}
			elseif($param['from'] == "p3" && isset($param['var']))
			{
				$this->params_from[] = "p3";

				$p = $controller->get->$param['var'];
				if(isset($param['as']) && $param['as'] == "array")
					$p = array($p);
				if(isset($param->filter))
					$p = Filter::apply($p,(string)$param->filter);

				$this->params[] = $p;
			}
			elseif(isset($param['constant']))
			{
				$this->params_from[] = "constant";

				$p = (string)$param['constant'];
				if(isset($param->filter))
					$p = Filter::apply($p,(string)$param->filter);
				$this->params[] = $p;

			}
			elseif($param['from'] == "limit")
			{
				$this->params_from[] = "limit";
				$this->params[] = array();
			}
		
		}
	}
	//}}}
	
	//{{{ getParams
	/**
	 * Returns collected parameters in the form, compatible
	 * with method calling mechanism.
	 * 
	 * @param null
	 * @return array
	 */
	function getParams()
	{
		return $this->params;
	}
	//}}}

	//{{{ getParamsFrom
	/**
	 * Returns source for the each element of the data, 
	 * passed to the method.
	 *
	 * @param null
	 * @retrun array
	 */
	function getParamsFrom()
	{
		return $this->params_from;
	}
	//}}}

	//{{{ replaceLimitParams
	/**
	 * Replacing parameters, marked as "from='limit'" with real values.
	 *
	 * @param null
	 * @return null
	 */
	function replaceLimitParams()
	{
		$controller = Controller::getInstance();
		foreach($this->params_from as $k=>$v)
			if($v == "limit")
				$this->params[$k] = array(	'from'=>$controller->getDisplayModeParams()->predicted_from,
					'limit'=>$controller->getDisplayModeParams()->predicted_limit);
	}
	//}}}
}
//}}}
