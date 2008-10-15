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

require("Config.php");
require("Storage.php");
require("functions.php");
require("Filter.php");
require("HTTPParamHolder.php");
require("Header.php");
require("Navigator.php");
require("Template.php");
require("EventDispatcher.php");
require("DataObject.php");
require("SelectorMatcher.php");
require("DataPools.php");
require("ResultSet.php");
require("WidgetsAdjacencyList.php");
require("DB.php");
require("Session.php");
require("POSTChecker.php");

class ControllerException extends Exception
{}

class WidgetLoader
{
	static private $cache = array();
	static function load($name = null)
	{
		if(!isset($name))
			return null;
		if(isset(self::$cache[$name]))
			return self::$cache[$name];
		if(file_exists(Config::get("ROOT_DIR")."/includes/widgets/".$name.".php"))
		{
			require Config::get("ROOT_DIR")."/includes/widgets/".$name.".php";
			return self::$cache[$name] = $name;
		}
		else return false;
	}
}
class Controller
{
	public	$p1 = null,
			$p2 = array(),
			$post = null,
			$get = null
		;

	protected 
			$header = null,
			$page = "index",
			$page_function = null,
			$datasets = array(),
			$datahandlers = array(),
			$navigator = null,
			$controller_name = null,
			$final_html = "",
			$dispatcher = null,
			$scripts = array(),
			$css = array(),
			$valuecheckers = array(),
			$widgets = array(),
			$system_widgets = array(),
			$display_mode_params = null,
			$adjacency_list = null,
			$form_signatures = array(),
			$checker_rules = array(),
            $pagehandler = null
			;


