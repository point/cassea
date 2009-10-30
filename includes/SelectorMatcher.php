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
 * This file contains class gor checking whenever widget is
 * match with selector and some helper classes that helps to
 * parse selectors and speed-ups such lookups.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id:$
 * @package system
 * @since 
 */

//{{{ SelectorMatcher
/**
 * To address the widget in the document we propose mechanism, 
 * based on the principles of CSS selectors.
 *
 * For example you have XML file with such structure:
 *
 * <pre><code>
 * <WTable>
 *	<WTableRow>
 *		<WTableColumn colspan="2">
 *			<WText/>
 *			<WBlock>
 *				<WBlock>
 *					<WBlock/>
 *				</WBlock>
 *			</WBlock>
 *			<WBlock id="qqq">
 *				<WCheckbox checked="1" title="qwe]2" class="s_class" id="cb"/>
 *			</WBlock>
 *		</WTableColumn>
 *		<WTableColumn colspan="2"></WTableColumn>
 *		<WTableColumn></WTableColumn>
 *		<WTableColumn></WTableColumn>
 *	</WTableRow>
 * </WTable>
 *
 * </code></pre>
 *
 * To access <code>WCheckbox</code> you may use, for example, such 
 * selector:
 * <pre><code>
 * ->f("wtable wtablecolumn[colspan=2]:nth-child(odd) > WText ~ #qqq > .s_class[title='qwe]2']:checked")->text('text to checkbox');
 * </code></pre>
 *
 * It's not fastest and slightly unreadable way but it shows how 
 * selector mechanism works.
 *
 * Currently, system supports such combinators:
 * <ul>
 * <li><code>E F</code> - an F widget descendant of an E widget (as of CSS 1)</li>
 * <li><code>E > F</code> - an F widget child of an E widget (as of CSS 2)</li>
 * <li><code>E + F</code> - an F widget immediately preceded by an E widget (as of CSS 2)</li>
 * <li><code>E ~ F</code> - an F widget preceded by an E widget (as of CSS 3)</li>
 * </ul>
 *
 * List of supported selectors:
 * <ul>
 * <li><code>E#myid</code> - an E element (optional) with ID equal to "myid". The fastest method.</li>
 * <li><code>E%myid</code> - an E element (optional) with ID starting with "myid". Fast method.</li>
 * <li><code>E</code> - an element with classname E. Fast method.</li>
 * <li><code>*</code> - any element</li>
 * <li><code>E[foo]</code> - an E element with a "foo" attribute (checking by calling ->getFoo() method)</li>
 * <li><code>E[foo="bar"]</code> - an E element whose "foo" attribute value is exactly equal to "bar"</li>
 * <li><code>E[foo~="bar"]</code> - an E element whose "foo" attribute value is a list of 
 *		whitespace-separated values, one of which is exactly equal to "bar"</li>
 * <li><code>E[foo^="bar"]</code> - an E element whose "foo" attribute value begins exactly with the string "bar"</li>
 * <li><code>E[foo$="bar"]</code> - an E element whose "foo" attribute value ends exactly with the string "bar"</li>
 * <li><code>E[foo*="bar"]</code> - an E element whose "foo" attribute value contains the substring "bar"</li>
 * <li><code>E[foo|="en"]</code> - an E element whose "foo" attribute has a hyphen-separated list of values beginning (from the left) with "en"</li>
 * <li><code></code></li>
 * <li><code></code></li>
 * <li><code></code></li>
 * <li><code></code></li>
 * <li><code></code></li>
 * <li><code></code></li>
 * <li><code></code></li>
 * </ul>
 *
 * All string comparisons are case-insensitive.
 */
class SelectorMatcher
{
	const TRUE_CACHE = 1;
	const TRUE_NOCACHE = true;
	const FALSE_CACHE = 0;
	const FALSE_NOCACHE = false;

