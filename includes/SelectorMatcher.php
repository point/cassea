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
 * This file contains class for checking whenever widget is
 * match with selector rule and some helper classes that helps to
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
 * selector rule:
 * <pre><code>
 * ->f("wtable wtablecolumn[colspan=2]:nth-child(odd) > WText ~ #qqq > .s_class[title='qwe]2']:checked")->text('text to checkbox');
 * </code></pre>
 *
 * It's not the fastest and slightly unreadable way but it shows how 
 * selectors mechanism works.
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
 * <li><code>E#myid</code> - an E widget (optional) with ID equal to "myid". The fastest method.</li>
 * <li><code>E%myid</code> - an E widget (optional) with ID starting with "myid". Fast method.</li>
 * <li><code>E</code> - an widget with classname E. Fast method.</li>
 * <li><code>*</code> - any widget</li>
 * <li><code>E.warning</code> - an E widget whose class is "warning" ie in a list of 
 *		whitespace-separated values, one of which is exactly equal to "warning"</li>
 * <li><code>E1, E2, EN</code> - matches the combined results of all the specified selectors.</li>
 * <li><code>E[foo]</code> - an E widget with a "foo" attribute (checking by calling ->getFoo() method)</li>
 * <li><code>E[foo="bar"]</code> - an E widget whose "foo" attribute value is exactly equal to "bar"</li>
 * <li><code>E[foo!="bar"]</code> - an E widget whose "foo" attribute value not equal to "bar"</li>
 * <li><code>E[foo~="bar"]</code> - an E widget whose "foo" attribute value is a list of 
 *		whitespace-separated values, one of which is exactly equal to "bar"</li>
 * <li><code>E[foo^="bar"]</code> - an E widget whose "foo" attribute value begins exactly with the string "bar"</li>
 * <li><code>E[foo$="bar"]</code> - an E widget whose "foo" attribute value ends exactly with the string "bar"</li>
 * <li><code>E[foo*="bar"]</code> - an E widget whose "foo" attribute value contains the substring "bar"</li>
 * <li><code>E[foo|="en"]</code> - an E widget whose "foo" attribute has a hyphen-separated list of values beginning 
 *		(from the left) with "en"</li>
 * <li><code>E:nth-child(n)</code> - an E widget, the n-th child of its parent. As opposed to CSS 3 rules, 
 * current implementation supports only "odd", "even" or numeric values for n. </li>
 * <li><code>E:first-child</code> - an E widget, first child of its parent</li>
 * <li><code>E:last-child</code> - an E widget, last child of its parent</li>
 * <li><code>E:index([first|last|odd|even|numeric]:[global|local])</code> - an E widget, which is in the iterable collection
 * and on the given position considering passed scope</li>
 * <li><code>E:enabled, E:disabled</code> - a user interface widget E which is enabled or disabled</li>
 * <li><code>E:checked</code> - a user interface widget E which is checked (for instance a radio-button or checkbox)</li>
 * <li><code>E:contains(bar)</code> - an E widget which has text or value property which is exactly equal to "bar"</li>
 * <li><code>E:hidden</code> - an widget E which is not visible (ie has visible='0')</li>
 * <li><code>E:disable</code> - an widget E which is not enabled (ie has enabled='0')</li>
 * <li><code>E:input</code> - an widget E which has WControl as a parent.</li>
 * <li><code>E:text</code> - an input widget of type text.</li>
 * <li><code>E:password</code> - an input widget of type password.</li>
 * <li><code>E:radio</code> - an input widget of type radio.</li>
 * <li><code>E:checkbox</code> - an input widget of type checkbox.</li>
 * <li><code>E:submit</code> - an input widget of type submit.</li>
 * <li><code>E:image</code> - an input widget of type image.</li>
 * <li><code>E:reset</code> - an input widget of type reset.</li>
 * <li><code>E:button</code> - an input widget of type button.</li>
 *
 * Some notes:
 *
 * All string comparisons are case-insensitive and values are trimmed.
 *
 * Selectors with multiple parameters, such as 
 * <code> [attr1][atttr2] </code> or
 * <code> tag:input:checked </code> 
 * are not currently supported. But user may use complex single-parameter
 * selectors: <code> tag#id[attr1=attr_value]:nth-child(odd) </code>
 *
 * Attributes are checking by calling <code>$widget->get{$attr_name}()</code>
 * method if it exists.
 */
class SelectorMatcher
{
	const TRUE_CACHE = 1;
	const TRUE_NOCACHE = true;
	const FALSE_CACHE = 0;
	const FALSE_NOCACHE = false;

