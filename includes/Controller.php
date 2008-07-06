<?php
// $Id:  $
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
require("ResultSet.php");


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
			$p2 = null,
			$post = null,
			$get = null
		;

	protected 
			$header = null,
			$page = "index",
			$page_function = null,
			$navigator = null,
			$controller_name = null,
			$final_html = "",
			$dispatcher = null,
			$scripts = array(),
			$css = array(),
			$valuecheckers = array(),
			$widgets = array(),
			$display_mode = self::DISPLAY_REGULAR,
			$display_mode_params = null
			;

	const DISPLAY_REGULAR = 1;
	const DISPLAY_ITERATIVE = 2;

	function __construct()
	{
		$this->get = new HTTPParamHolder($_GET);
		$this->post = new HTTPParamHolder($_POST);
		$this->header = Header::get();
		$this->dispatcher = new EventDispatcher();
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
		if(is_string($this->page_function))
			$ret = call_user_func($this->page_function,$this->p1,$this->p2);
		if(!isset($ret))
			if(isset($this->p1))
				$this->page = $this->p1;
			else
				$this->page = 'index';
		else
			$this->page = $ret;

		if(preg_match("/^\/controllers\/(\w+)\.php$/",$_SERVER['PHP_SELF'],$m))
			$this->controller_name = $m[1];

		if(!file_exists(Config::get('ROOT_DIR')."/pages/".$this->controller_name."/".$this->page.".xml"))
			throw new ControllerException('page file not found');

		$this->navigator = new Navigator($this->controller_name);
		$this->navigator->addStep($this->page);

		$this->addCSS("ns_reset.css");
		$this->addScript("jquery.js");
		$this->addScript("jquery.cookie.js");
		$this->addScript("jquery.bgiframe.js");
		$this->addScript("jquery.tooltip.js");
		$this->addCSS("jquery.tooltip.css");
		$this->addScript("jquery.treeview.js");
		$this->addScript("IE8.js","IE");
		$this->addCSS("default.css");
		/*$this->addScript("php_serialize.js");
		$this->addScript("swfobject.js");
		$this->addScript("formatDate.js");
		$this->addScript("w.js");*/
	
		$dom = new DomDocument;
		$dom->load(Config::get('ROOT_DIR')."/pages/".$this->controller_name."/".$this->page.".xml");
		$this->parsePage($dom);

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
	private function parsePage(DomDocument $dom)
	{
		$node = $dom->getElementsByTagName("WDataSet");
		for($i = 0, $c = $node->length;$i < $c;$i++)
		{
			$el = $node->item($i);
			if(empty($el)) continue;
			$this->addDataSet(simplexml_import_dom($el));
			$el->parentNode->removeChild($el->parentNode->firstChild);
		}

		$node = $dom->getElementsByTagName("WDataHandler");
		for($i = 0, $c = $node->length;$i < $c;$i++)
		{
			$el = $node->item($i);
			if(empty($el)) continue;
			$this->addDataHandler(simplexml_import_dom($el));
			$el->parentNode->removeChild($el->parentNode->firstChild);
		}

		$node = $dom->getElementsByTagName("WStyle");
		for($i = 0, $c = $node->length;$i < $c;$i++)
		{
			$el = $node->item($i);
			if(empty($el)) continue;
			$this->addStyle(simplexml_import_dom($el));
			$el->parentNode->removeChild($el->parentNode->firstChild);
		}

		$xpath = new DOMXPath($dom);
		foreach($xpath->query('//WJavaScript | //WHyperLinkJS | //WFormJS | //WInputJS | //WButtonJS | //WTextareaJS') as $el)
		{
			if(empty($el)) continue;
			$this->addJS(simplexml_import_dom($el));
			$el->parentNode->removeChild($el->parentNode->firstChild);
		}
		unset($xpath);

		$node = $dom->getElementsByTagName("WPageHandler");
		for($i = 0, $c = $node->length;$i < $c;$i++)
		{
			$el = $node->item($i);
			if(empty($el)) continue;
			$this->addPageHandler(simplexml_import_dom($el));
			$el->parentNode->removeChild($el->parentNode->firstChild);
		}

		$node = $dom->getElementsByTagName("WValueChecker");
		for($i = 0, $c = $node->length;$i < $c;$i++)
		{
			$el = $node->item($i);
			if(empty($el)) continue;
			$this->addValueChecker(simplexml_import_dom($el));
			$el->parentNode->removeChild($el->parentNode->firstChild);
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
			$w_id = $widget->getID();
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
	/*function addPageHandler(SimpleXMLElement $elem)
	{
		$this->pagehandler = new WPageHandler();
		if(!empty($this->datahandlers[$arr['attr']['datahandler']]))
			$this->pagehandler->setDatahandler($this->datahandlers[$arr['attr']['datahandler']]);
		$this->pagehandler->setHandler($this->vtsaSearch($arr,"handler"));
		$this->pagehandler->setParams($this->all_params['get']);
		$this->pagehandler->setGotoURL($arr['attr']['goto']);
	}*/
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
		if(!empty($this->widgets[$id]))
			return $this->widgets[$id];
		elseif(!empty($this->system_widgets[$id]))
			return $this->system_widgets[$id];
		else return $o;
	}
	function allHTML()
	{
		if(!is_array($this->widgets)) return "";
		reset($this->widgets);					

		foreach($this->widgets as $name=>$widget)
		{
			if(!$widget->getState()) continue;
			$this->widgets[$name]->messageInterchange();
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
					if(isset($c_p2[$k]))
					{
						$n_p2_k = null;
						if($v == null)
							{unset($n_p2[$c_p2[$k]]);continue;}
						elseif(substr($k,0,1) == "/")
							$n_p2_k = preg_replace($k,$v,$c_p2[$k]);
						else
							$n_p2_k = $v;
						$n_p2[$c_p2[$k]] = $n_p2_k;
					}
					else
						$n_p2[] = $k;
			}
			else
				foreach($p2 as $k=>$v)
				{
					if($v == null)
					{unset($n_p2[(int)$k]);continue;}
					$n_p2[(int)$k] = $v;
				}
		}
		if(isset($get) && is_array($get))
		{
			$n_get = $c_get = $this->get->getAllChecked();
			foreach($n_get as $k=>$v)
				if(substr($k,0,2) == "__") unset($n_get[$k]);
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
		return 	Filter::filter("http://".$_SERVER['SERVER_NAME']."/".$controller_name."/".
			(!empty($n_p2)?implode("/",$n_p2)."/":"").(strpos($page,".") === false?$page.".html":$page).
			(!empty($n_get2)?"?".implode("&",$n_get2):""),	Filter::STRING_QUOTE_ENCODE	);


		//for testing
		//var_dump($this->makeURL('nnn',array("c"=>"c2",'bb'=>'bb')));
		//var_dump($this->makeURL(null,array('p1','p2')));
	}
	function setDisplayMode($mode)
	{
		if($mode == self::DISPLAY_REGULAR || $mode == self::DISPLAY_ITERATIVE)
		{
		//	if($this->display_mode != $mode)
		//		$this->display_mode_params = new DisplayModeParams();
			$this->display_mode = $mode;
		}
		
	}
	function getDisplayMode()
	{
		return $this->display_mode;
	}
	/*function setDisplayModeParams(DisplayModeParams $p)
	{
		$this->display_mode_params = $p;
	}*/
	function getDisplayModeParams()
	{
		if(!isset($this->display_mode_params))
			$this->display_mode_params = new DisplayModeParams();
		return $this->display_mode_params;
	}
}
class DisplayModeParams
{
	protected 
		$iterative_count = 0,
		$iterative_current = 0
		;
	function __get($param)
	{
		return property_exists($this,$param)?$this->$param:null;
	}
	/*function __set($param,$value)
	{
		if(property_exists($this,$param))
			$this->$param = $value;
	}*/
	function updateIterativeCount($cnt)
	{
		if(!is_numeric($cnt)) return;
		if(!isset($this->iterative_count) || $cnt > $this->iterative_count)
			$this->iterative_count = $cnt;
	}
	function setIterativeCurrent($cur)
	{
		if(isset($cur) && 0+$cur > 0)
			$this->iterative_current = $cur;
	}

}
?>