	static function matched(WComponent $widget, $selector,$index,$global)
	{
		$controller = Controller::getInstance();
        $parser = SelectorParserFactory::getSelectorParser2($selector,$index,$global);
		$return_cache = true;
		if($parser->getSelectorsCount() == 1)
		{
			return self::matchAttributes($widget,$parser->getParsedSelector(0));
		}
		elseif(($sel_c = $parser->getSelectorsCount()) - ($sp_c = $parser->getSplittersCount()) == 1)
		{
			if(!($ret = self::matchAttributes($widget,$parser->getParsedSelector($sel_c-1)))) return $ret;
			$w2 = $widget;
			for($i = $sp_c - 1; $i >= 0; $i--)
			{
				if($parser->getParsedSplitter($i) == ">")
				{
					if(!$controller->getAdjacencyList()->hasParent($w2->getId())) return self::FALSE_CACHE;
					if(($ret = self::matchAttributes(($w2 = $controller->getWidget($controller->getAdjacencyList()->getParentForId($w2->getId()))),
						$parser->getParsedSelector($i)))) { if($ret === self::TRUE_NOCACHE) $return_cache = false; continue;}
					else return $ret;
				}
				elseif($parser->getParsedSplitter($i) == " ")
				{
					while(1)
					{
						if(!$controller->getAdjacencyList()->hasParent($w2->getId())) return self::FALSE_CACHE;
						if(($ret = self::matchAttributes(($w2 = $controller->getWidget($controller->getAdjacencyList()->getParentForId($w2->getId()))),
							$parser->getParsedSelector($i)))) { if($ret === self::TRUE_NOCACHE || $w2->isInsideRoll()) $return_cache = false; continue 2;}
					}
					return $return_cache?self::FALSE_CACHE:self::FALSE_NOCACHE;
				}
				elseif($parser->getParsedSplitter($i) == "+")
				{
					$until = null;
					if($controller->getAdjacencyList()->hasParent($w2->getId()))
						$until = $controller->getAdjacencyList()->getParentForId($w2->getId());

					while(($p_id = $controller->getAdjacencyList()->getPrevUntil($w2->getId(),$until)) !== null)
					{
						if(($w2 = $controller->getWidget($p_id))&&
							$controller->getAdjacencyList()->getParentForId($w2->getId()) !== $until) continue;

						if(($ret = self::matchAttributes($w2,$parser->getParsedSelector($i))))
						{ if($ret === self::TRUE_NOCACHE) $return_cache = false; continue 2;}
						else break;
					}
					return $return_cache?self::FALSE_CACHE:self::FALSE_NOCACHE;
				}
				elseif($parser->getParsedSplitter($i) == "~")
				{
					$until = null;
					if($controller->getAdjacencyList()->hasParent($w2->getId()))
						$until = $controller->getAdjacencyList()->getParentForId($w2->getId());

					while(($p_id = $controller->getAdjacencyList()->getPrevUntil($w2->getId(),$until)) !== null)
					{
						if(($w2 = $controller->getWidget($p_id))&&
							$controller->getAdjacencyList()->getParentForId($w2->getId()) !== $until) continue;
						
						if(($ret = self::matchAttributes($w2,$parser->getParsedSelector($i)))) 
						{if($ret === self::TRUE_NOCACHE) $return_cache = false; continue 2;}
					}
					return $return_cache?self::FALSE_CACHE:self::FALSE_NOCACHE;
				}
			}
			return $return_cache?self::TRUE_CACHE:self::TRUE_NOCACHE;
			}
		return $return_cache?self::FALSE_CACHE:self::FALSE_NOCACHE;
	}
	static function matchAttributes(WComponent $widget, $parsed_selector)
	{
		if(empty($parsed_selector)) return false;
		$controller = Controller::getInstance();

		//id, quick
		if(isset($parsed_selector['id']) && $widget->getIdLower() != $parsed_selector['id']) return self::FALSE_CACHE;
		// *
		//if(isset($parsed_selector['tag']) && $parsed_selector['tag'] === '*') return self::TRUE_CACHE;
		//id starts with
		if(isset($parsed_selector['starts_with']) 
			&& substr($widget->getIdLower(),0,strlen($parsed_selector['starts_with'])) != $parsed_selector['starts_with']) return self::FALSE_CACHE;
		// tag
		if(isset($parsed_selector['tag']) && $parsed_selector['tag'] !== "*" && $widget->getClassLower() !== $parsed_selector['tag']) return self::FALSE_CACHE;

		// .class
		if(isset($parsed_selector['class']) &&
			!in_array(strtolower($parsed_selector['class']),
				array_map('strtolower',preg_split("/\s+/",$widget->getStyleClass())),true))
					return $widget->isInsideRoll()?self::FALSE_NOCACHE:self::FALSE_CACHE;

		// [attribute]
		if(isset($parsed_selector['attr']))
			if( !isset($parsed_selector['attr_value']))
			{
				if(!method_exists($widget,"get".$parsed_selector['attr']) ||
					$widget->{"get".$parsed_selector['attr']}() === null) 
						return $widget->isInsideRoll()?self::FALSE_NOCACHE:self::FALSE_CACHE;
			}
			elseif(isset($parsed_selector['attr_value']) && isset($parsed_selector['attr_quant']))
				// [attr=val]
				if($parsed_selector['attr_quant']  === "=")
				{	 
					if(!method_exists($widget,"get".$parsed_selector['attr']) ||
						$widget->{"get".$parsed_selector['attr']}() != strtolower($parsed_selector['attr_value'])) 
							return $widget->isInsideRoll()?self::FALSE_NOCACHE:self::FALSE_CACHE;
				}
				// [attr~=val]
				elseif($parsed_selector['attr_quant']  === "~=")
				{
					if(!method_exists($widget,"get".$parsed_selector['attr']) ||
						!in_array(strtolower($parsed_selector['attr_value']),
							array_map('strtolower',preg_split("/\s+/",$widget->{"get".$parsed_selector['attr']}())),true))
								return $widget->isInsideRoll()?self::FALSE_NOCACHE:self::FALSE_CACHE;
				}
				// [attr!=val]
				elseif($parsed_selector['attr_quant']  === "!=")
				{
					if(!method_exists($widget,"get".$parsed_selector['attr']) ||
						$widget->{"get".$parsed_selector['attr']}() == strtolower($parsed_selector['attr_value'])) 
							return $widget->isInsideRoll()?self::FALSE_NOCACHE:self::FALSE_CACHE;
				}
				// [attr^=val]
				elseif($parsed_selector['attr_quant']  === "^=" )
				{
					if(!method_exists($widget,"get".$parsed_selector['attr']) ||
						stripos($widget->{"get".$parsed_selector['attr']}(),$parsed_selector['attr_value']) !== 0) 
							return $widget->isInsideRoll()?self::FALSE_NOCACHE:self::FALSE_CACHE;
				}
				// [attr$=val]
				elseif($parsed_selector['attr_quant']  === "$=")
				{	
					if(!method_exists($widget,"get".$parsed_selector['attr']) ||
						stripos($_s = $widget->{"get".$parsed_selector['attr']}(),$parsed_selector['attr_value']) 
						!== (strlen($_s)-strlen($parsed_selector['attr_value']))) 
							return $widget->isInsideRoll()?self::FALSE_NOCACHE:self::FALSE_CACHE;
				}
				// [attr*=val]
				elseif($parsed_selector['attr_quant']  === "*=")
				{
			
					if(!method_exists($widget,"get".$parsed_selector['attr']) ||
						stripos($widget->{"get".$parsed_selector['attr']}(),$parsed_selector['attr_value']) === false) 
							return $widget->isInsideRoll()?self::FALSE_NOCACHE:self::FALSE_CACHE;
				}
				// [attr|=val]
				elseif($parsed_selector['attr_quant']  === "|=")
				{
					if(!method_exists($widget,"get".$parsed_selector['attr']) ||
						!in_array(strtolower($parsed_selector['attr_value']),
							array_map('strtolower',preg_split("/\s*-\s*/",$widget->{"get".$parsed_selector['attr']}())),true))
								return $widget->isInsideRoll()?self::FALSE_NOCACHE:self::FALSE_CACHE;
				}
				else return self::FALSE_CACHE;


		//pseudo
		if(isset($parsed_selector['pseudo']))
			// :contains(text) for ->getText() and ->getValue()
			if($parsed_selector['pseudo'] == "contains" && isset($parsed_selector['pseudo_value']))
			{
				if(method_exists($widget,"getText") && $widget->getText() != $parsed_selector['pseudo_value']) return $widget->isInsideRoll()?self::FALSE_NOCACHE:self::FALSE_CACHE;
				if($widget instanceof WControl && $widget->getValue() != $parsed_selector['pseudo_value']) return $widget->isInsideRoll()?self::FALSE_NOCACHE:FALSE_CACHE;
			}
			// :hidden
			elseif($parsed_selector['pseudo'] === "hidden")
			{
				if($widget->getVisible()) 
					return $widget->isInsideRoll()?self::FALSE_NOCACHE:self::FALSE_CACHE;
			}
			// :visible
			elseif($parsed_selector['pseudo'] === "visible")
			{
				if(!$widget->getVisible()) 
					return $widget->isInsideRoll()?self::FALSE_NOCACHE:self::FALSE_CACHE;
			}
			// :disable -> widget disable='1'
			elseif($parsed_selector['pseudo'] === "disable")
			{
				if($widget->getState()) return self::FALSE_CACHE;
			}
			// :input
			elseif($parsed_selector['pseudo'] === "input")
			{	
				if(!$widget instanceof WControl) return self::FALSE_CACHE;
			}
			// :text
			elseif($parsed_selector['pseudo'] === "text")
			{
				if(!$widget instanceof WEdit || !$widget->getType() != "text") return self::FALSE_CACHE;
			}
			// :password
			elseif($parsed_selector['pseudo'] === "password")
			{	
				if(!$widget instanceof WEdit || !$widget->getType() != "password") return self::FALSE_CACHE;
			}
			// :radio
			elseif($parsed_selector['pseudo'] === "radio")
			{
				if(!$widget instanceof WRadio) return self::FALSE_CACHE;
			}
			// :checkbox
			elseif($parsed_selector['pseudo'] === "checkbox")
			{
				if(!$widget instanceof WCheckbox) return self::FALSE_CACHE;
			}
			// :submit
			elseif($parsed_selector['pseudo'] === "image")
			{
				if(!$widget instanceof WImage) return self::FALSE_CACHE;
			}
			// :reset
			elseif($parsed_selector['pseudo'] === "reset")
			{
				if(!$widget instanceof WButton || !$widget->getType() != "reset") return self::FALSE_CACHE;
			}
			// :button
			elseif($parsed_selector['pseudo'] === "button")
			{	
				if (!$widget instanceof WButton || !$widget->getType() != "button") return self::FALSE_CACHE;
			}
			// :hidden
			elseif($parsed_selector['pseudo'] === "hidden")
			{
				if(!$widget instanceof WHidden) return self::FALSE_CACHE;
			}
			// :enabled
			elseif($parsed_selector['pseudo'] === "disabled")
			{
				if(!$widget instanceof WContol || !$widget->getDisabled()) return self::FALSE_CACHE;
			}
			// :checked
			elseif($parsed_selector['pseudo'] === "checked")
			{
				if(!$widget instanceof WCheckbox || !$widget->getChecked()) return self::FALSE_CACHE;
			}
			// :first-child
			elseif($parsed_selector['pseudo'] === "first-child")
			{
				if(($parent = $controller->getAdjacencyList()->getParentForId($widget->getId())) === null) return self::FALSE_CACHE;
				if(($list = $controller->getAdjacencyList()->getChildren($parent)) 
					&& $controller->getAdjacencyList()->checkIndex($list,$widget->getId()) !== 0) return self::FALSE_CACHE;
			}
			// :last-child
			elseif($parsed_selector['pseudo'] === "last-child")
			{
				if(($parent = $controller->getAdjacencyList()->getParentForId($widget->getId())) === null) return self::FALSE_CACHE;
				if(($list = $controller->getAdjacencyList()->getChildren($parent)) 
					&& $controller->getAdjacencyList()->checkIndex($list,$widget->getId()) !== (count($list)-1)) return self::FALSE_CACHE;
			}
			// :nth-child
			elseif($parsed_selector['pseudo'] === "nth-child")
			{
				if(($parent = $controller->getAdjacencyList()->getParentForId($widget->getId())) === null 
					|| !isset($parsed_selector['pseudo_value'])) return self::FALSE_CACHE;
				$ind = ($list = $controller->getAdjacencyList()->getChildren($parent))?
					$controller->getAdjacencyList()->checkIndex($list,$widget->getId()):-2;
				if(is_numeric($parsed_selector['pseudo_value']))
				   return $ind != (abs($parsed_selector['pseudo_value'])-1)?self::FALSE_CACHE:self::TRUE_CACHE;
				elseif($parsed_selector['pseudo_value'] == "odd")
				   return $ind%2?self::FALSE_CACHE:self::TRUE_CACHE;
				elseif($parsed_selector['pseudo_value'] == "even")
				   return !$ind%2?self::FALSE_CACHE:self::TRUE_CACHE;
				else return self::FALSE_CACHE;
			}
			// :index for WRoll
			elseif($parsed_selector['pseudo'] === "index" && $widget->isInsideRoll())
			{
				if(!isset($parsed_selector['pseudo_value'])) return self::FALSE_CACHE;
				$w2 = $widget;
				$parent = null;
                
                // inconvinient in case of nested rolls
                // to select odd rows, for example, "wroll > wtablerow:odd" syntax should be used

				if(($parent = $controller->getAdjacencyList()->getParentRollForId($w2->getId())) === null)
				{
					while($w2 && ($p = $controller->getAdjacencyList()->getParentForId($w2->getId())) !== null)
						if($controller->getWidget($p) instanceof WRoll) {$parent = $p;break;}
						else $w2 = $controller->getWidget($p);
					if($parent == null) return self::FALSE_CACHE;
					$controller->getAdjacencyList()->setParentRollForIdCache($widget->getId(),$parent); 
				}

				if(!is_array($parsed_selector['pseudo_value']))
                {
                    if(strpos($parsed_selector['pseudo_value'],":") !== false)
                        list($parsed_selector['pseudo_value'],$parsed_selector['scope']) = explode(":",$parsed_selector['pseudo_value']); 
                    if(!isset($parsed_selector['scope']))
                        $parsed_selector['scope'] = "global";

                    $current = $controller->getDisplayModeParams()->getCurrent($parent,$parsed_selector['scope']);
                    if(is_numeric($parsed_selector['pseudo_value']) && $parsed_selector['pseudo_value'] != $current) return self::FALSE_NOCACHE;
                    if($parsed_selector['pseudo_value'] === "odd" && $current%2 !== 1) return self::FALSE_NOCACHE;
                    if($parsed_selector['pseudo_value'] === "even" && $current%2 !== 0) return self::FALSE_NOCACHE;

                    if($parsed_selector['pseudo_value'] === "first" && !$controller->getDisplayModeParams()->isFirst($parent,$parsed_selector['scope'])) return self::FALSE_NOCACHE;
                    if($parsed_selector['pseudo_value'] === "last" && !$controller->getDisplayModeParams()->isLast($parent,$parsed_selector['scope'])) return self::FALSE_NOCACHE;
                }
                elseif(!count($parsed_selector['pseudo_value'])) return self::FALSE_CACHE;
                else
                {
                    $controller->getDisplayModeParams()->setMatchedIndex(-1);
                    $cur_scope = array_shift($parsed_selector['scope']);
					$matched = $current = $controller->getDisplayModeParams()->getCurrent($parent,$cur_scope);
                    foreach($parsed_selector['pseudo_value'] as $k => $v)
					{
						if(is_numeric($k) && $k != $current) continue;
						// else $k -- array
						elseif($current != RSIndexer::getLastIndex($k)) continue;
                        else
                        {
                            $_w3 = $_w2 = $controller->getWidget($parent);
                            $flag = true;
							$_parent = null;
                            foreach(RSIndexer::toArray($k) as $next_index)
							{
								if(($_parent = $controller->getAdjacencyList()->getParentRollForId($_w2->getId())) === null)
								{
									while($_w2 && ($_p = $controller->getAdjacencyList()->getParentForId($_w2->getId())) !== null)
										if($controller->getWidget($_p) instanceof WRoll) {$_parent = $_p;break;}
										else  $_w2 = $controller->getWidget($_p);

									$controller->getAdjacencyList()->setParentRollForIdCache($_w3->getId(),$_parent); 
								}
                                if($_parent && $controller->getDisplayModeParams()->getCurrent($_parent,$cur_scope) != $next_index)
                                {$flag = false;break;}
                            }
                            if($flag)
							{
                                $controller->getDisplayModeParams()->setMatchedIndex($k);
                                return self::TRUE_NOCACHE;
                            }
                        }
                    }
                    return self::FALSE_NOCACHE;
                }
			}//finish pseudo
			else return self::FALSE_CACHE;
		return self::TRUE_NOCACHE;
	}
}
//}}}