	function __construct()
	{
		if(preg_match("/^\/controllers\/(\w+)\.php$/",$_SERVER['PHP_SELF'],$m))
			$this->controller_name = $m[1];
		else throw new ControllerException('controller name not defined');

		$this->get = new HTTPParamHolder($_GET,0);
		$this->post = new HTTPParamHolder($_POST);
		$this->header = Header::get();
		$this->dispatcher = new EventDispatcher();
		$this->display_mode_params = new DisplayModeParams();
		$this->adjacency_list = new WidgetAdjacencyList();

        DB::init(null, 'root', '','intvideo'); 
		Session::getInstance();

		POSTErrors::restoreErrorList();

		$this->navigator = new Navigator($this->controller_name);
	}
	static function getInstance()
	{
		static $instance = null;
		
		if(!$instance)
			$instance = new Controller();
		return $instance;
	}
	function setPageFunc($func)
	{
		if(is_callable($func))
			$this->page_function = $func;
	}
	function setPageClassMethod($class_name,$func)
	{
		if(is_callable($class_name,$func))
			$this->page_function = $class_name."::".$func;
	}
	function init()
	{
		$ret = null;
		$this->parseP1P2();	
		$this->handlePOST();
		if(is_string($this->page_function))
			$ret = str_replace('.xml','',call_user_func($this->page_function,$this->p1,$this->p2));
		if(!isset($ret))
			if(!empty($this->p1))
				$this->page = $this->p1;
			else
				$this->page = 'index';
		else
			$this->page = $ret;


		if(!file_exists(Config::get('ROOT_DIR').Config::get("XMLPAGES_PATH")."/".$this->controller_name."/".$this->page.".xml"))
			throw new ControllerException('page file not found');

		$this->navigator->addStep($this->page);

		$this->addCSS("ns_reset.css");
		$this->addScript("jquery.js");
		$this->addScript("jquery.cookie.js");
		$this->addScript("jquery.bgiframe.js");
		$this->addScript("jquery.tooltip.js");
		$this->addCSS("jquery.tooltip.css");
		$this->addScript("jquery.treeview.js");
		//$this->addScript("IE8.js","IE");
		$this->addCSS("default.css");
		/*$this->addScript("php_serialize.js");
		$this->addScript("swfobject.js");
		$this->addScript("formatDate.js");
		$this->addScript("w.js");*/

		$dom = new DomDocument;
		$dom->load(Config::get('ROOT_DIR')."/pages/".$this->controller_name."/".$this->page.".xml");

		$this->parsePage($this->processPage($dom));

	}
	private final function parseP1P2()
	{
		$this->get->bindFilter('__p1',Filter::STRING_QUOTE_ENCODE);
		$this->get->bindFilter('__p2',Filter::STRING_QUOTE_ENCODE);
		if(isset($this->get->__p1))
			$this->p1 = $this->get->__p1;
			
		if(isset($this->get->__p2))
		{
			$this->p2 = $this->get->__p2;
			if ( strpos($this->p2,'/') === 0 ) 
				$this->p2 = substr($this->p2, 1);

			if ( strlen($this->p2) -1 ===  strrpos($this->p2,'/'))		
				$this->p2 = substr($this->p2, 0, -1);

			$this->p2 = explode("/",$this->p2);
		}
    }
    private function pagePath($src)
    {
        if(empty($src))
            throw new ControllerException('page file not found');
        $src = str_replace('.xml','',$src);
        if($src{0} == "/") 
            if(!file_exists(Config::get('ROOT_DIR').Config::get("XMLPAGES_PATH").$src.'.xml'))
                throw new ControllerException('page file not found');
            else $src = Config::get('ROOT_DIR').Config::get("XMLPAGES_PATH").$src.'.xml';
        else
            if(!file_exists(Config::get('ROOT_DIR').Config::get("XMLPAGES_PATH").'/'.$this->controller_name.'/'.$src.'.xml'))
                throw new ControllerException('page file not found');
            else $src = Config::get('ROOT_DIR').Config::get("XMLPAGES_PATH").'/'.$this->controller_name.'/'.$src.'.xml';
        return $src;
    }
    protected function processPage(DomDocument $dom)
    {
        // extends
        $adj_list = array($dom);
        $included_pages = array($this->page.".xml");
        $t_dom = $dom;
        while(($e_src = $t_dom->firstChild->getAttribute('extends')) != "" && !in_array($e_src,$included_pages))
        {
            $t_dom = new DomDocument;
            try { $t_dom->load($this->pagePath($e_src)); }
            catch(ControllerException $e) { throw new ControllerException('extends page not found');}
            //$included_pages[] = $e_src;
            array_unshift($included_pages,$e_src);
            array_unshift($adj_list,$t_dom);
        }
        // searching for <parent> blocks
        for($i = 1, $c = count($adj_list);$i < $c;$i++)
        {
            $nl = t(new DOMXPath($adj_list[$i]))->query("//parent[@id]");
            for($j = 0, $c2 = $nl->length;$j < $c2;$j++)
                for($k = $i-1; $k >=0; $k--)
                {
                    $nl2 = t(new DOMXPath($adj_list[$k]))->query("//block[@id='".$nl->item($j)->getAttribute('id')."']");
                    if(!$nl2->length) continue;
                    $el = $nl->item($j);
                    $el2 = $nl2->item(0);

                    $el2 = $adj_list[$i]->importNode($el2,true);
                    $el->parentNode->replaceChild($el2,$el);
                    break;
                }
        }
        // extending

        $dom = $adj_list[0];
        $blocks = t(new DOMXPath($dom))->query("//block[@id]");
        for($i = 0, $c = $blocks->length;$i < $c;$i++)
        {
            if(($id = $blocks->item($i)->getAttribute("id")) == "") continue;
            for($j = count($adj_list)-1; $j > 0; $j--)
            {
                $subst_blocks = t(new DOMXPath($adj_list[$j]))->query("//block[@id='".$id."']");
                if(!$subst_blocks->length) continue;

                $el = $blocks->item($i);
                $el2 = $subst_blocks->item(0);

                $el2 = $dom->importNode($el2,true);
                $el->parentNode->replaceChild($el2,$el);
                break;
            }
        }


        // clean up from <block>
        $node_list = $dom->getElementsByTagName('block');
        for($i = 0, $c = $node_list->length; $i < $c; $i++)
        {
            for($el = $node_list->item(0),$el_cn = $el->childNodes,$j = 0, $c2 = $el_cn->length;$j < $c2;$j++)
                $el->parentNode->insertBefore($el_cn->item($j)->cloneNode(true),$el);
            $el->parentNode->removeChild($el);
        }

        // include
		$node = $dom->getElementsByTagName("include");
		for($i = 0, $c = $node->length;$i < $c;$i++)
        {
			$el = $node->item(0);
            if(($src = $el->getAttribute('src')) == "") continue;
            try{$src = $this->pagePath($src);}
            catch(ControllerException $e){ throw new ControllerException('include page file not found');}
            $d = new DomDocument;
            $d->load($src);
            $imported_node = $dom->importNode($d->firstChild,true);
            if($imported_node->hasChildNodes())
                for($node_list = $imported_node->childNodes,$i = 0, $c = $node_list->length; $i < $c;$i++)
                    $el->parentNode->insertBefore($node_list->item($i)->cloneNode(true),$el);
            
            $el->parentNode->removeChild($el);
            
        }
        return $dom;
    }
	private function parsePage(DomDocument $dom)
	{

		$node = $dom->getElementsByTagName("WDataSet");
		for($i = 0, $c = $node->length;$i < $c;$i++)
		{
			$el = $node->item(0);
			if(empty($el)) continue;
			$this->addDataSet(simplexml_import_dom($el));
			$el->parentNode->removeChild($el);
		}
		$node = $dom->getElementsByTagName("WDataHandler");
		for($i = 0, $c = $node->length;$i < $c;$i++)
		{
			$el = $node->item(0);
			if(empty($el)) continue;
			$this->addDataHandler(simplexml_import_dom($el));
			$el->parentNode->removeChild($el);
		}

		$node = $dom->getElementsByTagName("WStyle");
		for($i = 0, $c = $node->length;$i < $c;$i++)
		{
			$el = $node->item(0);
			if(empty($el)) continue;
			$this->addStyle(simplexml_import_dom($el));
			$el->parentNode->removeChild($el);
		}

		$xpath = new DOMXPath($dom);
		foreach($xpath->query('//WJavaScript | //WHyperLinkJS | //WFormJS | //WInputJS | //WButtonJS | //WTextareaJS') as $el)
		{
			if(empty($el)) continue;
			$this->addJS(simplexml_import_dom($el));
			$el->parentNode->removeChild($el);
		}
		unset($xpath);

		$node = $dom->getElementsByTagName("WPageHandler");
		for($i = 0, $c = $node->length;$i < $c;$i++)
		{
			$el = $node->item(0);
			if(empty($el)) continue;
			$this->addPageHandler(simplexml_import_dom($el));
			$el->parentNode->removeChild($el);
		}

		$node = $dom->getElementsByTagName("WValueChecker");
		for($i = 0, $c = $node->length;$i < $c;$i++)
		{
			$el = $node->item(0);
			if(empty($el)) continue;
			$this->addValueChecker(simplexml_import_dom($el));
			$el->parentNode->removeChild($el);
		}
	
		$sxml = simplexml_import_dom($dom);
		foreach($sxml as $elem)
			$this->buildWidget($elem);

	}
	function buildWidget(SimpleXMLElement $elem,$system = 0)
	{
		if(($widget_name = WidgetLoader::load($elem->getName())) === false) return;

		$widget = new $widget_name(isset($elem['id'])?(string)$elem['id']:null);
		if(!$widget instanceof WComponent) return;
		$w_id = $widget->getID();
		$this->adjacency_list->add($w_id);

		$widget->parseParams($elem);

		if(isset($elem['dataset']) && isset($this->datasets[(string)$elem['dataset']]))
			$widget->setDataSet($this->datasets[(string)$elem['dataset']]);


		WidgetLoader::load("WStyle");
		if(isset($elem['style']) && isset($this->styles[(string)$elem['style']]))
			$widget->setStyle($this->styles[(string)$elem['style']]);
		else 	$widget->setStyle(new WStyle());

		WidgetLoader::load("WJavaScript");
		if(isset($elem['javascript']) && isset($this->javascripts[(string)$elem['javascript']]))
			$widget->setJavaScript($this->javascripts[(string)$elem['javascript']]);
		else	$widget->setJavaScript(new WJavaScript());

		if($widget instanceof WControl && isset($elem['valuechecker']) && isset($this->valuecheckers[(string)$elem['valuechecker']]))
					$widget->setValueChecker($this->valuecheckers[(string)$elem['valuechecker']]);

		if($widget instanceof WControl && isset($elem['datahandler']) && isset($this->datahandlers[(string)$elem['datahandler']]))
		{
			$this->corresp_map[$widget->getName()]['dh'] = (string)$elem['datahandler'];
			$widget->setDataHandler((string)$elem['datahandler']);
			if(!empty($elem['filter']))
				$this->corresp_map[$widget->getName()]['filter'] = (string)$elem['filter'];
			if(!empty($elem['apply_filter']))
				$this->corresp_map[$widget->getName()]['apply_filter'] = (string)$elem['apply_filter'];
		}
		if($widget instanceof WComponent && $widget->getState())
			$widget->buildComplete();
		if($system)
		{
			$this->system_widgets[$w_id] = $widget;
			return $w_id;
		}
		else
			$this->widgets[$w_id] = $widget;
	}
	function addDataSet(SimpleXMLElement $elem)
	{
		if(WidgetLoader::load("WDataSet") === false) return;

		$ds = new WDataSet(isset($elem['id'])?$elem['id']:null);
		$ds->parseParams($elem);
		$this->datasets[(string)$ds->getId()] = $ds;
	}

