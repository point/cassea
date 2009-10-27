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
 * This file contains class for setting, aggregating data, taken from 
 * user's models and managing search for those data for particular widget
 * and some helper classes.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id:$
 * @package system
 * @since 
 */

$GLOBALS['__m_cache'] = array();
$GLOBALS['__rsindexer_cache'] = array();

//{{{ ResultSet
/**
 * There are 3 different ways to use ResultSet object in
 * the user's models:
 *
 * <ul>
 *	<li>Pointing to which widget pass specified data:
 *		<pre><code>
 *			$r = new ResultSet;
 *			$r->f("#widget_id")->text('text of the widget');
 *		</code></pre>
 *	</li>
 *	<li>If the widget is situated inside the WRoll and you want to
 *		pass the data at the N-th iteration of it, use such syntax:
 *		<pre><code>
 *			$r = new ResultSet;
 *			$r->f("#widget_id:index(1)")->text('text of the widget');
 *		</code></pre>
 *		Will set text to the widget at the second iteration (zero-based indexes)
 *	</li>
 *	<li>Alternative syntax to this example is:
 *		<pre><code>
 *			$r = new ResultSet;
 *			$r->f("#widget_id", 1)->text('text of the widget');
 *		</code></pre>
 *		Will set text to the widget at the second iteration too, but it's much
 *		faster. It's recommended to use this or next types of syntax to assign values
 *		in the cycle loops.
 *	</li>
 *	<li>In case of nested WRoll's:
 *		<pre><code>
 *			$r = new ResultSet;
 *			$r->f("#widget_id", array(1,2))->text('text of the widget');
 *		</code></pre>
 *		Will set text to the widget at the third iteration of most deep WRoll, and at the 
 *		second iteration of the previous WRoll. It's also much faster, than syntax, based on
 *		"index" pseudo-property:
 *		<pre><code>
 *		$r = new ResultSet;
 *		$r->f("#roll1:index(1) #widget_id:index(2)")->text('text of the widget); //TOO BAD!
 *		</code></pre>
 *	</li>
 * </ul>
 *
 * ResultSet objects supports fluent interfaces, and you can chain method calls.
 *
 * So, typical use case of the resultsets is:
 * In datasource object:
 * <pre><code>
 * function getData()
 * {
 *		return t(new ResultSet)->f("#element1")->text('qwe')->f("#href2")->href('http://google.com');
 * }
 * </pre></code>
 * 
 * Here function t() used to return reference of newly created object to emulate
 * PHP unsupported syntax <code>return new ResultSet()->f("#element1")->text('qwe').....</code>.
 *
 * Additionally scope parameter might be specified. It works in such way:
 * For example we have WRoll with pagination with 10 items per page and currently
 * we are located at the second page. If scope is global then in order to address widget at 
 * the first iteration we had to pass <code>->f("#widget_id",10)</code>, ie 
 * 0-9 - iterations on the first page, 10-19 - iterations of the second page and so on.
 * If the scope is local, then we may use <code>->f("#widget_id",0)</code> syntax.
 * Default is global scope and it useful for automatic computing of the iterations range 
 * to display.
 */
class ResultSet
{
	private 
		/**
		 * Holds selectors, passed without pointing indexes 
		 * as second parameter.
		 * ie ->f("#widget_id")->...
		 * @var array
		 */
		$fors = array(),
		/**
		 * Values for selectors, passed without pointing indexes
		 * @var array
		 */
		$for_values = array(),
		/**
		 * Default values for widget, pointed via ->f()->def() method without indexes
		 * @var array
		 */
        $default_values = array(),
		/**
		 * Default values for widget, pointed via ->f()->def() method with indexes
		 * @var array
		 */
        $default_values_array = array(),
		/**
		 * Type of each selector. Might be "array" (if indexes was used) 
		 * or "plain" (if not)
		 * @var array
		 */
        $types = array(),
		/**
		 * Holds data, sending with pointing indexes
		 * ie ->f("#widget_id",4)->
		 */
        $fors_array = array(),
		/**
		 * Values for selectors, passed with indexes
		 * @var array
		 */
        $for_values_array = array(),
		/**
		 * Current index passed to ->f() function. 
		 * Might be numeric or string. RSIndexer used to 
		 * find actual numeric index
		 * @var mixed
		 */
		$cur_index = -1,
		/**
		 * Array of selectors, marked as ->f1(). Such selectors once
		 * matched will be deleted from the list. Actual 
		 * values and other information keeps in
		 * $this->fors or $this->fors_array properties.
		 * @var array
		 */
		$f1s = array(),
		/**
		 * Global scope for current ResultSet object
		 * @var string
		 */
		$scope = "global"

