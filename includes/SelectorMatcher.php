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

//
// $Id$
//
class SelectorMatcher
{
	static $cache = array();
	static function matched(WComponent $widget, $selector,$index,$global)
	{
		/*echo $id = $widget->getId();
        static $c = 0;
        if($id == "select2")
        {$c++;echo " $c ";}*/

		$controller = Controller::getInstance();
        $parser = SelectorParserFactory::getSelectorParser($selector,$index,$global);
		//$parser = new SelectorParser($selector,$index,$global);
		if($parser->getSelectorsCount() == 1)
		{
			if(self::matchAttributes($widget,$parser->getParsedSelector(0))) return true;
		}
		elseif(($sel_c = $parser->getSelectorsCount()) - ($sp_c = $parser->getSplittersCount()) == 1)
		{
			//if(!self::matchAttributes($widget,$parser->getParsedSelector($sel_c-1))) continue;
			if(!self::matchAttributes($widget,$parser->getParsedSelector($sel_c-1))) return false;
			$w2 = $widget;
			for($i = $sp_c - 1; $i >= 0; $i--)
			{
				if($parser->getParsedSplitter($i) == ">")
				{
					if(!$controller->getAdjacencyList()->hasParent($w2->getId())) return false;
					//if(!$controller->getAdjacencyList()->hasParent($w2->getId())) continue 2;
					if(self::matchAttributes(($w2 = $controller->getWidget($controller->getAdjacencyList()->getParentForId($w2->getId()))),
						$parser->getParsedSelector($i))) continue;
					else return false;
					//else continue 2;
				}
				elseif($parser->getParsedSplitter($i) == " ")
				{
					while(1)
					{
						if(!$controller->getAdjacencyList()->hasParent($w2->getId())) return false;
						//if(!$controller->getAdjacencyList()->hasParent($w2->getId())) continue 3;							
						if(self::matchAttributes(($w2 = $controller->getWidget($controller->getAdjacencyList()->getParentForId($w2->getId()))),
							$parser->getParsedSelector($i))) continue 2;
					}
					return false;
					//continue 2;
				}
				elseif($parser->getParsedSplitter($i) == "+")
				{
					$until = null;
					if($controller->getAdjacencyList()->hasParent($w2->getId()))
						$until = $controller->getAdjacencyList()->getParentForId($w2->getId());

					while(($p_id = $controller->getAdjacencyList()->getPrevUntil($w2->getId(),$until)) !== null)
					{
						if(self::matchAttributes(($w2 = $controller->getWidget($p_id)),$parser->getParsedSelector($i+1))
							&& !self::matchAttributes(($w2 = $controller->getWidget($p_id)),$parser->getParsedSelector($i)))continue;

						if(self::matchAttributes(($w2 = $controller->getWidget($p_id)),$parser->getParsedSelector($i))) continue 2;
						else return false;
						//else continue 3;
					}
					return false;
					//continue 2;
				}
				elseif($parser->getParsedSplitter($i) == "~")
				{
					$until = null;
					if($controller->getAdjacencyList()->hasParent($w2->getId()))
						$until = $controller->getAdjacencyList()->getParentForId($w2->getId());

					while(($p_id = $controller->getAdjacencyList()->getPrevUntil($w2->getId(),$until)) !== null)
					{
						if(self::matchAttributes(($w2 = $controller->getWidget($p_id)),$parser->getParsedSelector($i))) 
							continue 2;
					}
					return false;
					//continue 2;
				}
			}
			return true;
			}
		return false;
	}
	static function matchAttributes(WComponent $widget, $parsed_selector)
	{
        //static $__c = 0;
		if(empty($parsed_selector)) return false;
        /*echo ++$__c." ";
        print_pre($widget->getId());
        print_pre($parsed_selector);
        echo "<hr>";*/
		$controller = Controller::getInstance();

		//id, quick
		if(isset($parsed_selector['id']) && $widget->getIdLower() != $parsed_selector['id']) return false;
		// *
		if(isset($parsed_selector['tag']) && $parsed_selector['tag'] == '*') return true;
		// tag
		if(isset($parsed_selector['tag']) && $widget->getClassLower() != $parsed_selector['tag']) return false;
		// [attribute]
		if(isset($parsed_selector['attr']))
		   if( !isset($parsed_selector['attr_value'])
			&& (!method_exists($widget,"get".ucfirst($parsed_selector['attr'])) ||
				$widget->{"get".ucfirst($parsed_selector['attr'])}() === null)) return false;
			// [attr=val]
			elseif(isset($parsed_selector['attr_value']) && isset($parsed_selector['attr_quant']))
				if($parsed_selector['attr_quant']  === "=" && 
					(!method_exists($widget,"get".ucfirst($parsed_selector['attr'])) ||
                    $widget->{"get".ucfirst($parsed_selector['attr'])}() != strtolower($parsed_selector['attr_value']))) return false;
			// [attr!=val]
				elseif($parsed_selector['attr_quant']  === "!=" &&
					(!method_exists($widget,"get".ucfirst($parsed_selector['attr'])) ||
					$widget->{"get".ucfirst($parsed_selector['attr'])}() == strtolower($parsed_selector['attr_value']))) return false;
			// [attr^=val]
				elseif($parsed_selector['attr_quant']  === "^=" &&
					(!method_exists($widget,"get".ucfirst($parsed_selector['attr'])) ||
					stripos($widget->{"get".ucfirst($parsed_selector['attr'])}(),$parsed_selector['attr_value']) !== 0)) return false;
			// [attr$=val]
				elseif($parsed_selector['attr_quant']  === "$=" &&
					(!method_exists($widget,"get".ucfirst($parsed_selector['attr'])) ||
					stripos($_s = $widget->{"get".ucfirst($parsed_selector['attr'])}(),$parsed_selector['attr_value']) !== (strlen($_s)-strlen($parsed_selector['attr_value'])))) return false;
			// [attr*=val]
				elseif($parsed_selector['attr_quant']  === "*=" &&
					(!method_exists($widget,"get".ucfirst($parsed_selector['attr'])) ||
					stripos($widget->{"get".ucfirst($parsed_selector['attr'])}(),$parsed_selector['attr_value']) === false)) return false;



		if(isset($parsed_selector['pseudo']))
			// :contains(text) for ->getText() and ->getValue()
			if($parsed_selector['pseudo'] == "contains" && isset($parsed_selector['pseudo_value']))
			{
				if(method_exists($widget,"getText") && $widget->getText() != $parsed_selector['pseudo_value']) return false;
				if($widget instanceof WControl && $widget->getValue() != $parsed_selector['pseudo_value']) return false;
			}
			// :hidden
			elseif($parsed_selector['pseudo'] === "hidden" && $widget->getVisible()) return false;
			// :visible
			elseif($parsed_selector['pseudo'] === "visible" && !$widget->getVisible()) return false;
			// :disable -> widget disable='1'
			elseif($parsed_selector['pseudo'] === "disable" && $widget->getState()) return false;
			// :input
			elseif($parsed_selector['pseudo'] === "input" && !$widget instanceof WControl) return false;
			// :text
			elseif($parsed_selector['pseudo'] === "text" && (!$widget instanceof WEdit || !$widget->getType() != "text")) return false;
			// :password
			elseif($parsed_selector['pseudo'] === "password" && (!$widget instanceof WEdit || !$widget->getType() != "password")) return false;
			// :radio
			elseif($parsed_selector['pseudo'] === "radio" && !$widget instanceof WRadio) return false;
			// :checkbox
			elseif($parsed_selector['pseudo'] === "checkbox" && !$widget instanceof WCheckbox) return false;
			// :submit
			elseif($parsed_selector['pseudo'] === "submit" && (!$widget instanceof WButton || !$widget->getType() != "submit")) return false;
			// :image
			elseif($parsed_selector['pseudo'] === "image" && !$widget instanceof WImage) return false;
			// :reset
			elseif($parsed_selector['pseudo'] === "reser" && (!$widget instanceof WButton || !$widget->getType() != "reset")) return false;
			// :button
			elseif($parsed_selector['pseudo'] === "button" && (!$widget instanceof WButton || !$widget->getType() != "button")) return false;
			// :hidden
			elseif($parsed_selector['pseudo'] === "hidden" && !$widget instanceof WHidden) return false;
			// :enabled
			elseif($parsed_selector['pseudo'] === "enabled" && (!$widget instanceof WContol || $widget->getDisabled())) return false;
			// :disabled
			elseif($parsed_selector['pseudo'] === "disabled" && (!$widget instanceof WContol || !$widget->getDisabled())) return false;
			// :checked
			elseif($parsed_selector['pseudo'] === "checked" && (!$widget instanceof WCheckbox || !$widget->getChecked())) return false;
			// :first-child
			elseif($parsed_selector['pseudo'] === "first-child")
			{
				if(($parent = $controller->getAdjacencyList()->getParentForId($widget->getId())) === null) return false;
				if(($list = $controller->getAdjacencyList()->getChildren($parent)) 
					&& $controller->getAdjacencyList()->checkIndex($list,$widget->getId()) !== 0) return false;
			}
			// :last-child
			elseif($parsed_selector['pseudo'] === "last-child")
			{
				if(($parent = $controller->getAdjacencyList()->getParentForId($widget->getId())) === null) return false;
				if(($list = $controller->getAdjacencyList()->getChildren($parent)) 
					&& $controller->getAdjacencyList()->checkIndex($list,$widget->getId()) !== (count($list)-1)) return false;
			}
			// :nth-child
			elseif($parsed_selector['pseudo'] === "nth-child")
			{
				if(($parent = $controller->getAdjacencyList()->getParentForId($widget->getId())) === null 
				|| !isset($parsed_selector['pseudo_value']) || !is_numeric($parsed_selector['pseudo_value'])) return false;
				if(($list = $controller->getAdjacencyList()->getChildren($parent)) 
					&& $controller->getAdjacencyList()->checkIndex($list,$widget->getId()) !== ($parsed_selector['pseudo_value']-1)) return false;

			}
			// :index for WRoll
			elseif($parsed_selector['pseudo'] === "index")
            {
				if(!isset($parsed_selector['pseudo_value'])) return false;
				//return false;
				$w2 = $widget;
				$parent = null;
                
                // inconvinient in case of nested rolls
                // to select odd rows, for example, "wroll > wtablerow:odd" syntax should be used

                /*if($w2 instanceof WRoll) $parent = $w2->getId();
                else*/
                    while($w2 && ($p = $controller->getAdjacencyList()->getParentForId($w2->getId())) !== null)
                        if($controller->getWidget($p) instanceof WRoll) {$parent = $p;break;}
                        else $w2 = $controller->getWidget($p);
                if($parent == null) return false;
                if(!is_array($parsed_selector['pseudo_value']))
                {
                    if(strpos($parsed_selector['pseudo_value'],":") !== false)
                        list($parsed_selector['pseudo_value'],$parsed_selector['scope']) = explode(":",$parsed_selector['pseudo_value']); 
                    if(!isset($parsed_selector['scope']))
                        $parsed_selector['scope'] = "global";

                    $current = $controller->getDisplayModeParams()->getCurrent($parent,$parsed_selector['scope']);
                    if(is_numeric($parsed_selector['pseudo_value']) && $parsed_selector['pseudo_value'] != $current) return false;
                    if($parsed_selector['pseudo_value'] === "odd" && $current%2 !== 1) return false;
                    if($parsed_selector['pseudo_value'] === "even" && $current%2 !== 0) return false;

                    if($parsed_selector['pseudo_value'] === "first" && !$controller->getDisplayModeParams()->isFirst($parent,$parsed_selector['scope'])) return false;
                    if($parsed_selector['pseudo_value'] === "last" && !$controller->getDisplayModeParams()->isLast($parent,$parsed_selector['scope'])) return false;
                }
                elseif(!count($parsed_selector['pseudo_value'])) return false;
                else
                {
                    $controller->getDisplayModeParams()->setMatchedIndex(-1);
                    $cur_scope = array_shift($parsed_selector['scope']);
                    $matched = $current = $controller->getDisplayModeParams()->getCurrent($parent,$cur_scope);
                    foreach($parsed_selector['pseudo_value'] as $k => $v)
                    {
                        if($current != RSIndexer::getLastIndex($k)) continue;
                        else
                        {
                            $_w2 = $controller->getWidget($parent);
                            $flag = true;
                            foreach(RSIndexer::toArray($k) as $next_index)
                            {
                                while($_w2 && ($_p = $controller->getAdjacencyList()->getParentForId($_w2->getId())) !== null)
                                    if($controller->getWidget($_p) instanceof WRoll) {$_parent = $_p;break;}
                                    else  $_w2 = $controller->getWidget($_p);
                                if($_parent && $controller->getDisplayModeParams()->getCurrent($_parent,$cur_scope) != $next_index)
                                {$flag = false;break;}
                            }
                            if($flag)
                            {
                                $controller->getDisplayModeParams()->setMatchedIndex($k);
                                return true;
                            }
                        }
                    }
                    return false;
                    //if(!isset($parsed_selector['pseudo_value'][$current]) || $parsed_selector['pseudo_value'][$current] != $current) return false;
                    //$matched = $current;
                    //$controller->getDisplayModeParams()->setMatchedIndex($current);
                }
			}
		return true;
	}
}
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
            $o->setIndex($index);
            $o->setScope($scope);
            $o->processIndexScope();
            return $o;
        }
    }
}
class SelectorParser
{
	/*private static $pattern_combined = <<<EOF
/\.([\w-]+)|\[(\w+)(?:([!*^$~|]?=)["']?(.*?)["']?)?\]|:([\w-]+)(?:\(["']?(.*?)?["']?\)|$)/
	EOF;*/