	function addDataHandler(SimpleXMLElement $elem)
	{
		if(WidgetLoader::load("WDataHandler") === false) return;

		$dh = new WDataHandler(isset($elem['id'])?$elem['id']:null);
		$dh->parseParams($elem);
		$this->datahandlers[] = $dh;

		/*$this->datahandlers[] = array("id"=>$dh->getId(),'priority'=>$dh->getPriority(),"object"=>$dh); 
		usort($this->datahandlers,create_function('$a,$b',
			'return ($a["priority"] < $b["priority"])?-1:1;'));*/
	}
	protected function addStyle(SimpleXMLElement $elem)
	{
		WidgetLoader::load("WStyle");
		if(empty($elem['id'])) return;
		$s = new WStyle((string)$elem['id']);
		$s->parseParams($elem);
		$this->styles[$s->getId()] = $s;
	}
	protected function addJS(SimpleXMLElement $elem)
	{
		if(empty($elem['id'])) return;
		if(($c_name = WidgetLoader::load($elem->getName())) === false) return;
		$j = new $c_name($elem['id']);
		$j->parseParams($elem);
		$this->javascripts[(string)$elem['id']] = $j;
	}	
	function addPageHandler(SimpleXMLElement $elem)
	{
		if(WidgetLoader::load("WPageHandler") === false) return;

        $this->pagehandler = new WPageHandler();
        $this->pagehandler->parseParams($elem);
	}
	function addValueChecker(SimpleXMLElement $elem)
	{
		if(!isset($elem['id'])) return;
		if(WidgetLoader::load("WValueChecker") === false) return;
		$vc = new WValueChecker((string) $elem['id']);
		$vc->parseParams($elem);
		$this->valuecheckers[$vc->getId()] = $vc;
	}	
	function getValueChecker($id)
	{
		if(isset($id) && isset($this->valuecheckers[$id]))
			return $this->valuecheckers[$id];
		return null;
	}