	;
	//{{{ setScope
	/**
	 * Sets default scope for the current ResultSet object.
	 *
	 * @param string it might be "local" or "global" string
	 * @return null
	 */
	function setScope($scope)
	{
		if($scope != "local" && $scope != "global") return;
		$this->scope = $scope;
	}
	//}}}

	//{{{ f
	/**
	 * Sets current selector that address widget(s) to which
	 * all specified data will be assigned.
	 *
	 * List of supported selectors situated at {@link SelectorMatcher} class.
	 *
	 * @param string selector
	 * @param mixed optional index if desire widget located in the WRoll. 
	 * It could be numeric or array
	 * @param string scope for current selector. It could be "global" or 
	 * "local" strings
	 */
    function f($selector = "", $index = null, $scope = null)
    {
		if(!isset($scope))
			$scope = $this->scope;
        if(isset($index))
        {
            $this->cur_index = (($ind = RSIndexer::index($index)) === null)?0:$ind;
            $this->types[$selector] = "array";
            $this->fors_array[$selector]['index'][$this->cur_index] = 1;
            $this->fors_array[$selector]['scope'][$this->cur_index] = $scope;
        }
        else
        {
            $this->types[$selector] = "plain";
            $this->fors[$selector] = 1;
            $this->cur_index = -1;
        }
        $this->cur_selector = $selector;
        return $this;
    }
	//}}}

	//{{{ f1
	/**
	 * Same as {@link f} but once widget is matched against given selector,
	 * it will be removed and next time won't be matched.
	 *
	 * @param string selector
	 * @param mixed optional index if desire widget located in the WRoll. 
	 * It could be numeric or array
	 * @param string scope for current selector. It could be "global" or 
	 * "local" strings
	 * @return ResultSet current object to support fluent interfaces
	 * @see f
	 */
    function f1($selector = "", $index = null, $scope = null)
    {
		if(!isset($scope))
			$scope = $this->scope;
        if(strpos($selector,",") === false)
            $this->f1s[$selector] = 1;
        return $this->f($selector, $index, $scope);
	}
	//}}}

	//{{{ set
	/**
	 * Sets data to property of the widget that could be found by
	 * the current selector.
	 * This is explicit way to pass the data.
	 *
	 * @param string name of the property to set
	 * @param mixed data to pass to widget.
	 * @return ResultSet current object to support fluent interfaces
	 */
    function set($key,$value)
    {
		if(!isset($this->cur_selector)) return $this;
        if($this->types[$this->cur_selector] == "array")
            $this->for_values_array[$this->cur_selector][$this->cur_index][$key] = $value;
        else
            $this->for_values[$this->cur_selector][$key] = $value;
        return $this;
    }
	//}}}

	//{{{ def
	/**
	 * Sets data to the default property of the widget that could
	 * be found by the current selector
	 * @param mixed data to pass to widget.
	 * @return ResultSet current object to support fluent interfaces
	 */
    function def($value)
    {
        if(!isset($this->cur_selector)) return $this;
        if($this->types[$this->cur_selector] == "array")
            $this->default_values_array[$this->cur_selector][$this->cur_index] = $value;
        else
            $this->default_values[$this->cur_selector] = $value;
        return $this;
	}
	//}}}