//{{{ SelectorParserFactory
class SelectorParserFactory
{
    private static $cache = array();
    static function getSelectorParser($selector,$index,$scope)
    {
        if(!isset(self::$cache[$selector]))
		{
            $o = new SelectorParser($selector,$index,$scope);
            self::$cache[$selector] = $o;
            return $o;
        }
        else
        {
            $o = self::$cache[$selector];
			if($index)
			{
				$o->setIndex($index);
				$o->setScope($scope);
				$o->processIndexScope();
			}
            return $o;
        }
    }

    static function &getSelectorParser2($selector,$index,$scope)
    {
		$m = md5($selector);
        if(!isset($GLOBALS['__selector_cache'][$m]))
		{
            $o = new SelectorParser($selector,$index,$scope);
            $GLOBALS['__selector_cache'][$m] = &$o;
            return $o;
        }
        else
        {
            $o = &$GLOBALS['__selector_cache'][$m];
            $o->setIndex($index);
            $o->setScope($scope);
            $o->processIndexScope();
            return $o;
        }
    }
}
//}}}

//{{{ SelectorParser
class SelectorParser
{
	const pattern_combined = 
'/\.([\w-]+)|\[(\w+)(?:([!*^$~|]?=)(["\']?)([^\4]*?)\4)?\]|:([\w-]+)(?:\(["\']?(.*?)?["\']?\)|$)/';