	function getWidget($id)
	{
		$o = null;
		if(isset($this->widgets[$id]))
			return $this->widgets[$id];
		elseif(isset($this->system_widgets[$id]))
			return $this->system_widgets[$id];
		else return $o;
	}
	function allHTML()
	{
		foreach($this->datasets as $d)
			$d->loadDelayed();

		if(!is_array($this->widgets)) return "";
		reset($this->widgets);					

		foreach($this->widgets as $name=>$widget)
		{
			if(!$widget->getState()) continue;
			$this->widgets[$name]->messageInterchange();
		}
		foreach($this->widgets as $name=>$widget)
		{
			if(!$widget->getState()) continue;
			$this->widgets[$name]->preRender();
			$this->final_html .= $this->widgets[$name]->generateHTML();
			$this->widgets[$name]->postRender();				
		}
	//	$this->saveCorrespMap();
	/*	$h = &CHeader::get();
		for($i = 0, $c = count($this->scripts); $i < $c; $i++)
			$h->add_script('',array('src'=>$this->scripts[$i],'type'=>"text/javascript"));
		for($i = 0, $c = count($this->css); $i < $c; $i++)
			$h->add_css($this->css[$i]);*/
		return $this->final_html;
	}

	function head($echo = 1)
	{
		$h = Header::get();
		foreach($this->scripts as $v)
			$h->addScript($v['src'],$v['cond']);
		foreach($this->css as $v)
			$h->addCSS($v['src'],$v['cond']);
		$v = $h->send();
		$v .= "<body>\n";
		if($echo)
			echo $v;
		else return $v;
	}
	function tail($echo = 1)
	{
		$v = "\n</body></html>";
		if($echo)
			echo $v;
		else return $v; 
	}
	function getDispatcher()
	{
		return $this->dispatcher;
	}
	function getAdjacencyList()
	{
		return $this->adjacency_list;
	}
	function getStyleByName($name = null)
	{
		if(!isset($name) ||empty($this->styles["".$name]))
			return null;
		return $this->styles["".$name];
	}
	function getJavaScriptByName($name = null)
	{
		if(!isset($name) || empty($this->javascripts["".$name]))
			return null;
		return $this->javascripts["".$name];
	}
	function addScript($src = null,$cond = null)
	{
		if(empty($src)) return;
		if(in_array($src,$this->scripts))return;
		/*if(strpos($src,"/") === false)
			$src = "/way_scripts/".$src;*/
		$this->scripts[] = array('src'=>"/".Config::get("JS_VER")."/".$src,'cond'=>$cond);
	}
	function addCSS($src = null,$cond = null)
	{
		if(empty($src)) return;
		if(in_array($src,$this->css)) return;
		/*if(strpos($src,"/") === false)
			$src = "/way_admin/css/".$src;*/
		$this->css[] = array('src'=>"/".Config::get("CSS_VER")."/".$src,'cond'=>$cond);
	}
	function getNavigator()
	{
		return $this->navigator;
	}
	function makeURL($page = null, $p2 = null,$controller_name = null, $get = null)
	{
		if(!isset($controller_name) || !is_scalar($controller_name))
			$controller_name = $this->controller_name;
		if(!isset($page) || !is_scalar($page))
			$page = $this->p1;
		$n_p2 = $this->p2;
		$n_get = array();
		if(isset($p2) && is_array($p2))
		{
			//determining p2 type. If it is assoc, type == 1, if numeric , type == 2
			$p2_type = 1;
			foreach($p2 as $k=>$v)
				if(is_int($k))
					{$p2_type = 2;break;}
			if($p2_type == 1)
			{
				$c_p2 = array_flip($this->p2);
				foreach($p2 as $k=>$v)
				{
					if(isset($c_p2[$k]))
					{
						$n_p2_k = null;
						if($v == null)
							{unset($n_p2[$c_p2[$k]]);continue;}
						else
							$n_p2[$c_p2[$k]] = $v;
					}
					elseif(substr($k,0,1) == "/")
					{
						$flag = 0;
						foreach($this->p2 as $temp_k_p2 => $temp_v_p2)
							if(preg_match($k,$temp_v_p2))
							{ 
								if($v === null)
								{
									unset($n_p2[$temp_k_p2]);
									$flag = 1;break;
								}
								else 
								{
									$n_p2[$temp_k_p2] = preg_replace($k,$v,$temp_v_p2); 
									$flag = 1; break;
								}
							}
						if(!$flag && !empty($v))
							$n_p2[] = $v;
					}
					elseif(!empty($k))
						$n_p2[] = $k;
				}
			}
			else
				foreach($p2 as $k=>$v)
				{
					if($v == null)
					{unset($n_p2[(int)$k]);continue;}
					$n_p2[(int)$k] = $v;
				}
		}
		$n_get = $c_get = $this->get->getAllChecked();
		foreach($n_get as $k=>$v)
			if(substr($k,0,2) == "__") unset($n_get[$k]);
		if(isset($get) && is_array($get))
		{
			foreach($get as $k=>$v)
			{
				if(substr($k,0,2) == "__") continue;
				if(isset($c_get[$k]))
					if($v == null)
						unset($n_get[$k]);
					else
						$n_get[$k] = $v;
				else
					$n_get[$k] = $v;
			}
		}

		$n_get2 = array();
		foreach($n_get as $k=>$v)
			$n_get2[] = $k."=".$v;
		foreach($n_p2 as $k=>$v)
			if(empty($v)) unset($n_p2[$k]);
		return 	Filter::filter("http://".$_SERVER['SERVER_NAME']."/".$controller_name."/".
			(!empty($n_p2)?implode("/",$n_p2)."/":"").(!empty($page) && strpos($page,".") === false?$page.".html":$page).
			(!empty($n_get2)?"?".implode("&",$n_get2):""),	Filter::STRING_QUOTE_ENCODE	);


		//for testing
		//var_dump($this->makeURL('nnn',array("c"=>"c2",'bb'=>'bb')));
		//var_dump($this->makeURL(null,array('p1','p2')));
	}
	function getDisplayModeParams()
	{
		return $this->display_mode_params;
	}
	function getPage()
	{
		return $this->page;
	}
	function getControllerName()
	{
		return $this->controller_name;
	}
	function XMLPageChanged($mtime)
	{
		if(!isset($mtime)) return true;
		$file = Config::get('ROOT_DIR').Config::get("XMLPAGES_PATH")."/".$this->controller_name."/".$this->page.".xml";
		return pageChanged($file,$mtime); 
	}
	private function handlePOST()
	{
		if($this->post->isEmpty()) return;

		$this->restoreSignatures();
		$this->restoreCheckers();
        $this->restorePageHandler();

		if(!in_array($this->post->__sig,$this->form_signatures))
			$this->gotoStep_1();

		POSTErrors::flushErrors();
		POSTChecker::checkByRules($this->post,$this->checker_rules);
		if(POSTErrors::hasErrors())
		{
			POSTErrors::saveErrorList();
			$this->gotoStep_1();
		}

		DataUpdaterPool::restorePool();
		try
		{
			DataUpdaterPool::callCheckers();
		}
		catch(CheckerException $e)
		{
			POSTErrors::addError($e->getWidgetName(),$e->getAdditionalId(),$e->getMessage());
		}
		if(POSTErrors::hasErrors())
		{
			POSTErrors::saveErrorList();
			$this->gotoStep_1();
		}
		DataUpdaterPool::callHandlers();
		DataUpdaterPool::callFinilze();

        $ret = $this->pagehandler->handle();

        /*var_dump($ret);
        die("BBBBBBBBB");*/
        if(is_string($ret))
            $this->gotoLocation($ret);
        elseif(is_numeric($ret))
            $this->gotoLocation($this->navigator->getStepURL($ret));

		$this->gotoStep_1();
	}
	private function gotoStep_1()
	{
		$s = $this->navigator->getStep(-1);
		if(isset($s,$s['url']))
			header("Location: ".$s['url']);
		exit();
    }
    private function gotoLocation($loc)
    {
        if(isset($loc))
            header("Location: ".$loc);
        exit();
    }
	// checkers
	function setChecker($name,$rule,$rule_value)
	{
		if(!isset($name) || !isset($rule) || !isset($rule_value)) return;
		$this->checker_rules[$name][$rule] = trim($rule_value);
	}
	private function restoreCheckers()
	{
		$storage = Storage::createWithSession("controller");
		$this->checker_rules = $storage->get('checker_rules');
		$storage->un_set('checker_rules');
		if(!is_array($this->checker_rules))
			$this->checker_rules = array();
	}
	// signatures
	function addFormSignature($sig = null)
	{
		if(!isset($sig)) return;
		$this->form_signatures[] = $sig;
	}
	private function checkSignature($sig = null)
	{
		if(!isset($sig)) return false;
		return in_array($sig,$this->form_signatures);
	}
	private function restoreSignatures()
	{
		$storage = Storage::createWithSession("controller");
		$this->form_signatures = $storage->get('signatures');
		$storage->un_set('signatures');
		if(!is_array($this->form_signatures))
			$this->form_signatures = array();
	}
    private function restorePageHandler()
    {
        if(WidgetLoader::load("WPageHandler") === false) return;
		$storage = Storage::createWithSession("controller");
		$this->pagehandler = $storage->get('pagehandler');
		$storage->un_set('pagehandler');
    }
	// destructor
	function __destruct()
	{
		$storage = Storage::createWithSession("controller");
		$storage->set('signatures',$this->form_signatures);
		$storage->set('checker_rules',$this->checker_rules);
		DataUpdaterPool::savePool();
        $storage->set('pagehandler',$this->pagehandler);
		DB::close();
	}
}
class DisplayModeParams
{
	protected 
		$widget_params = array();
	public 
		$predicted_from = null,
		$predicted_limit = null
		;
		

