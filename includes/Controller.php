<?php
// $Id: WText.php 1020 2008-03-19 17:24:58Z point $
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
		else return null;
	}
}
class Controller
{
	public	$post = null,
			$get = null;
	private $header = null,
			$page = "index",
			$function = null,
			$navigator = null,
			$p1 = null,
			$p2 = null,
			$controller_name = null,
			$final_html = "",
			$dispatcher = null
		;
	function __construct()
	{
		$this->get = new ParamHolder($_GET);
		$this->post = new ParamHolder($_POST);
		$this->header = Header::get();
		$this->navigator = new Navigator("index");
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
			$this->function = $func;
	}
	function setPageClassMethod($class_name,$func)
	{
		if(is_callable($class_name,$func))
			$this->function = $class_name."::".$func;
	}
	function init()
	{
		$ret = null;
		$this->parseP1P2();	
		if(is_string($this->function))
			$ret = call_user_func($this->function,$this->p1,$this->p2);
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
		$widget_name = WidgetLoader::load($elem->getName());
		if(!isset($widget_name)) return ;

		$widget = new $widget_name(isset($elem['id'])?$elem['id']:null);
		if(!$widget instanceof WComponent) return;
		$widget->parseParams($elem);

		if(isset($elem['dataset']) && isset($this->datasets[$elem['dataset']]))
			$widget->setDataSet($this->datasets[$elem['dataset']]);


		WidgetLoader::Load("WStyle");
		if(isset($elem['style']) && isset($this->styles[$elem['style']]))
			$widget->setStyle($this->styles[$s_name]);
		else 	$widget->setStyle(new WStyle());

		WidgetLoader::load("WJavaScript");
		if(isset($elem['javascript']) && isset($this->javascripts[$elem['javascript']]))
			$widget->setJavaScript($this->javascripts[$j_name]);
		else	$widget->setJavaScript(new WJavaScript());

		if($widget instanceof WControl && isset($elem['valuechecker']) && isset($this->valuecheckers[$elem['valuechecker']]))
					$widget->setValueChecker($this->valuecheckers[$elem['valuechecker']]);

		if($widget instanceof WControl && isset($elem['datahandler']) && isset($this->datahandlers[$elem['datahandler']]))
		{
			$this->corresp_map[$widget->getName()]['dh'] = $elem['datahandler'];
			$widget->setDataHandler($elem['datahandler']);
			if(!empty($elem['filter']))
				$this->corresp_map[$widget->getName()]['filter'] = $elem['filter'];
			if(!empty($elem['apply_filter']))
				$this->corresp_map[$widget->getName()]['apply_filter'] = $elem['apply_filter'];
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
		WidgetLoader::load("WDataSet");
		/*$ds = new WDataSet();
		$ds->setName($arr['attr']['name']);

		$ds->setClassname($this->vtsaSearch($arr,"classname"));
		$ds->setDatasource($this->vtsaSearch($arr['value'],"datasource"));
		if(isset($arr['attr']['label']))
			$ds->setLabel($arr['attr']['label']);
		$ds->setParams(array_merge($ds->getParams(),$this->retrieveParamsByLabel($ds->getLabel())));
		$additional_params = array();
		for($i = 0; $i < count($arr['value']); $i++)
			if($arr['value'][$i]['name'] == "param")
				$additional_params[$arr['value'][$i]['attr']['name']] = $arr['value'][$i]['attr']['value'];
		$ds->setParams(array_merge($ds->getParams(),$additional_params));
		$ds->setPreload( isset($arr['attr']['preload']) ? $arr['attr']['preload']: null);
		$ds->setPreloadParam( isset( $arr['attr']['preload_param']) ? $arr['attr']['preload_param'] : null);
		$ds->setStatic( isset( $arr['attr']['static']) ?$arr['attr']['static'] : null );
		$ds->setDontUseOid( isset( $arr['attr']['dont_use_oid']) ? $arr['attr']['dont_use_oid']: null );
		$value = $arr['value'];
		$user_func_params = array();
		for($i = 0; $i < count($value); $i++)
		{
			if($value[$i]['name'] == "user_func")
			{
				$value = $value[$i]['value'];
				$k = 0;
				for($j = 0; $j < count($value); $j++)
				{
					if($value[$j]['name'] != "param") continue;
					$user_func_array[$k]['type'] = isset( $value[$j]['attr']['type']) ?$value[$j]['attr']['type'] : null;
					$user_func_array[$k]['variable'] = (!empty($value[$j]['attr']['variable']))?$value[$j]['attr']['variable']:null;
					$user_func_array[$k++]['constant'] = (!empty($value[$j]['attr']['constant']))?$value[$j]['attr']['constant']:null;
				}
				$ds->setUserFuncParams($user_func_array);
				break;
			}
		}
		$this->datasets[$arr['attr']['name']] = &$ds;
		 */
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
		$v = $h->send();
		$v .= "<body>\n";
		if($echo)
			echo $v;
		else return $v;
	}
	function tail($echo = 1)
	{
		$v = "</body></html>";
		if($echo)
			echo $v;
		else return $v; 
	}

}
?>