	const pattern_id = "/#([\w-]+)/";
	const pattern_quick_id = "/^#([\w-]+)$/";

	const pattern_tag = "/^(\w+|\*)/";//tag
	const pattern_quick_tag = "/^(\w+|\*)$/";//tag
	
	const pattern_splitter = '/\s*([+>~\s])\s*(?=[a-zA-Z#.*:\[])/';


	const pattern_starts_with = "/^%([\w-]+)/";
	const pattern_quick_starts_with = "/^%([\w-]+)$/";
	
	private $splitters = array();
    private $splitters_count = 0;
    private $selectors = array();
    private $selectors_count = 0;
	private $iter = 0;
	private $index = null,
			$scope = null
			;

	function __construct($selectors = null,$index,$scope)
	{
		$this->index = $index;
		$this->scope = $scope;
		if(isset($selectors))
			$this->parse($selectors);
	}
	function parse($selector)
	{
		$this->splitSelectors(trim($selector));
        $this->processIndexScope();

	}
    function processIndexScope()
    {
		if(($c = $this->selectors_count) > 0 &&
			isset($this->index) && isset($this->scope))
        {
			$this->selectors[$c-1]['pseudo'] = 'index';
			$this->selectors[$c-1]['pseudo_value'] = $this->index;
			$this->selectors[$c-1]['scope'] = $this->scope;
		}
    }
    function setIndex($index)
    {
        $this->index = $index;
    }
    function setScope($scope)
    {
        $this->scope = $scope;
    }
	private function splitSelectors($selector)
	{
		if(preg_match(self::pattern_quick_id,$selector,$m))
		{$this->selectors[0]['id'] = strtolower($m[1]); $this->selectors_count = 1;return;}
		if(preg_match(self::pattern_quick_starts_with,$selector,$m))
		{$this->selectors[0]['starts_with'] = strtolower($m[1]); $this->selectors_count = 1;return;}
		if(preg_match(self::pattern_quick_tag,$selector,$m))
		{$this->selectors['0']['tag'] = strtolower($m[1]) ; $this->selectors_count = 1; return;}

		$matches2 = preg_split(SelectorParser::pattern_splitter,$selector,0,PREG_SPLIT_DELIM_CAPTURE);
		for($j = 0, $i = 0, $c = count($matches2);$j < $c; $j+=2)
		{
			if(empty($matches2[$j])) continue;

			$selector = $matches2[$j];
			$splitter = isset($matches2[$j+1])?$matches2[$j+1]:null;

			$flag = 0;

			if(!is_null($splitter))
				$this->splitters[] = (trim($splitter) === "")?" ":trim($splitter);

			if(preg_match(self::pattern_quick_id,$selector,$m) && !empty($m[1]))
				$this->selectors[$i]['id'] = strtolower($m[1]) and $flag = 1;
			if(preg_match(self::pattern_quick_starts_with,$selector,$m) && !empty($m[1]))
				$this->selectors[$i]['starts_with'] = strtolower($m[1]) and $flag = 1;
			if(preg_match(self::pattern_quick_tag,$selector,$m) && !empty($m[1]))
				$this->selectors[$i]['tag'] = strtolower($m[1]) and $flag = 1;

			if($flag) {$i++; continue;}

			if(preg_match(self::pattern_id,$selector,$m) && !empty($m[1]))
				$this->selectors[$i]['id'] = strtolower($m[1]) ;

			if(preg_match(self::pattern_starts_with,$selector,$m) && !empty($m[1]))
				$this->selectors[$i]['starts_with'] = strtolower($m[1]) ;

			if(preg_match(self::pattern_tag,$selector,$m) && !empty($m[1]))
				$this->selectors[$i]['tag'] = strtolower($m[1]) ;

			while(preg_match(self::pattern_combined,$selector,$m))
			{
				$selector = str_replace($m[0],'',$selector);
				//unsetting captured \4 ie ' or " 
				unset($m[4]);
				$this->mylist($this->selectors[$i],array_values(array_slice($m,1)));
				unset($m);
			}
			unset($m);
			$i++;
		}
		$this->selectors = array_values($this->selectors);
        $this->selectors_count = count($this->selectors);
        $this->splitters_count = count($this->splitters);
	}
	private function mylist(&$array1,$array2)
	{
		$attrs = array('class', 'attr','attr_quant','attr_value','pseudo','pseudo_value');
		foreach(array_filter($array2,create_function('$var','return is_numeric($var) || !empty($var);')) as $k => $v)
			$array1[$attrs[$k]] = strtolower($v);
	}
	public function getSelectors()
	{
		return $this->selectors;
	}
	public function getSplitters()
	{
		return $this->splitters;
	}
	function getSelectorsCount()
	{
		return $this->selectors_count;
	}
	function getSplittersCount()
	{
		return $this->splitters_count;
	}
	function getParsedSelector($ind = 0)
	{
		if($ind >=$this->selectors_count || $ind < 0) return array();
		return $this->selectors[$ind];
	}
	function getParsedSplitter($ind = 0)
	{
		if($ind > $this->splitters_count || $ind < 0) return array();
		return $this->splitters[$ind];
	}
}
//}}}