	//{{{ findMatched
	/**
	 * Among the stored selectors search those, which are matched with the 
	 * current widget's state.
	 *
	 * For matched selectors {@link WidgetResultSet} will be filled with the 
	 * data, passed from the user's models.
	 *
	 * In order to reduce the lookups of result of matching given widget with
	 * the particular selector, intermediate caching in $GLBALS is used.
	 *
	 * @param WComponent widget to be analyzed
	 * @return WidgetResultSet with the data that should be passed to this widget
	 */
    function findMatched(WComponent $widget)
    {
        $wrs = new WidgetResultSet();
        foreach($this->fors as $selectors => $v)
            foreach(explode(",",$selectors) as $selector)
			{
				// hit the cache, ie SelectorMatcher::matched for this widget and selector 
				// was called
				if(array_key_exists(($md5 = md5($selector.$widget->getId())),$GLOBALS['__m_cache'])) 
				{
					if($GLOBALS['__m_cache'][$md5] === SelectorMatcher::FALSE_CACHE) continue;
					elseif($GLOBALS['__m_cache'][$md5] === SelectorMatcher::TRUE_CACHE)
					{
						@$wrs->merge($this->for_values[$selectors]);
						@$wrs->setDef($this->default_values[$selectors]);
						if(isset($this->f1s[$selectors]))
						{
							unset($this->fors[$selectors]);
							unset($this->for_values[$selectors]);
						}
					}
					//if hit the cache but we shouldn't use cached guideline
					//so make lookup once again
					elseif(SelectorMatcher::matched($widget,$selector,null,null))
					{
						@$wrs->merge($this->for_values[$selectors]);
						@$wrs->setDef($this->default_values[$selectors]);
						if(isset($this->f1s[$selectors]))
						{
							unset($this->fors[$selectors]);
							unset($this->for_values[$selectors]);
						}
					}
				}
				//caching the result
				elseif(($GLOBALS['__m_cache'][$md5] = SelectorMatcher::matched($widget,$selector,null,null)))
				{
					@$wrs->merge($this->for_values[$selectors]);
					@$wrs->setDef($this->default_values[$selectors]);
					if(isset($this->f1s[$selectors]))
					{
						unset($this->fors[$selectors]);
						unset($this->for_values[$selectors]);
					}
				}
			}
        
		//once again but now for selector with indexes
        foreach($this->fors_array as $selectors => $arr)
            foreach(explode(",", $selectors) as $selector)
			{
				$md5 = md5($selector.$widget->getId());
				if(array_key_exists($md5,$GLOBALS['__m_cache'])) 
				{
					if($GLOBALS['__m_cache'][$md5] === SelectorMatcher::FALSE_CACHE) continue;
					elseif($GLOBALS['__m_cache'][$md5] === SelectorMatcher::TRUE_CACHE)
					{
						@$wrs->merge($this->for_values_array[$selectors][$matched = Controller::getInstance()->getDisplayModeParams()->getMatchedIndex()]);
						@$wrs->setDef($this->default_values_array[$selectors][$matched]);
						if(isset($this->f1s[$selectors]))
						{
							unset($this->fors_array[$selectors]['index'][$matched]);
							unset($this->fors_array[$selectors]['scope'][$matched]);
							unset($this->for_values_array[$selectors][$matched]);
						}
					}
					elseif(SelectorMatcher::matched($widget,$selector,$arr['index'],$arr['scope']))
					{
						@$wrs->merge($this->for_values_array[$selectors][$matched = Controller::getInstance()->getDisplayModeParams()->getMatchedIndex()]);
						@$wrs->setDef($this->default_values_array[$selectors][$matched]);
						if(isset($this->f1s[$selectors]))
						{
							unset($this->fors_array[$selectors]['index'][$matched]);
							unset($this->fors_array[$selectors]['scope'][$matched]);
							unset($this->for_values_array[$selectors][$matched]);
						}
					}
				}
				elseif(($GLOBALS['__m_cache'][$md5] = SelectorMatcher::matched($widget,$selector,$arr['index'],$arr['scope'])))
				{
						@$wrs->merge($this->for_values_array[$selectors][$matched = Controller::getInstance()->getDisplayModeParams()->getMatchedIndex()]);
						@$wrs->setDef($this->default_values_array[$selectors][$matched]);
						if(isset($this->f1s[$selectors]))
						{
							unset($this->fors_array[$selectors]['index'][$matched]);
							unset($this->fors_array[$selectors]['scope'][$matched]);
							unset($this->for_values_array[$selectors][$matched]);
						}
				}
			}

        return $wrs;
    }
	//}}}