	const pattern_combined =
'/\.([\w-]+)|\[(\w+)(?:([!*^$~|]?=)["\']?(.*?)["\']?)?\]|:([\w-]+)(?:\(["\']?(.*?)?["\']?\)|$)/';
	const pattern_id = "/#([\w-]+)/";
	const pattern_quick_id = "/^#([\w-]+)$/";

	//const pattern_tag = "/^(\w+|\*)/";
	const pattern_tag = "/^(\w+|\*)/";//tag
	const pattern_quick_tag = "/^(\w+|\*)$/";//tag
	//const pattern_splitter = "/\s*([+>~\s])\s*([a-zA-Z#.*:\[]*)/"	;
	//const pattern_splitter = '/\s*([a-zA-Z#.*:\[]*)\s*([+>~\s])/'	;
	const pattern_splitter = '/([^+>~\s]+)(\s*[+>~\s])?/'	;
	
/*		$patterns = array(
		"id"=> "/#([\w-]+)/",
		"tag"=> "/^(\w+|\*)/",
		"quick"=> "/^(\w+|\*)$/", //by tag name
		"splitter"=> "/\s*([+>~\s])\s*([a-zA-Z#.*:\[]*)/",
		"combined"=> $combined
	);*/
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
		if(preg_match(self::pattern_quick_tag,$selector,$m))
		{$this->selectors['0']['tag'] = strtolower($m[1]) ; $this->selectors_count = 1; return;}

