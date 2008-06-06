<?php
// $Id:  $
//

require("functions.php");
require("Filter.php");
require("ParamHolder.php");
require("Header.php");
require("Config.php");
require("Storage.php");
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
			$dispatcher = null
		;
	function __construct()
	{
		$this->get = new ParamHolder($_GET);
		$this->post = new ParamHolder($_POST);
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
			$this->addDataSet(simplexml_import_dom($el));
			$el->parentNode->removeChild($el);
		}

		$node = $dom->getElementsByTagName("WDataHandler");
		for($i = 0, $c = $node->length;$i < $c;$i++)
		{
			$el = $node->item($i);
			$this->addDataHandler(simplexml_import_dom($el));
			$el->parentNode->removeChild($el);
		}

		$node = $dom->getElementsByTagName("WStyle");
		for($i = 0, $c = $node->length;$i < $c;$i++)
		{
			$el = $node->item($i);
			$this->addStyle(simplexml_import_dom($el));
			$el->parentNode->removeChild($el);
		}

		$xpath = new DOMXPath($dom);
		foreach($xpath->query('//WJavaScript | //WHyperLinkJS | //WFormJS | //WInputJS | //WButtonJS | //WTextareaJS') as $el)
		{
			$this->addJS(simplexml_import_dom($el));
			$el->parentNode->removeChild($el);
		}
		unset($xpath);

		$node = $dom->getElementsByTagName("WPageHandler");
		for($i = 0, $c = $node->length;$i < $c;$i++)
		{
			$el = $node->item($i);
			$this->addPageHandler(simplexml_import_dom($el));
			$el->parentNode->removeChild($el);
		}

		$node = $dom->getElementsByTagName("WValueChecker");
		for($i = 0, $c = $node->length;$i < $c;$i++)
		{
			$el = $node->item($i);
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
	function addScript($src = null)
	{
		if(empty($src)) return;
		if(in_array($src,$this->scripts))return;
		/*if(strpos($src,"/") === false)
			$src = "/way_scripts/".$src;*/
		$this->scripts[] = $src;
	}
	function addCSS($src = null)
	{
		if(empty($src)) return;
		if(in_array($src,$this->css)) return;
		/*if(strpos($src,"/") === false)
			$src = "/way_admin/css/".$src;*/
		$this->css[] = $src;
	}

}
?>