	//{{{ __call
	/**
	 * Shorthand alternative to {@link set} method.
	 *
	 * I.e calling <code>->f("#w")->text('qwe');</code>
	 * will be transformed to <code>->f("#w")->set('text','qwe');</code>
	 *
	 * @param string name of the property
	 * @param mixed data to be setted for this property
	 * @return ResultSet current object to support fluent interfaces
	 */
	function __call($name,$arguments)
	{
		if(!isset($arguments[0])) return $this;
		return $this->set($name,$arguments[0]);
	}
	//}}}
}
//}}}

//{{{ RSIndexer
/**
 * Helper class that hides all nuances of the implementation 
 * of index types in {@link ResultSet::f} and 
 * {@link ResultSet::f1} methods. 
 */
class RSIndexer
{
	//{{{ index
	/**
	 * Return string or numeric that might be used 
	 * as a index in array.
	 *
	 * @param mixed input value to process
	 * @param mixed string or numeric to be used as the index in arrays
	 */
    static function index($inp)
    {
        if(is_numeric($inp))
            return $inp;
        if(is_array($inp) && count($inp) == 1)
            return $inp[0];
        if(is_array($inp) && count($inp) > 1)
            return serialize($inp);
        return null;
    }
	//}}}


	//{{{ getLastIndex
	/**
	 * Returns the last index of inputted value.
	 * I.e if selector was 
	 * <code>->f("#w",array(2,3,4))</code>
	 * it will return "4".
	 * If it's a numeric, it will be returned itself.
	 * Otherwise last index of array will be returned.
	 *
	 * Intermediate cache is used via $GLOBALS array 
	 * to speed up this lookup.
	 *
	 * @param mixed value in which we need to find the last index
	 * @return numeric the last index
	 */
    static function getLastIndex($s_index)
    {
        if(is_numeric($s_index))
            return $s_index;
        if(!isset($s_index)) return null;

		if(isset($GLOBALS['__rsindexer_cache'][$s_index]))
			return $GLOBALS['__rsindexer_cache'][$s_index][0];

        $us_index = unserialize($s_index);
        if($us_index === false || !is_array($us_index) || empty($us_index))
            $us_index = array(null);

		$us_index = array_reverse($us_index);
		$GLOBALS['__rsindexer_cache'][$s_index] = $us_index;
        return $us_index[0];
    }
	//}}}

	//{{{ toArray
	/**
	 * Returns array, which is reverted representation of
	 * array, passed via <code>f()</code> method
	 * and starting with the second element.
	 *
	 * Primarily for internal use.
	 *
	 * @param value that should be converted to array
	 * @return array
	 */
    static function toArray($s_index)
    {
        if(!isset($s_index)|| is_numeric($s_index)) return array();

		if(isset($GLOBALS['__rsindexer_cache'][$s_index]))
            return array_slice($GLOBALS['__rsindexer_cache'][$s_index],1);

        $us_index = unserialize($s_index);
        if($us_index === false || !is_array($us_index) || empty($us_index))
			$us_index = array(null);
		$us_index = array_reverse($us_index);

		$GLOBALS['__rsindexer_cache'][$s_index] = $us_index;
        return array_slice($us_index,1);
    }
	//}}}

}
//}}}