	//{{{ matched
	/**
	 * Checks if given widget is matching with the specified parameters.
	 *
	 * This function use some trick with return values. To mark that results
	 * might be cached, method will return numeric values, that could be 
	 * casted to boolean true/false transparently. So it's still possible
	 * to use <code>if(SelectorMatcher::matched(...) )</code> code, but if
	 * calee side need some cache-specific tests, use strong types comparison:
	 * <code>if(SelectorMatcher::matched(...) === SelectorMatcher::TRUE_CACHE)</code>
	 * <code>SelectorMatcher::TRUE_CACHE</code> or <code>SelectorMatcher::FALSE_CACHE</code>
	 * return value means that this response might be cached and there is no need to
	 * make this request once again with the same parameters.
	 *
	 * @param WComponent widget to be checked 
	 * @param string selector string 
	 * @param mixed index of the iterable collection iterator (ie second argument, passed to the 
	 * method <code>f()</code>
	 * @param string scope of indexes of the current selector
	 * @return mixed values, casting to bool. 
	 */
	static function matched(WComponent $widget, $selector,$index,$scope)
	{
		$controller = Controller::getInstance();
        $parser = SelectorParserFactory::getSelectorParser2($selector,$index,$scope);
		$return_cache = true;
		if($parser->getSelectorsCount() == 1)
		{
			return self::matchAttributes($widget,$parser->getParsedSelector(0));
		}
		//starting from the last combinator and selector
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
	//}}}


	//{{{ matchAttributes
	/**
	 * Helper function which detects if given widget is matching with 
	 * the attributes, described in the splitted part of the selector.
	 *
	 * For example for selector 
	 * <pre><code>
	 * ->f("#text")->....
	 * </code></pre>
	 *
	 * each widget will be checked if it's ID is exactly equals to "text".
	 *
	 * Such as {@link matched} method it returns _CACHE , _NOCACHE
	 * values, which are easily converting to bool.
	 *
	 * @param WComponent widget to be checked by the attributes list
	 * @param array of parsed selector's parameters
	 * @return mixed values, casting to bool
	 */
	static protected function matchAttributes(WComponent $widget, $parsed_selector)
	{
		if(empty($parsed_selector)) return false;
		$controller = Controller::getInstance();

		//id, quick
		if(isset($parsed_selector['id']) && $widget->getIdLower() != $parsed_selector['id']) return self::FALSE_CACHE;
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
						trim($widget->{"get".$parsed_selector['attr']}()) != strtolower($parsed_selector['attr_value'])) 
							return $widget->isInsideRoll()?self::FALSE_NOCACHE:self::FALSE_CACHE;
				}
				// [attr~=val]
				elseif($parsed_selector['attr_quant']  === "~=")
				{
					if(!method_exists($widget,"get".$parsed_selector['attr']) ||
						!in_array(strtolower($parsed_selector['attr_value']),
							array_map('strtolower',preg_split("/\s+/",trim($widget->{"get".$parsed_selector['attr']}()))),true))
								return $widget->isInsideRoll()?self::FALSE_NOCACHE:self::FALSE_CACHE;
				}
				// [attr!=val]
				elseif($parsed_selector['attr_quant']  === "!=")
				{
					if(!method_exists($widget,"get".$parsed_selector['attr']) ||
						trim($widget->{"get".$parsed_selector['attr']}()) == strtolower($parsed_selector['attr_value'])) 
							return $widget->isInsideRoll()?self::FALSE_NOCACHE:self::FALSE_CACHE;
				}
				// [attr^=val]
				elseif($parsed_selector['attr_quant']  === "^=" )
				{
					if(!method_exists($widget,"get".$parsed_selector['attr']) ||
						stripos(trim($widget->{"get".$parsed_selector['attr']}()),$parsed_selector['attr_value']) !== 0) 
							return $widget->isInsideRoll()?self::FALSE_NOCACHE:self::FALSE_CACHE;
				}
				// [attr$=val]
				elseif($parsed_selector['attr_quant']  === "$=")
				{	
					if(!method_exists($widget,"get".$parsed_selector['attr']) ||
						stripos($_s = trim($widget->{"get".$parsed_selector['attr']}()),$parsed_selector['attr_value']) 
						!== (strlen($_s)-strlen($parsed_selector['attr_value']))) 
							return $widget->isInsideRoll()?self::FALSE_NOCACHE:self::FALSE_CACHE;
				}
				// [attr*=val]
				elseif($parsed_selector['attr_quant']  === "*=")
				{
			
					if(!method_exists($widget,"get".$parsed_selector['attr']) ||
						stripos(trim($widget->{"get".$parsed_selector['attr']}()),$parsed_selector['attr_value']) === false) 
							return $widget->isInsideRoll()?self::FALSE_NOCACHE:self::FALSE_CACHE;
				}
				// [attr|=val]
				elseif($parsed_selector['attr_quant']  === "|=")
				{
					if(!method_exists($widget,"get".$parsed_selector['attr']) ||
						!in_array(strtolower($parsed_selector['attr_value']),
							array_map('strtolower',preg_split("/\s*-\s*/",trim($widget->{"get".$parsed_selector['attr']}()))),true))
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
			elseif($parsed_selector['pseudo'] === "submit")
			{
				if(!$widget instanceof WButton || $widget->getType() != "submit") return self::FALSE_CACHE;
			}
			// :image
			elseif($parsed_selector['pseudo'] === "image")
			{
				if(!$widget instanceof WButton || $widget->getType() != "image") return self::FALSE_CACHE;
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
			// :disabled
			elseif($parsed_selector['pseudo'] === "disabled")
			{
				if(!$widget instanceof WContol || !$widget->getDisabled()) return self::FALSE_CACHE;
			}
			// :enabled
			elseif($parsed_selector['pseudo'] === "enabled")
			{
				if(!$widget instanceof WContol || $widget->getDisabled()) return self::FALSE_CACHE;
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
	//}}}
}
//}}}

//{{{ SelectorParserFactory
/**
 * Factory class which holds parsed selectors in order to reduce
 * number of <code> new SelectorParser() </code> calls and so
 * to reduce number of preg_* functions calls.
 */
class SelectorParserFactory
{
	/**
	 * Cache of parsed selectors
	 * @var array
	 */
    private static $cache = array();

	//{{{ getSelectorParser2
	/**
	 * Returns cached SelectorParser instance, with update index and scope values.
	 * If it not exists in the cache, it will be created and 
	 * placed to the cache, located at global scope ($GLOBALS)
	 *
	 * @param string selector to be parsed
	 * @param mixed index, passed as a second argument to the function f()
	 * @param string scope of the indexes in current selector
	 * @return SelectorParser instance
	 */
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
	//}}}
}
//}}}

//{{{ SelectorParser
/**
 * Based on MooTools framework
 * http://mootools.net/
 *
 * It splits given selector rule, extracting splitters (such as "+",">","~" etc)
 * and and selectors.
 * Each selector may consists of zero or one elements:
 * <ol>
 * <li>Class name (ie wtable, wblock etc)</li>
 * <li>Widget id</li>
 * <li>Widget "starts_with id" rule</li>
 * <li>Attribute (ie title, visible etc)</li>
 * <li>Attribute quntificator (ie "=", "^=" etc)</li>
 * <li>Attribute value</li>
 * <li>Pseudo attribute (ie ":checkbox", ":hidden" etc)</li>
 * <li>Pseudo value</li>
 * </ol>
 *
 * If selector rule consist of one selector, fast checks of the tag, id or "starts_with id"
 * will be performed. If nothing else was specified, no other heavy regexps will be 
 * executed.
 */
class SelectorParser
{
	/**
	 * Regexp to match and capture described parts of the single selector
	 */
	const pattern_combined = 
'/\.([\w-]+)|\[(\w+)(?:([!*^$~|]?=)(["\']?)([^\4]*?)\4)?\]|:([\w-]+)(?:\(["\']?(.*?)?["\']?\)|$)/';

	/**
	 * Pattern used to capture id from the single selector
	 */
	const pattern_id = "/#([\w-]+)/";
	/**
	 * Pattern to quick match and capture of id.
	 * If selector is matched this regexp, no other
	 * checks will be used.
	 */
	const pattern_quick_id = "/^#([\w-]+)$/";

	/**
	 * Pattern used to check and capture tag name of a selector.
	 */
	const pattern_tag = "/^(\w+|\*)/";//tag
	/**
	 * Pattern to quick match and capture tag name.
	 * If selector is matched this regexp, no other 
	 * checks will be used.
	 */
	const pattern_quick_tag = "/^(\w+|\*)$/";//tag
	
	/**
	 * Pattern, used to split selector rule on the single selectors and
	 * capture combinator, used between two selectors.
	 */
	const pattern_splitter = '/\s*([+>~\s])\s*(?=[a-zA-Z#.*:\[])/';


	/**
	 * Pattern used to check and capture starts_with id value
	 */
	const pattern_starts_with = "/^%([\w-]+)/";
	/**
	 * Pattern used to quick match and capture starts_with id.
	 * If selector is matched this regexp, no other checks will be 
	 * performed.
	 */
	const pattern_quick_starts_with = "/^%([\w-]+)$/";
	
	/**
	 * Array of parsed splitters (combinators)
	 * @var array
	 */
	private $splitters = array();
	/**
	 * Cached value of splitters count
	 * @var int
	 */
    private $splitters_count = 0;
	/**
	 * Array of parsed selectors
	 * @var array
	 */
    private $selectors = array();
	/**
	 * Cached value of selectors count
	 */
    private $selectors_count = 0;
	/**
	 * Currently used index for iterable collections.
	 * @var mixed
	 */
	private $index = null,
	/**
	 * Currently used scope for iterable collections.
	 */
	$scope = null
			;

	//{{{ __construct
	function __construct($selectors = null,$index,$scope)
	{
		$this->index = $index;
		$this->scope = $scope;
		if(isset($selectors))
			$this->parse($selectors);
	}
	//}}}

	//{{{ parse
	/**
	 * Parsing given selector rule: splitting into combinators and selectors, 
	 * and parsing specified index and scope.
	 *
	 * @param string selector rule
	 * @return null
	 */
	function parse($selector)
	{
		$this->splitSelectors(trim($selector));
        $this->processIndexScope();

	}
	//}}}

	//{{{ processIndexScope
	/**
	 * Updates index and scope parameters for current
	 * already parsed selector rule. 
	 * 
	 * @param null
	 * @return null
	 */
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
	//}}}

	//{{{ setIndex
	/**
	 * Sets index parameter for current parsed selector rule.
	 * 
	 * @param mixed index
	 * @return null
	 */
    function setIndex($index)
    {
        $this->index = $index;
	}
	//}}}

	//{{{ setScope
	/**
	 * Sets scope parameter for current parsed selector rule.
	 *
	 * @param string scope
	 * @return null
	 */
    function setScope($scope)
    {
        $this->scope = $scope;
	}
	//}}}

	//{{{ splitSelectors
	/**
	 * Splits given selector rule into separate selectors and combinators.
	 *
	 * First of all it tries to quick match on pattern_quick_id, pattern_quick_starts_with,
	 * and pattern_quick_tag regexps. If they've been completed successfully no other checks 
	 * will be performed.
	 *
	 * Otherwise rule will be split on pattern_splitter and each part will be fully checked.
	 *
	 * @param string selector rule
	 * @return null
	 */
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
	//}}}

	//{{{ mylist
	private function mylist(&$array1,$array2)
	{
		$attrs = array('class', 'attr','attr_quant','attr_value','pseudo','pseudo_value');
		foreach(array_filter($array2,create_function('$var','return is_numeric($var) || !empty($var);')) as $k => $v)
			$array1[$attrs[$k]] = strtolower($v);
	}
	//}}}

	//{{{ getSelectors
	/**
	 * Returns currently parsed selectors
	 *
	 * @param null
	 * @return array of arrays of selector parameters
	 */
	function getSelectors()
	{
		return $this->selectors;
	}
	//}}}

	//{{{ getSplitters
	/**
	 * Returns currently parsed splitters
	 *
	 * @param null
	 * @return array
	 */
	function getSplitters()
	{
		return $this->splitters;
	}
	//}}}

	//{{{ getSelectorsCount
	/**
	 * Returns cached selectors count
	 *
	 * @param null
	 * @return int
	 */
	function getSelectorsCount()
	{
		return $this->selectors_count;
	}
	//}}}

	//{{{ getSplittersCount
	/**
	 * Returns cached splitters count
	 */
	function getSplittersCount()
	{
		return $this->splitters_count;
	}
	//}}}

	//{{{ getParsedSelector
	/**
	 * Returns parsed selector by given index
	 *
	 * @param int index
	 * @param array of parameters of the selector if it was found
	 */
	function getParsedSelector($ind = 0)
	{
		if($ind >=$this->selectors_count || $ind < 0) return array();
		return $this->selectors[$ind];
	}
	//}}}

	//{{{ getParsedSplitter
	/**
	 * Returns parsed splitter by given index
	 *
	 * @param int index
	 * @return string splitter if it was found
	 */
	function getParsedSplitter($ind = 0)
	{
		if($ind > $this->splitters_count || $ind < 0) return array();
		return $this->splitters[$ind];
	}
	//}}}
}
//}}}