	function set($widget_id,$from,$limit,$count)
	{
		if(!isset($widget_id) || !is_numeric($from) || !is_numeric($limit) || !is_numeric($count)) return;
		$this->widget_params[$widget_id] = array(
			"from"=>$from,
			"limit"=>$limit,
			"count"=>$count,
			"current"=>$from
			);
	}
	function getFrom($widget_id)
	{
		return !isset($this->widget_params[$widget_id])?$this->widget_params[$widget_id]['from']:0;
	}
	function getLimit($widget_id)
	{
		if(!isset($this->widget_params[$widget_id])) return 0;
		if($this->widget_params[$widget_id]['from'] + $this->widget_params[$widget_id]['limit'] > $this->widget_params[$widget_id]['count'])
			return $this->widget_params[$widget_id]['count'] - $this->widget_params[$widget_id]['from'];
		return $this->widget_params[$widget_id]['limit'];
	}
	function getCurrent($widget_id,$scope)
	{
		if(!isset($this->widget_params[$widget_id])) return;
		if($scope == "global")
			return $this->widget_params[$widget_id]['current'];
		else
			return $this->widget_params[$widget_id]['current'] - $this->widget_params[$widget_id]['from'];
	}

	function incCurrent($widget_id)
	{
		if(!isset($this->widget_params[$widget_id])) return;
		if($this->widget_params[$widget_id]['current'] - $this->widget_params[$widget_id]['from']+1 > 
			$this->widget_params[$widget_id]['limit']) return;

		$this->widget_params[$widget_id]['current']++;
	}
	function resetCurrent($widget_id)
	{
		if(!isset($this->widget_params[$widget_id])) return ;
		$this->widget_params[$widget_id]['current'] = $this->widget_params[$widget_id]['from'];
		
	}
	function isFirst($widget_id,$scope)
	{
		if(!isset($this->widget_params[$widget_id])) return false;
		if($scope == "global")
			return $this->widget_params[$widget_id]['current'] == 0;
		else
			return $this->widget_params[$widget_id]['current'] == $this->widget_params[$widget_id]['from'];
	}
	function isLast($widget_id,$scope)
	{
		if(!isset($this->widget_params[$widget_id])) return false;
		if($scope == "global")
			return $this->widget_params[$widget_id]['current'] == $this->widget_params[$widget_id]['count']-1;
		return $this->widget_params[$widget_id]['current'] == 
			$this->widget_params[$widget_id]['from'] + $this->widget_params[$widget_id]['limit'] -1;
		
	}
}
?>