		$i = 0;
		$ret = preg_match_all(SelectorParser::pattern_splitter,$selector,$matches,PREG_SET_ORDER);
		foreach($matches as $v)
		{
			$flag = 0;
			if(empty($v[1]) && empty($v[2])) continue;

			if(isset($v[2]))
				$this->splitters[] = (trim($v[2]) === "")?" ":trim($v[2]);

			if(preg_match(self::pattern_quick_tag,$v[1],$m) && !empty($m[1]))
				$this->selectors[$i]['tag'] = strtolower($m[1]) and $flag = 1;
			if(preg_match(self::pattern_quick_id,$v[1],$m) && !empty($m[1]))
				$this->selectors[$i]['id'] = strtolower($m[1]) and $flag = 1;

			if($flag) {$i++;continue;}

			if(preg_match(self::pattern_tag,$v[1],$m) && !empty($m[1]))
				$this->selectors[$i]['tag'] = strtolower($m[1]) ;

			if(preg_match(self::pattern_id,$v[1],$m) && !empty($m[1]))
				$this->selectors[$i]['id'] = strtolower($m[1]) ;

			while(preg_match(self::pattern_combined,$v[1],$m) && !empty($m[0]))
			{
				$v[1] = str_replace($m[0],'',$v[1]);
				$this->mylist($this->selectors[$i],array_values(array_slice($m,2)));
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
		$attrs = array('attr','attr_quant','attr_value','pseudo','pseudo_value');
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
/*echo "=============================+";
$s1 = new SelectorParser();
$selector = "a#aaaa[href$=q]:nth-child(odd)> div:last-child ~ p#qqq:last-child > .input";
$s1->parse($selector);
var_dump($s1->getSelectors());
var_dump($s1->getSplitters());*/

?>
