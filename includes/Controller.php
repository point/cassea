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
 * This file contains classes that encapsulates main logic of the application 
 * such as Controller and AjaxController plus several helper classes.
 *
 * It should be included (or required) first of all other. Typical front controller logic 
 * consists of these statements:
 *
 * <pre><code>
 * require("../includes/Controller.php");
 * $c = Controller::getInstance();
 * $c->init();
 * $c->getHeadBodyTail(1);
 * </code></pre>
 *
 * This code could be founded at <root_dir>/controllers/common.php.inc
 * If this behaviour is enough for particular front controller, user may simply
 * require <root_dir>/controllers/common.php.inc file.
 *
 * @author point <alex.softx@gmail.com>
 * @author billy <alexey.mirniy@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: Controller.php 184 2009-11-05 15:14:47Z point $
 * @package system
 * @since 
 */

/**
 * Need for properly booting of app
 */
require_once(dirname(__FILE__)."/Boot.php");

//{{{ WidgetLoader
/**
 * !!! Deprecated 
 * Widgets are now loading with system-wide Autoload machanism.
 *
 * Helper class to include files with widgets.
 * Used instead of default autoload mechanism, because in calling side 
 * need to know does such widget exists.
 */
class WidgetLoader
{
	//{{{ load
	/**
	 * Tries to include file and class with given widget name.
	 *
	 * @param string name of the widget to load
	 * @returm mixed it can be boolean false -- indicates that such widget doesn't exists. 
	 * Or string -- name of class/file (without extension)
	 */
	static function load($name)
	{
		if(is_null($name))return false;
		if (class_exists($name,false)) return $name;

		$c = Config::getInstance();
		$rd = $c->root_dir;
		$vd = $c->vendors_dir;
		if(file_exists( $p = $rd.$vd."/widgets/".$name.".php"));
		elseif(file_exists($p= $rd."/includes/widgets/".$name.".php"));
		else return false;
		require($p);
		return $name;
	}
	//}}}
}
//}}}

//{{{ Controller
class Controller extends EventBehaviour
{
	/**
	 * Max count of form signatures per user to store
	 */
	const MAX_SIGNATURES = 32; //per user

	/**
	 * @var instance of Controller class
	 * @static
	 */
	static  $instance = null;
	
	public	
		/**
		 * Parameters of current request
		 *
		 * @var mixed
		 */
		$p1 = null,
		/**
		 * "p2" parameters of current request.
		 * @var array
		 */
		$p2 = array(),
		/**
		 * Holds all filtered and checked POST data. Use $_POST in extreme cases.
		 *
		 * @var HTTPParamHolder
		 */
		$post = null,
		/**
		 * Holds all filtered and checked GET data. Use $_GET in extreme cases.
		 *
		 * @var HTTPParamHolder 
		 */
		$get = null,
		/**
		 * Holds all filtered and checked COOKIE data. Use $_COOKIE in extreme cases.
		 *
		 * @var object of HTTPParamHolder class. 
		 */
		$cookie = null
		;

	protected 
	
		/**
		 * Defines whenever to reqister step in Navigator
		 *
		 * @var bool
		 */
		$register_step = 1,
		/**
		 * Defines postfix to add to storage name. 
		 * Used for prevent mixing of stored values in case of AJAX requests.
		 *
		 * @var string
		 */
		$storage_postfix = "default",
		/**
		 * This flag sets to true if {@link init} was made completely.
		 * 
		 * @var bool
		 */
		$inited = false,
		/**
		 * Header cached object
		 *
		 * @var Header
		 */
		$header = null,
		/**
		 * Finded page name. Default is "index".
		 *
		 * @var string
		 */
		$page = "index",
		/**
		 * Function that should be used to retrieve name of the page
		 *
		 * @var callable
		 */
		$page_function = null,
		/**
		 * List of DataSets used on the page
		 *
		 * @var array
		 */
		$datasets = array(),
		/**
		 * List of DataHandlers used on the page
		 *
		 * @var array
		 */
		$datahandlers = array(),
		/**
		 * Tracks steps on pages. Used to rewind.
		 *
		 * @var Navigator
		 */
		$navigator = null,
		/**
		 * Name of the current front controller (index, about, catalog etc).
		 *
		 * @var string
		 */
		$controller_name = null,
		/**
		 * Widget event dispatcher. Used to pass events from/to widgets
		 *
		 * @var WidgetEventDispatcher
		 */
		$dispatcher = null,
		/**
		 * List of javascripts, used on the current page
		 *
		 * @var array
		 */
		$scripts = array(),
		/**
		 * List of CSS, used on the current page
		 *
		 * @var array
		 */
		$css = array(),
		/**
		 * List of {@link WValueChecker valuecheckers} used on the current page.
		 *
		 * @var array
		 */
		$valuecheckers = array(),
		/**
		 * List of non-system widgets, used on the current page.
		 *
		 * @var array
		 */
		$widgets = array(),
		/**
		 * List of system widgets, used on the current page.
		 *
		 * @var array
		 */
		$system_widgets = array(),
		/**
		 * Defines various parameters of page display
		 *
		 * @var array
		 */
		$display_mode_params = null,
		/**
		 * Holds list of widget's predeccessors.
		 *
		 * @var WidgetAdjacencyList
		 */
		$adjacency_list = null,
		/**
		 * List of form signatures used to check authority of the incoming post data
		 *
		 * @var array
		 */
		$form_signatures = array(),
		/**
		 * List of rules to check incoming POST data
		 *
		 * @var array
		 */
		$checker_rules = array(),
		/**
		 * List of rules to check incoming files, sended via POST
		 *
		 * @var array
		 */
		$file_rules = array(),
		/**
		 * List of custom error messages for checking POST data
		 *
		 * @var array
		 */
		$checker_messages = array(),
		/**
		 * PageHandler object. It manages where to go after post handling.
		 *
		 * @var PageHandlerObject
		 */
		$pagehandler = null,
		/**
		 * List of files that are included in current page or 
		 * located up by the extending hierarchy tree.
		 *
		 * @var array
		 */
		$ie_files = array(), //included and extending files

		/**
		 * Storage object that holds notify messages
		 *
		 * @var Storage
		 */
		$notifyStorage = null,
		/**
		 * This flag setted to true if current request was made via XMLHTTPRequest
		 *
		 * @var bool
		 */
		$is_ajax = false,
		/**
		 * Alternative response string. Set it if you want to suppress standard output,
		 * obtained by widgets render mechanism. Useful in ajax responses.
		 *
		 * @var string
		 */
		$response_string = null,
		/**
		 * Holds ids of forms which should not be checked by signature checker and 
		 * by value checkers
		 *
		 * @var array of form ids
		 */
		$no_check_forms = array()
		;

	//{{{ __construct
	/**
	 * This function called when calling {@link getInstance} at first time.
	 * 
	 * It checks controller name, makes base checks, initializes post, get and cookie variables,
	 * parse "p1" and "p2" parameters.
	 *
	 * It triggers "BeforeConstruct" and "AfterConstruct" events. $this passed as 1st argument.
	 *
	 * @param null
	 * @return null
	 * @throws ControllerException if controller name wasn't defined
	 * @see parseP1P2
	 */
	protected function __construct()
	{
		$this->trigger("BeforeConstruct",$this);

		if(preg_match("/^\/controllers\/(\w+)\.php$/",$_SERVER['PHP_SELF'],$m))
			$this->controller_name = $m[1];
		else throw new ControllerException('controller name not defined');

        //some browsers (ie) have bugs, if host contains underscore
        if(strpos($_SERVER['HTTP_HOST'],"_") !== false)
            Header::redirect(str_replace("_","-",requestURI(1)), 301);

		$this->storage_postfix = $this->controller_name;

		$this->get = new HTTPParamHolder($_GET);
        $this->post = new HTTPParamHolder($_POST,1);
        $this->post->cleanStrings();
        $this->cookie = new HTTPParamHolder($_COOKIE);

		$this->parseP1P2();	

		$this->trigger("AfterConstruct",$this);

	}
	//}}}

	//{{{ getInstance
	/**
	 * Singleton method. Used to construct or return controller object. Depending on type of request
	 * Controller or AjaxController instance will be returned.
	 *
	 * @param null
	 * @return Controller object
	 */ 
	static function getInstance()
	{
        if(!isset(self::$instance))
        {
            if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === "XMLHttpRequest")
                self::$instance = new AjaxController();
            else 
                self::$instance = new Controller();
        }
		return self::$instance;
	}
	//}}}
	
	//{{{ setPageFunc
	/**
	 * Sets callback to function, that defines name of the page to be displayed.
	 *
	 * This function must be called before {@link init} method. Only callable 
	 * parameter allowed.
	 *
	 * @param callable function to be called.
	 * @return null
	 */
	function setPageFunc($func)
	{
		if(is_callable($func)) $this->page_function = $func;
		else
			throw new ControllerException('Given callback "'.print_r($func, true).'" isn\'t callable');
	}
	//}}}


	//{{{ init
	/**
	 * This method inits lots of inner components of the system.
	 * It designed to be called directly by user.
	 *
	 * It also calls handler for POST data if current request method is post.
	 * Otherwise application continues to initialize data for GET request,
	 * adds essential javascript and css files, parse current page
	 * and sets flag inited to "true". 
	 * 
	 * Triggers "BeforeInit", "BeforeHandlePOST", "BeforeAddScript", "AfterInit" events.
	 * $this passed as 1st argument in all of this events.
	 * @param null
	 * @return null
	 * @throws ControllerException if system can't load page file
	 */
	function init()
    {
		$this->trigger("BeforeInit",$this);

		Boot::setupAll();
        
        $this->header = Header::get();
		$this->dispatcher = new WidgetEventDispatcher();
		$this->display_mode_params = new DisplayModeParams();
		$this->adjacency_list = new WidgetsAdjacencyList();

		$this->navigator = new Navigator();

        $full_path = $this->findPage();

		$dom = new DomDocument;
        if($dom->load($full_path) === false)
            throw new ControllerException("Can not load XML ".$full_path);

		$this->restoreSignatures();
		POSTErrors::restoreErrorList();
		
		$this->trigger("BeforeHandlePOST",$this);

        if($_SERVER['REQUEST_METHOD'] == "POST")
		{
			$this->restoreCheckers();
			$this->parsePageOnPOST($this->processPage($dom));
			$this->handlePOST();
			exit();
		}

		$this->trigger("BeforeAddScript",$this);

		$this->addScript("jquery.js",null,5);
		$this->addScript("jquery.cookie.js",null,5);
		$this->addScript("jquery.bgiframe.js",null,5);
		$this->addScript("jquery.tooltip.js",null,5);
		$this->addCSS("jquery.tooltip.css",null,5);
		$this->addCSS("default.css",null,5);
		$this->addScript("default.js",null,5);

		if($this->register_step)
			$this->navigator->addStep($this->page);

        $this->parsePageOnGET($this->processPage($dom));
        
		$this->inited = true;

		$this->trigger("AfterInit",$this);
    }
	//}}}
	
	//{{{ findPage
	/**
	 * Tries to find page to display basing on "p1", "p2" parameters
	 *
	 * Pages will be looked-up page in XMLPAGES_DIR. If no pages were found 
	 * 404 error and ControllerException will be raised.
	 *
	 * If page is marked as internal, ControllerException will be raised.
	 *
	 * Triggers "BeforeFindPage" and "AfterFindPage" events. $this passed as 1st argument.
	 *
	 * @param null
	 * @return string full path to the page (from the server's root).
	 * @throws ControllerException in case of error.
	 */
    protected function findPage()
    {
		$this->trigger("BeforeFindPage",$this);

        $ret = null;
		if(!is_null($this->page_function))
			$ret = str_replace('.xml','',call_user_func($this->page_function,$this->p1,$this->p2));
		if(!isset($ret))
			if(!empty($this->p1))
				$this->page = $this->p1;
			else
				$this->page = 'index';
		else
			$this->page = $ret;

		$this->trigger("PageFinded",array($this,&$this->page));

		$full_path = $this->pagePath($this->page);

        if(preg_match('/internal\s*=\s*[\'"`]\s*([^\'"`]+)\s*[\'"`]/',file_get_contents($full_path,null,null,0,100),$m) && (bool)$m[1])
            throw new ControllerException('page '.$this->page.' is for internal use only');

		$this->trigger("AfterFindPage",$this);

        return $full_path;
	}
	//}}}
	
	//{{{ parseP1P2
	/**
	 * Retrieves and parses "p1" and "p2" parameters from GET.
	 * Used by internal functions at the initialization time.
	 *
	 * @param null
	 * @return null
	 */
	protected final function parseP1P2()
	{
		$this->get->bindFilter('__p1',Filter::STRING_QUOTE_ENCODE);
		$this->get->bindFilter('__p2',Filter::STRING_QUOTE_ENCODE);
		if(isset($this->get->__p1))
			$this->p1 = $this->get->__p1;
			
		if(isset($this->get->__p2))
		{
			$this->p2 = urldecode($this->get->__p2);

            $this->p2 = trim($this->p2,"/");

            if(!empty($this->p2))
                $this->p2 = explode("/",$this->p2);
            else $this->p2 = array();
		}
    }
	//}}}
	
	//{{{ pagePath
	/**
	 * Returns full page path based on given src.
	 * 
	 * If $src is a string with leading slash, page will be looked-up 
	 * from XMLPAGES_DIR. Otherwise it will be looked-up from the dir
	 * with paages for current controller.
	 *
	 * If page not found in XMLPAGES_DIR, {@link vendorPagePath} will be
	 * called to try to find the page.
	 * 
	 * @param string partial src of the page to find
	 * @param bool if set to true page will be looking-up in the vendor
	 * dir only.
	 * @return string full path to the page from server's root.
	 * @see vendorPagePath
	 * @throws ControllerException if unable to find page path
	 */
    protected final function pagePath($src, $vendor_only = false)
    {
        if(empty($src))
			throw new ControllerException('page file not set');


		// page name to xml file
		$src .= ((substr($src,-4) != '.xml')?'.xml':''); // first.xml.example.xml;
		$src = ($src{0} != "/")?('/'.$this->controller_name.'/'.$src):$src;

		if ($vendor_only) return  $this->vendorPagePath($src);
		else
		{
			// models page
			$file = Dir::get(Config::get('ROOT_DIR'), true)->getDir(Config::get("XMLPAGES_DIR"))->getFile($src);
			if($file->exists()) return $file;

			// vendor pages
			try{
				return $this->vendorPagePath($src);
			}
			catch(ControllerException $e)
			{throw new ControllerException('page file '.$file->getAbsPath().' not found');}
		}
		
	}
	//}}}

	//{{{ vendorPagePath
	/**
	 * Trying to retrieve full page path in vendor dir only.
	 *
	 * @param string partial src of the page to find
	 * @return string full path to the page from server's root.
	 * @throws ControllerException if unable to find page path
	 */
    protected final function vendorPagePath($src)
    {
		$file = Dir::get(Config::get('ROOT_DIR'), true)->getDir(Config::get("vendors_dir"))->getDir('pages')->getFile($src);

		if($file->exists()) return $file;
		throw new ControllerException('vendor page file '.$src.' not found', 1);
	}
	//}}}

	//{{{ processPage
	/**
	 * It makes all transformation with page's DOM model in order to make
	 * checks, extending and including functions.
	 *
	 * If current page is not allowed to show, Header::FORBIDDEN HTTP code will be returned.
	 *
	 * While extending page, system tries to overload ascending <block>'s with 
	 * blocks on the current page with same names. For example
	 * <pre><code>
	 * Page base.xml:
	 * <root>
	 *		<block id="b1">
	 *			<WText>Hello base.xml</WText>
	 *		</block>
	 *		<WText>Common text</WText>
	 * </root>
	 * 
	 * Page derived.xml:
	 * <root extends="base">
	 *		<block id="b1">
	 *			<WText>Hello derived.xml</WText>
	 *		</block>
	 * </root>
	 * </pre></code>
	 *
	 * Since page derived.xml is extending base.xml, system tries to find base.xml in current controller's 
	 * directory and tries to substitute block "b1" in base.xml with derived.xml "b1". 
	 *
	 * Also, <pre><code> <parent id="b1"/> </code></pre> may be used to include parent's block with id "b1".
	 *
	 * Ascending pages always checking upto ACL
	 *
	 * In case of including, 
	 * <pre><code> 
	 * <include src="base.xml" block="b1" allow="admin"/> 
	 * </code></pre> syntax is used.
	 *
	 * It includes block "b1" (may be optional) from the file "base.xml" and allows it only for group
	 * "admin" (optional too).
	 *
	 * Triggers "BeforePageProcess", "BeforePageExtendsLookup", "BeforePageParentLookup", 
	 * "BeforePageExtending", "BeforePageIncluding","AfterPageProcess" events. 
	 * $this passed as 1st argument, $dom passed as 2nd parameter.
	 *
	 * @param DomDocument object to make transformation
	 * @return DomDocument object that have been transofrmed
	 * @throws ControllerException in case of unrecoverable error
	 * @see ACL::check
	 */
    protected function processPage(DomDocument $dom)
    {
		$this->trigger("BeforePageProcess",array($this,&$dom));

        if(! $dom instanceof DOMNode || !isset($dom->firstChild))
            throw new ControllerException("XML document not valid");
        // check rights
        $a = $dom->firstChild->getAttribute('allow');
        $d = $dom->firstChild->getAttribute('deny');
        if(!ACL::check($a,$d)) Header::error(Header::FORBIDDEN);
        

		$this->trigger("BeforPageExtendsLookup",array($this,&$dom));

        // extends
        $adj_list = array($dom);
        $included_pages = array($this->page.".xml");
        $t_dom = $dom;
        while(($e_src = $t_dom->firstChild->getAttribute('extends')) != "" && !in_array($e_src,$included_pages))
        {
            $t_dom = new DomDocument;
            try { $t_dom->load(($pp = $this->pagePath($e_src))); }
            catch(ControllerException $e) { throw new ControllerException('extends page not found');}
			
            $_a = $t_dom->firstChild->getAttribute('allow');
            $_d = $t_dom->firstChild->getAttribute('deny');
            if(!ACL::check($_a,$_d)) Header::error(Header::FORBIDDEN);
           
            array_unshift($included_pages,$e_src);
            array_unshift($adj_list,$t_dom);
            $this->ie_files[] = $pp;
        }

		$this->trigger("BeforePageParentLookup",array($this,&$dom));

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

		$this->trigger("BeforePageExtending",array($this,&$dom));

        if(count($adj_list) > 1)
            for($adj_i = count($adj_list) - 2; $adj_i >=0; $adj_i--)
            {

                $dom = $adj_list[$adj_i];
                $blocks = t(new DOMXPath($dom))->query("//block[@id]");
                for($i = 0, $c = $blocks->length;$i < $c;$i++)
                {
                    if(($id = $blocks->item($i)->getAttribute("id")) == "") continue;
                    for($j = count($adj_list)-1; $j > $adj_i; $j--)
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

            }

		$this->trigger("BeforePageClearingBlocks",array($this,&$dom));

        // clean up from <block>
        $node_list = $dom->getElementsByTagName('block');
        for($i = 0, $c = $node_list->length; $i < $c; $i++)
        {
			$el = $node_list->item(0);
			if(ACL::check($el->getAttribute('allow'),$el->getAttribute('deny')))
            for($el = $node_list->item(0),$el_cn = $el->childNodes,$j = 0, $c2 = $el_cn->length;$j < $c2;$j++)
                $el->parentNode->insertBefore($el_cn->item($j)->cloneNode(true),$el);
            $el->parentNode->removeChild($el);
        }

		$this->trigger("BeforePageIncluding",array($this,&$dom));

        // include
        $node = $dom->getElementsByTagName("include");
		for($i = 0, $c = $node->length;$i < $c;$i++)
        {
			$el = $node->item(0);
			if($el && ($src = $el->getAttribute('src')) == "") {$el->parentNode->removeChild($el);continue;}

            $_a = $el->getAttribute('allow');
			$_d = $el->getAttribute('deny');
			if(!ACL::check($_a,$_d))  {$el->parentNode->removeChild($el);continue;}

			try{
				$src = $this->pagePath($src, (bool)$el->getAttribute('vendor'));
			}
			catch(ControllerException $e){ 
				throw new ControllerException('include page file '.$src.' not found');
			}
            $d = new DomDocument;
            $d->load($src);

            $_a = $d->firstChild->getAttribute('allow');
            $_d = $d->firstChild->getAttribute('deny');
            if(!ACL::check($_a,$_d)) {$el->parentNode->removeChild($el);continue;}

            if($el && ($block_id = $el->getAttribute("block")) !== "")
            {
                $block = t(new DOMXPath($d))->query("//block[@id='".$block_id."']");
                if(!$block->length) {$el->parentNode->removeChild($el);continue;}
                else
                {
                    $n_d = new DOMDocument('1.0', 'utf-8');
                    $n_el = $n_d->createElement('root');
                    $n_block = $n_d->importNode($block->item(0),true);
                    $n_el->appendChild($n_block);
                    $n_d->appendChild($n_el);
                    $d = $n_d;
                }
            }
            $d = $this->processPage($d);

            $imported_node = $dom->importNode($d->firstChild,true);
            if($imported_node->hasChildNodes())
                for($node_list = $imported_node->childNodes,$j = 0, $c2 = $node_list->length; $j < $c2;$j++)
                    $el->parentNode->insertBefore($node_list->item($j)->cloneNode(true),$el);
            
            $el->parentNode->removeChild($el);
            $this->ie_files[] = $src;
            
        }

		$this->trigger("AfterPageProcess",array($this,&$dom));

        return $dom;
    }
	//}}}
	
	//{{{ parsePageOnGET
	/**
	 * This method creates all necessary objects to make GET request, so to display page.
	 * This is DataSets, Styles, JavaScripts, ValueCheckers objects and widgets directly 
	 * (via {@link buildWidget} function.
	 *
	 * It's called only if current request method is GET.
	 *
	 * Triggers "BeforeParsePageOnGET" and "AfterParsePageOnGET" events.
	 * $this passed as 1st argument, $dom as 2nd argument.
	 *
	 * @param DomDocument object on the base of which all inner components 
	 * would be created.
	 * @return null
	 * @see buildWidget
	 */
	protected function parsePageOnGET(DomDocument $dom)
	{
		$this->trigger("BeforeParsePageOnGET",array($this,&$dom));

		$node = $dom->getElementsByTagName("WPageHandler");
		if($node->length)
		{
			$el = $node->item(0);
			$this->addPageHandler(simplexml_import_dom($el),true);
			$el->parentNode->removeChild($el);
		}

		$node = $dom->getElementsByTagName("WDataSet");
		for($i = 0, $c = $node->length;$i < $c;$i++)
		{
			$el = $node->item(0);
			if(empty($el)) continue;
			$this->addDataSet(simplexml_import_dom($el));
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
		$this->getDispatcher()->notify(new WidgetEvent("all_build_complete"));
		unset($d);

		$this->trigger("AfterParsePageOnGET",array($this,&$dom));

	}
	//}}}
	
	//{{{ parsePageOnPOST
	/**
	 *
	 * This method creates all necessary objects to make POST request.
	 * This is DataHandler, PageHandler objects.
	 *
	 * It's called only if current request method is POST.
	 *
	 * Triggers "BeforeParsePageOnPOST" and "AfterParsePageOnPOST" events.
	 * $this passed as 1st argument, $dom as 2nd argument.
	 *
	 * @param DomDocument object on the base of which all inner components 
	 * would be created.
	 * @return null
	 */
	protected function parsePageOnPOST(DomDocument $dom)
	{
		$this->trigger("BeforeParsePageOnPOST",array($this,&$dom));

		$node = $dom->getElementsByTagName("WDataHandler");
		for($i = 0, $c = $node->length;$i < $c;$i++)
		{
			$el = $node->item(0);
			if(empty($el)) continue;
			$this->addDataHandler(simplexml_import_dom($el));
			$el->parentNode->removeChild($el);
		}
		$node = $dom->getElementsByTagName("WPageHandler");
		if($node->length)
		{
			$el = $node->item(0);
			$this->addPageHandler(simplexml_import_dom($el));
			$el->parentNode->removeChild($el);
		}

		WidgetLoader::load("WForm");
		$node = $dom->getElementsByTagName("WForm");
		for($i = 0, $c = $node->length;$i < $c;$i++)
		{
			$el = $node->item(0);
			if(empty($el)) continue;
			if($el->getAttribute(WForm::no_check_attribute) && ($id = $el->getAttribute("id")))
				$this->no_check_forms[] = $id;
			$el->parentNode->removeChild($el);
		}

		$this->trigger("BeforeParsePageOnPOST",array($this,&$dom));
	}
	//}}}

	//{{{ buildWidget
	/**
	 * This function creates new instance of widget and setups all need environment,
	 * such as javascripts, styles, etc.
	 *
	 * If widget class could not be found it silently exists.
	 *
	 * System widgets are kept in other list, so if widget creates internally, it should be
	 * marked as system.
	 *
	 * Triggers "BeforeBuildWidget" event.
	 * $this passed as 1st argument, $elem as 2nd, $system as 3rd.
	 *
	 * @param SimpleXMLElement object which prepresents widget
	 * @return null
	 */
	function buildWidget(SimpleXMLElement $elem,$system = 0)
	{
		$this->trigger("BeforeBuildWidget",array($this,&$elem,&$system));

		if(($widget_name = WidgetLoader::load($elem->getName())) === false) return;

		$widget = new $widget_name(isset($elem['id'])?(string)$elem['id']:null);
		if(!$widget instanceof WComponent) return;
		
		$w_id = $widget->getID();
		$widget->parseParams($elem);

		if(isset($this->system_widgets[$w_id]) || isset($this->widgets[$w_id])) 
			throw new IdExistsException('Widget with id '.$w_id.' already exists');


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
	//}}}
	
	//{{{ checkACL
	/**
	 * Iternal function that checks rights upon given parsed XML, representing single widget.
	 *
	 * @param SimpleXMLElement parsed part of XML tree, representing single widget
	 * @return bool result of checking ACL rights
	 */
	private function checkACL(SimpleXMLElement $elem)
	{
		$a = $d = null;
        if(isset($elem['allow']))
            $a = (string)$elem['allow'];
        if(isset($elem['deny']))
            $d = (string)$elem['deny'];
        return ACL::check($a,$d);
	}
	//}}}
	
	//{{{ addDataSet
	/** 
	 * Adds DataSet widget to current datasets list. 
	 *
	 * Triggers "BeforeAddDataSet" event.
	 * $this passed as 1st element, $elem as 2nd.
	 *
	 * @param SimpleXMLElement  parsed part of XML tree, representing single dataset.
	 * @return null
	 */
	function addDataSet(SimpleXMLElement $elem)
	{
		$this->trigger("BeforeAddDataSet",array($this,&$elem));

		if(WidgetLoader::load("WDataSet") === false) return;

		if(!$this->checkACL($elem)) return;

		$ds = new WDataSet(isset($elem['id'])?$elem['id']:null);
			
		$ds->parseParams($elem);
		$this->datasets[] = $ds;
	}
	//}}}

	//{{{ addDataHandler
	/** 
	 * Adds DataHandler widget to current datahandlers list. 
	 *
	 * Triggers "BeforeAddDataHandler" event.
	 * $this passed as 1st element, $elem as 2nd.
	 *
	 * @param SimpleXMLElement  parsed part of XML tree, representing single datahandler.
	 * @return null
	 */
	function addDataHandler(SimpleXMLElement $elem)
	{
		$this->trigger("BeforeAddDataHandler",array($this,&$elem));

		if(WidgetLoader::load("WDataHandler") === false) return;

		if(!$this->checkACL($elem)) return;

		$dh = new WDataHandler(isset($elem['id'])?$elem['id']:null);
		$dh->parseParams($elem);
		$this->datahandlers[] = $dh;
	}
	//}}}
	
	//{{{ addStyle
	/**
	 * Adds style to styles list. Each widget could declare 
	 * <code> style="id"</code> construction to use one of
	 * created style. This style are located in this list.
	 *
	 * Triggers "BeforeAddStyle" event. 
	 * $this passed as 1st element, $elem as 2nd.
	 *
	 * @param SimpleXMLElement  parsed part of XML tree, representing single style.
	 * @return null
	 * @throws IdExistsException in case of such style already exists.
	 */ 
	protected function addStyle(SimpleXMLElement $elem)
	{
		$this->trigger("BeforeAddStyle",array($this,&$elem));

		WidgetLoader::load("WStyle");
		if(empty($elem['id'])) return;

		if(!$this->checkACL($elem)) return;

		$s = new WStyle((string)$elem['id']);

		if(isset($this->styles[$s->getId()]))
			throw new IdExistsException('WStyle with id '.$s->getId().' already exists');

		$s->parseParams($elem);
		$this->styles[$s->getId()] = $s;
	}
	//}}}
	
	//{{{ addJS
	/**
	 * Adds js to inner js list. Each widget could declare 
	 * <code>javascript="id"</code> construction to use one of
	 * created script object. 
	 *
	 * Triggers "BeforeAddJS" event.
	 * $this passed as 1st element, $elem as 2nd.
	 *
	 * @param SimpleXMLElement  parsed part of XML tree, representing single js.
	 * @return null
	 * @throws IdExistsException in case of such javascript already exists.
	 */
	protected function addJS(SimpleXMLElement $elem)
	{
		$this->trigger("BeforeAddJS",array($this,&$elem));

		if(($c_name = WidgetLoader::load($elem->getName())) === false) return;

		if(!$this->checkACL($elem)) return;

		$j = new $c_name((string)$elem['id']);

		$j->parseParams($elem);
		
		if(isset($this->javascripts[$j->getId()]))
			throw new IdExistsException('WJavaScript with id '.$j->getId().' already exists');

		$this->javascripts[$j->getId()] = $j;
	}	
	//}}}
	
	//{{{ addPageHandler
	/**
	 * Sets PageHandler for current request. It uses to detect location 
	 * to redirect while doing POST request.
	 *
	 * Triggers "BeforeAddPageHandler" event.
	 * $this passed as 1st element, $elem as 2nd.
	 *
	 * @param SimpleXMLElement  parsed part of XML tree, representing pagehandler.
	 * @param bool defines, true if this method was called while GET request, false if it was POST
	 * @return null
	 */
	protected function addPageHandler(SimpleXMLElement $elem, $is_get = false)
	{
		$this->trigger("BeforeAddPageHandler",array($this,&$elem));

		if(WidgetLoader::load("WPageHandler") === false) return;

		if(!$this->checkACL($elem)) return;


        $this->pagehandler = new WPageHandler();
		$this->pagehandler->parseParams($elem);
		if($is_get)
			$this->pagehandler->storeSteps();
		else
			$this->pagehandler->restoreSteps();

	}
	//}}}
	
	//{{{ addValueChecker
	/**
	 * Adds valuechecker to inner list. Each widget could declare 
	 * <code>valuechecker="id"</code> construction to use one of
	 * created objects. It's handy method to do both client-side
	 * and server-side data checks.
	 *
	 * Triggers "BeforeAddValueChecker" event.
	 * $this passed as 1st element, $elem as 2nd.
	 *
	 * @param SimpleXMLElement  parsed part of XML tree, representing single js.
	 * @return null
	 * @throws IdExistsException in case of such valuechecker already exists.
	 */
	protected function addValueChecker(SimpleXMLElement $elem)
	{
		$this->trigger("BeforeAddValueChecker",array($this,&$elem));

		if(!isset($elem['id'])) return;

		if(!$this->checkACL($elem)) return;


		if(WidgetLoader::load("WValueChecker") === false) return;
		$vc = new WValueChecker((string) $elem['id']);

		if(isset($this->valuecheckers[$vc->getId()]))
			throw new IdExistsException('WValueChecker with id '.$vc->getId().' already exists');

		$vc->parseParams($elem);
		$this->valuecheckers[$vc->getId()] = $vc;
	}	
	//}}}

	//{{{ getValueChecker
	/**
	 * Returns valuechecker with given id.
	 *
	 * @param string id of valuechecker to return
	 * @return WValueChecker asked valuechecker object
	 */
	function getValueChecker($id)
	{
		if(isset($id) && isset($this->valuecheckers[$id]))
			return $this->valuecheckers[$id];
		return null;
	}
	//}}}

	//{{{ getWidget
	/**
	 * Returns widget with given id.
	 *
	 * Both common and system widget lists are checked.
	 *
	 * @param string id of widget to return
	 * @return WComponent requested object or null if nothing was found.
	 */
	function getWidget($id)
	{
		if(isset($this->widgets[$id]))
			return $this->widgets[$id];
		elseif(isset($this->system_widgets[$id]))
			return $this->system_widgets[$id];
		else return null;
	}
	//}}}

	//{{{ allHTML
	/**
	 * Returns html representation of current page. 
	 * 
	 * First of all it loads delayed datasets to manage data that depends of 
	 * final widgets states.
	 * Then {@WComponent::preRender} method and {@WComponent::messageInterchange} method
	 * are called upon all widgets.
	 *
	 * At final, all widgets generates HTML and system triggers {@WComponent::postRender} method.
	 *
	 * Triggers "BeforeAllHTML" and "AfterAllHTML" events.
	 * $this passed as 1st argument.
	 *
	 * @param null
	 * @return string HTML representation
	 */ 
	function allHTML()
	{
		$final_html = "";
		$this->trigger("BeforeAllHTML",$this);

		foreach($this->datasets as $d)
			$d->loadDelayed();

		if(!is_array($this->widgets)) return "";

		foreach($this->widgets as $name=>$widget)
		{
			if(!$widget->getState() || !$widget instanceof WComponent) continue;
			$this->widgets[$name]->preRender();
        }
		foreach($this->widgets as $name=>$widget)
		{
			if(!$widget->getState() || !$widget instanceof WComponent) continue;
			$widget->messageInterchange();
		}
        foreach($this->widgets as $name => $widget)
        {
            if(!$widget->getState() || !$widget instanceof WComponent) continue;
			$final_html .= $this->widgets[$name]->generateHTML();
        }
		foreach($this->widgets as $name=>$widget)
		{
			if(!$widget->getState() || !$widget instanceof WComponent) continue;
			$this->widgets[$name]->postRender();				
        }

        $final_html.= $this->processNotifications();

		$this->trigger("AfterAllHTML",$this);

		return $final_html;
	}
	//}}}

	//{{{ head
	/**
	 * Returns or echo's head of HTML document: from doctype to body tag.
	 *
	 * If response_string {@link setResponseString} is setted, this content
	 * will be echo'd or returned.
	 *
	 * It adds css and js to {@Header} in order to their priority.
	 * Depending of argument it echo's result string to output or 
	 * returns it as string.
	 *
	 *
	 * Triggers "BeforeHead" event with $this as 1st argument. 
	 * And "AfterHead" event with $this as 1st argument and 
	 * result string as 2nd.
	 *
	 * @param bool echo to output or return as string
	 * @return mixed this could be either null or string, depending on argument value.
	 * @see setResponseString
	 * @see getHeadBodyTail
	 * @see tail
	 * @see addScript
	 * @see addCSS
	 */
	function head($echo = 1)
	{
		$this->trigger("BeforeHead",$this);
	
        if(isset($this->response_string))
            if ($echo) echo $this->response_string;
            else return $this->response_string;

		usort($this->scripts,create_function('$a,$b',
			'if($a["priority"] == $b["priority"]) return $a["ind"]-$b["ind"];
			 else return $a["priority"] - $b["priority"];'));

		foreach($this->scripts as $v)
			$this->header->addScript($v['src'],$v['cond']);

		usort($this->css,create_function('$a,$b',
			'if($a["priority"] == $b["priority"]) return $a["ind"]-$b["ind"];
			 else return $a["priority"] - $b["priority"];'));

		foreach($this->css as $v)
			$this->header->addCSS($v['src'],$v['cond'],$v['media']);
		$v = $this->header->send();
		$v .= "<body>\n";

		$this->trigger("AfterHead",array($this,&$v));

		if($echo)
			echo $v;
		else return $v;
	}
	//}}}
	
	//{{{ tail
	/**
	 * Return enclosing tags of HTML document
	 *
	 * If response_string {@link setResponseString} is setted nothing will be returned or echo'd
	 *
	 * Triggers "BeforeTail" event with $this as 1st argument.
	 *
	 * @param bool echo to output or return as string
	 * @return mixed this could be either null or string, depending on argument value.
	 * @see head
	 * @see getHeadBodyTail
	 * @see setResponseString
	 */
	function tail($echo = 1)
	{
		$this->trigger("BeforeTail",$this);

        if(isset($this->response_string)) return;

        $v = "\n</body></html>";

		$this->trigger("AfterTail",array($this,&$v));

		if($echo)
			echo $v;
		else return $v; 
	}
	//}}}

	//{{{ setResponseString
	/**
	 * Set response string directly. If setted outputed instead of 
	 * standard allHTML-mechanism.
	 *
	 * Set it to output non-html data, or data for AJAX.
	 *
	 * @param string string to be outputed
	 * @return null
	 * @see head
	 * @see tail
	 * @see getHeadBodyTail
	 */
    function setResponseString($str)
    {
        if(!isset($str) || !is_scalar($str)) return ;
        $this->response_string = $str;
	}
	//}}}

	//{{{ getHeadBodyTail
	/**
	 * Returns or echo's head, body and tail of HTML document: from doctype to 
	 * end body tag. It consist of sequential call of {@link allHTML}, 
	 * {@link head} and {@link tail} methods.
	 *
	 * If response_string {@link setResponseString} is setted, this content
	 * will be echo'd or returned.
	 *
	 * Preferred to use this method instead of head(), tail() and allHTML() 
	 * due to not obviuos call sequence. First of all  allHTML() called to 
	 * allow user-code to call some Header functions. Then head() and
	 * tail() method called.
	 *
	 * Triggers "BeforeHeadBodyTail" event with $this as 1st argument. 
	 * "AfterHeadBodyTailResponce" if response string outputed 
	 * with $this as the first argument, and response_string as the second.
	 * "AfterHeadBodyTailRegular" on regular output with such
	 * arguments: $this, $head string, $body string, and $tail string.
	 *
	 * @param bool defines whenever to echo or return content directly.
	 * @return mixed null or string depending on argument.
	 */
	function getHeadBodyTail($echo = 1)
	{
		$this->trigger("BeforeHeadBodyTail",$this);
		
        if(isset($this->response_string))
		{
			$this->trigger("AfterHeadBodyTailResponce",array($this,&$this->response_string));
            if ($echo) echo $this->response_string;
			else return $this->response_string;
		}
        else
        {
            $body = $this->allHTML();
            $head = $this->head(0);
            $tail = $this->tail(0);
			$this->trigger("AfterHeadBodyTailRegular",array($this,&$head,&$body,&$tail));
            if ($echo) echo $head,$body,$tail;
            else return ($head.$body.$tail);
		}
	}
	//}}}

	//{{{ getDispatcher
	/**
	 * Return dispatcher used for routing widget's events.
	 *
	 * Primarily used by widgets. Not for external use.
	 * 
	 * @param null
	 * @return WidgetEventDispatcher object. It must be single for request.
	 */
	function getDispatcher()
	{
		return $this->dispatcher;
	}
	//}}}
	
	//{{{ getAdjacencyList
	/**
	 * Returns widgets adjacency list.
	 *
	 * Primarily used by internal function. Not for external use.
	 *
	 * @param null
	 * @return WidgetAdjacencyList object. It must be single for request.
	 */
	function getAdjacencyList() 
	{
		return $this->adjacency_list;
	}
	//}}}
	
	//{{{ getStyleByName
	/**
	 * Returns style object by given id.
	 *
	 * @param string id of style object.
	 * @return mixed WStyle object or null if nothing was found.
	 */
	function getStyleByName($name = null)
	{
		if(!isset($name) ||empty($this->styles["".$name]))
			return null;
		return $this->styles["".$name];
	}
	//}}}
	
	//{{{ getJavaScriptByName
	/**
	 * Returns javascript object by given id.
	 *
	 * @param string id of javascript object.
	 * @return mixed WJavaScript object or null if nothing was found.
	 */
	function getJavaScriptByName($name = null)
	{
		if(!isset($name) || empty($this->javascripts["".$name]))
			return null;
		return $this->javascripts["".$name];
	}
	//}}}

	//{{{ addScript
	/**
	 * Add script to be included to the head part of HTML document.
	 * Additionally, condition parameter may be setted. In this case
	 * link to js file will be wrapped into conditional comment. For example
	 * 
	 * <pre><code>
	 * $controller->addScript("ie_fix.js","lt IE 7", 20);
	 * </code></pre>
	 *
	 * will produce
	 * <pre><code>
	 * <!--[if IE 7]>
	 * <script src="/0.1/ie_fix.js" type="text/javascript"></script>
	 * <![endif]-->
	 * </code></pre>
	 *
	 * Priority defines relative position in <head> section.
	 * Scripts with higher priority will be positioned later than scripts
	 * with lower priority.
	 *
	 * Scripts with equal priorities positioned in order of method invocation.
	 *
	 * Triggers "BeforeAddScript" event with $this as 1st argument,
	 * $src as 2nd, $cond as 3rd, $priority as 4th.
	 *
	 * @param string name of js to load
	 * @param string conditional comment to wrap the script tag. Default is null i.e.
	 * no conditional comment is used.
	 * @param numeric position priority. Default is 10.
	 * @see addCSS
	 */
	function addScript($src = null,$cond = null,$priority=10)
	{
		$this->trigger("BeforeAddScript",array($this,&$src,&$cond,&$priority));

		if(empty($src)) return;
		$priority = (int)$priority;
		if($priority < 1) $priority=10;

		//if(in_array($src,$this->scripts))return;
		$this->scripts[] = array('src'=>
			strpos($src,"http://") === false?
			"/".Config::get("JS_VER")."/".ltrim($src,"/"):$src,'cond'=>$cond,'priority'=>$priority,"ind"=>count($this->scripts));
	}
	//}}}
	
	//{{{ addCSS
	/**
	 * Similar to {@link addScript}, adds link to css file to the head part 
	 * of HTML document.
	 * Additionally, condition parameter may be setted. In this case
	 * link to css  will be wrapped into conditional comment. For example
	 * 
	 * <pre><code>
	 * $controller->addCSS("ie.css","IE", 20, "screen, projection");
	 * </code></pre>
	 *
	 * will produce
	 * <pre><code>
	 * <!--[if IE]>
	 * <link  href="/0.1/ie.css" type="text/css" rel="stylesheet" media="screen, projection"/>
	 * <![endif]-->
	 * </code></pre>
	 *
	 * Priority defines relative position in <head> section.
	 * Links with higher priority will be positioned later than links
	 * with lower priority.
	 *
	 * Links with equal priorities positioned in order of method invocation.
	 *
	 * Triggers "BeforeAddCSS" event with $this as 1st argument,
	 * $src as 2nd, $cond as 3rd, $media as 4th and $priority as 5th.
	 *
	 * @param string name of css file to load
	 * @param string conditional comment to wrap the link tag. Default is null, i.e. no 
	 * conditional comments is used.
	 * @param string media. Default is null i.e. no media is specified
	 * @param numeric position priority. Default is 10.
	 * @see addScript
	 */
	function addCSS($src = null,$cond = null,$priority = 10,$media = null)
	{
		$this->trigger("BeforeAddCSS",array($this,&$src,&$cond, &$priority,&$media));

		if(empty($src)) return;

		$priority = (int)$priority;
		if($priority < 1) $priority=10;

		//if(in_array($src,$this->css)) return;
		$this->css[] = array('src'=>
			strpos($src,"http://") === false?
			"/".Config::get("CSS_VER")."/".ltrim($src,"/"):$src,'cond'=>$cond, 'media'=>$media,'priority'=>$priority,"ind"=>count($this->css));
	}
	//}}}
	
	//{{{ getNavigator
	/**
	 * Return instance of page navigator, used in the system.
	 * Primarily for internal usage.
	 *
	 * @param null
	 * @return Navigator object.
	 */
	function getNavigator()
	{
		return $this->navigator;
	}
	//}}}
	
	//{{{ makeURL
	/**
	 * Used for easy way of creating various URLs, inside the system and userland models.
	 * It hide the kitchen of building user-friendly URL from developer and 
	 * simply operates with base definitions, such as "p1" or "p2".
	 *
	 * @param scalar p1 parameter. It could be null, then current p1 parameter will be taken.
	 * For example, if we are at URL <code>http://example.com/blog/1.html</code> and call
	 * <code>$controller->makeURL("2.html");</code>, URL <code>http://example.com/blog/2.html</code>
	 * will be returned. 
	 * In case if <code>$controller->makeURL()</code>, URL <code>http://example.com/blog/</code> 
	 * will be returned.
	 *
	 * @param array p2 parameters. It could be assoc or int-based.
	 * In case of assoc, method will be looking for values in p2 that equals 
	 * to keys of parameter's array.
	 * For example, if URL is <code>http://example.com/blog/dir1/dir2/dir3/2.html</code>
	 * and <code>$controller->makeURL(null,array("dir2"=>"dir0"));</code> is called,
	 * <code>http://example.com/blog/dir1/dir0/dir3/2.html</code> will be returned.
	 * 
	 * To unset some of "p2" parameters use null as a value:
	 * <code>$controller->makeURL(null,array("dir2"=>null));</code> will return
	 * <code></code>.
	 *
	 * Also, key of passing "p2" parameter may starts from "/", that indicates of using 
	 * regular expression to find thing to substitute. For example:
	 * <code>$controller->makeURL(null,array("/dir(\d)/"=>"d\\1"));</code>
	 * will return <code>http://example.com/blog/d1/d2/d3/2.html</code>. Null value
	 * may be used also to unset.
	 *
	 * If no keys was found new value will be added to "p2" list.
	 *
	 * To receive access to p2 variables by their position order, use int-based array.
	 * For example <code>$controller->makeURL(null,array(0=>null));</code> will 
	 * unset 0 parameter. As a result <code>http://example.com/blog/dir2/dir3/2.html</code>
	 * will be returned. 
	 * Similar, use this method to replace or add parameter to "p2":
	 * <code>$controller->makeURL(null, array(1=>"dir0"));</code> will return
	 * <code>http://example.com/blog/dir1/dir0/dir3/2.html</code>.
	 *
	 * @param string new controller name. It could be null, then current controller name
	 * will be taken.
	 * To change controller name in the hyperlink, use $controller_name parameter. E.g.
	 * <code>$controller->makeURL(null,null,'new_blog');</code>
	 * will return 
	 * <code>http://example.com/new_blog/dir1/dir2/di3/2.html</code>
	 *
	 * @param array $_GET variables. This parameter could be null, so current 
	 * $_GET parameters will stay in resulting URL link.
	 * 
	 * if you want to change $_GET parameters (following after ? in the URL), 
	 * pass assoc array to $get. It works similar to assoc arrays in case of "p2" parameters, 
	 * but without regular expressions on keys. Keep in mind, that system parameters (i.e. that
	 * started with "__") are filtered and wont be presented in final URL link. 
	 * Null values caused to unset specified key.
	 *
	 * So, <code>$controller->makeURL(null,null,null,array('get_key'=>'get_value','unset_me'=>null))</code>
	 * on URL <code>http://example.com/blog/dir1/dir3/2.html?unset_me=yep</code>
	 * will return 
	 * <code>http://example.com/blog/dir1/dir3/2.html?get_key=get_value</code>.
	 *
	 * In order to specify language, that should be used on the page use the last parameter $lang 
	 * by passing 2 letter-name of the language.
	 * If this language is not default, it will be presented in the URL link.
	 *
	 * @see Language
	 */
	function makeURL($p1 = null, $p2 = null,$controller_name = null, $get = null, $lang = null)
	{
		if(!isset($controller_name) || !is_scalar($controller_name) || strlen($controller_name) < 3)
			$controller_name = $this->controller_name;

		if(!isset($p1) || !is_scalar($p1))
			if($this->p1 !== "index")
				$p1 = $this->p1;
			else $p1 = null;

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
						if($v === null)
							unset($n_p2[$c_p2[$k]]);
						else
							$n_p2[$c_p2[$k]] = $v;
					elseif(substr($k,0,1) == "/")
					{
						$replaced = false;
						foreach($this->p2 as $temp_k_p2 => $temp_v_p2)
							if(preg_match($k,$temp_v_p2))
								if($v === null)
									{ unset($n_p2[$temp_k_p2]); $replaced = true; }
								else 
									{ $n_p2[$temp_k_p2] = preg_replace($k,$v,$temp_v_p2); $replaced = true; }
						if(!empty($v) && !$replaced)
							$n_p2[] = $v;
					}
					elseif(!empty($v))
						$n_p2[] = $v;
			}
			else
				foreach($p2 as $k=>$v)
					if($v == null)
						unset($n_p2[(int)$k]);
					else
						$n_p2[(int)$k] = $v;
		}

		$n_p2 = array_values(array_map('rawurlencode',array_filter($n_p2)));

		$c_get = $n_get = $this->get->getAllChecked();

		foreach($n_get as $k=>$v)
			if(substr($k,0,2) == "__") unset($n_get[$k]);

		if(isset($get) && is_array($get))
			foreach($get as $k=>$v)
			{
				if(substr($k,0,2) == "__") continue;
				if(isset($c_get[$k]))
					if($v === null)
					{ unset($n_get[$k]); continue; }
				$n_get[$k] = $v;
			}
		
		if($controller_name == "index" && empty($n_p2))
			$controller_name = null;

		$n_get2 = array();
		foreach($n_get as $k=>$v)
			$n_get2[] = rawurlencode($k)."=".rawurlencode($v);
		unset($n_get);

		if(is_null($lang))
		{
			if(!Language::isDefault())
				$lang = Language::currentName();
		}
		elseif(is_string($lang))
		{
			$t = Language::getLangList(true);
			$lang_id = isset($t[$lang])?$t[$lang]:null;
			if(Language::isDefault($lang_id))
				$lang = null;
		}
		elseif(is_numeric($lang))
		{
			if(Language::isDefault($lang))
				$lang = null;
			else $lang = Language::getLangName($lang);
		}

		
		return Header::makeHTTPHost(). 
			((!empty($lang))?"/".$lang:"").
            ((!empty($controller_name))?"/".$controller_name:"")."/".
			(!empty($n_p2)?implode("/",$n_p2)."/":"").(!empty($p1) && strpos($p1,".") === false?$p1.".html":$p1).
			(!empty($n_get2)?"?".implode("&",$n_get2):"");
    }
	//}}}

	//{{{ getDisplayModeParams
	/**
	 * Returns object with information of current display mode parameters.
	 *
	 * Primarily used by internal functions.
	 *
	 * @param null
	 * @return DisplaModeParams object
	 * @see DisplayModeParams
	 */
	function getDisplayModeParams()
	{
		return $this->display_mode_params;
	}
	//}}}
	
	//{{{ getPage
	/**
	 * Returns name of the current page. 
	 *
	 * It could be either string or numeric, depending
	 * of current URL formatting strategy.
	 *
	 * I.e. in case <code>http://example.com/2.html<code>
	 * numeric "2" will be returned.
	 *
	 * @param null
	 * @return mixed current page
	 * @see Controller::makeURL
	 */
	function getPage()
	{
		return $this->page;
	}
	//}}}
	
	//{{{ getControllerName
	/**
	 * Returns name of the current controller.
	 *
	 * It always presented, and if 
	 * <code>http://example.com/2.html</code> URL is used, 
	 * this method returns "index" as a result.
	 *
	 * @param null
	 * @return string alphanumeric string with current controller name
	 */
	function getControllerName()
	{
		return $this->controller_name;
	}
	//}}}
	
	//{{{ XMLPageChanged
	/**
	 * Checks if current page changed since the specified time.
	 *
	 * Primarily used by internal functions.
	 *
	 * @param int time to check
	 * @return bool result of checking
	 */
	function XMLPageChanged($mtime)
	{
		if(!isset($mtime)) return true;
		$file = Config::get('ROOT_DIR').Config::get("XMLPAGES_DIR")."/".$this->controller_name."/".$this->page.".xml";
        if(fileChanged($file,$mtime)) return true;
        foreach($this->ie_files as $f)
            if(fileChanged($f,$mtime)) return true;
        return false;
	}
	//}}}

	//{{{ handlePOST
	/**
	 * Process incoming POST data. 
	 * This happens only if current request method is POST and it not empty.
	 *
	 * It consists of sequence of checks and calls:
	 *
	 * <ol>
	 * <li>"BeforeHandlePOST" event is called with $this parameter.</li>
	 * <li>It decides whenever form should be checked upon trusted signatures.</li>
	 * <li>If the form should be checked, "BeforeCheckSignature" event is called with
	 * $this parameter.</li>
	 * <li>Checks incoming form signature to detect POST data from the form, that user didn't visit.</li>
	 * <li>"BeforeCheckByRules" event is called with $post as a first parameter and real 
	 * complete form signature value, that has been passed by the client's UA.
	 * <li>Checks incoming form data with pointed value checkers. And if error occurred, 
	 * it shows error message. No data will be passed to declared data handlers.</li>
	 * <li>"BeforeCallHandlers" event is called with $this and $formid (just id, no signature field).</li>
	 * <li>All registered checkers are called to perform form data checks in userland.
	 * If CheckerException is raised, error message will be shown without calling declared 
	 * handlers.</li>
	 * <li>Declared data handlers and finalizers are called.</li>
	 * <li>"AfterHandlePOST" event is called with $this parameter and $ret string, which points
	 * where to redirect after data processing.</li>
	 *
	 * @param null
	 * @return null
	 * @see DataHandlerObject
	 * @see PageHandlerObject
	 */
	protected function handlePOST()
    {
		$this->trigger("BeforeHandlePOST",$this);

		if($this->post->isEmpty()) 
			Header::redirect(requestURI(true), Header::SEE_OTHER); 

		WidgetLoader::load("WForm");
		list($formid) = explode(":",$this->post->{WForm::signature_name});
		if(empty($formid))
			Header::redirect(requestURI(true), Header::SEE_OTHER); 


		if(!in_array($formid,$this->no_check_forms))
		{
			$this->trigger("BeforeCheckSignature",$this);
			if(!$this->checkSignature($this->post->{WForm::signature_name}))
				Header::redirect(requestURI(true), Header::SEE_OTHER); 

			POSTErrors::flushErrors();

			$this->trigger("BeforeCheckByRules",array(&$this->post,$this->post->{WForm::signature_name}));

			POSTChecker::checkByRules($this->post->{WForm::signature_name},$this->checker_rules,$this->checker_messages);
			POSTChecker::checkFiles(  $this->post->{WForm::signature_name},$this->file_rules,$this->checker_messages);
			
			if(POSTErrors::hasErrors())
			{
				POSTErrors::saveErrorList();
				Header::redirect(requestURI(true), Header::SEE_OTHER); 
			}
			//DataUpdaterPool::restorePool();
		}
		$this->trigger("BeforeCallHandlers",array($this,&$formid));

		try
		{
			DataUpdaterPool::callCheckers($formid);
		}
		catch(CheckerException $e)
		{
			POSTErrors::addError($e->getWidgetName(),$e->getAdditionalId(),$e->getMessage());
		}
		if(POSTErrors::hasErrors())
		{
			POSTErrors::saveErrorList();
			Header::redirect(requestURI(true), Header::SEE_OTHER); 
		}
		DataUpdaterPool::callHandlers($formid);
		DataUpdaterPool::callFinalize($formid);
        $ret = null;
        if(isset($this->pagehandler))
            $ret = $this->pagehandler->handle();

		$this->trigger("AfterHandlePOST",array($this,&$ret));

        if(is_numeric($ret))
            $this->gotoLocation($this->navigator->getStepURL($ret));
        elseif(is_string($ret))
            $this->gotoLocation($ret);

		//$this->gotoStep_0();
        Header::redirect(requestURI(true), Header::SEE_OTHER); 
		exit();
	}
	//}}}
	
	//{{{ gotoStep_0
	/**
	 * Redirects to the current URL and halts script execution.
	 * Primarily for internal use.
	 *
	 * @param null
	 * @return null
	 */
	protected function gotoStep_0()
	{
		//TODO: review mb delete
		//deprecated
		$s = $this->navigator->getStep(0);
        Header::redirect( isset($s,$s['url'])?$s['url']:"/");
		exit();
    }
	//}}}
	
	//{{{ gotoLocation
	/**
	 * Redirects to specified location or page.
	 *
	 * @param string location, ie "/blog/2.html" or page "2.html". In the last case, all 
	 * current parameters will be saved and only page will be changed.
	 * return null
	 */
    protected function gotoLocation($loc)
    {
		if(!isset($loc)) exit();
		if(($pos = strpos($loc,"/")) === false) 
			$loc = $this->makeURL($loc);//suggest $loc is a page to which redirect to.
		elseif($pos == 0)
			$loc = Header::makeHTTPHost().$loc;
		Header::redirect($loc, Header::SEE_OTHER);
    }
	//}}}
	
	// checkers

	//{{{ setChecker
	/**
	 * Set checker rules for html control with specified name.
	 * Used by POSTChecker and should be placed in storage. It will be 
	 * restored during POST request. 
	 * Primarily for internal use by WValueChecker class.
	 *
	 * @param string complete form signature, that will come via POST
	 * @param WControl object of HTML control, which should be checked upon the rules. WControl because
	 * widget should have getName() method.
	 * @param string name of the rule to apply ("required", "min", "max" etc)
	 * @param string value for rule. May be empty if rule doesn't need value (such as required).
	 * @param string optional message to be displayed, if default doesn't feet for particular needs.
	 */
	function setChecker($form_sig,WControl $widget,$rule,$rule_value, $message = null)
    {
		if(!isset($form_sig,$widget,$rule,$rule_value)) return;
		if($widget instanceof iFileUploader)
			$this->file_rules[$form_sig][$widget->getName()][$rule] = trim($rule_value);
		else
			$this->checker_rules[$form_sig][$widget->getName()][$rule] = trim($rule_value);
        if (!is_null($message)) $this->checker_messages[$form_sig][$widget->getName()] = $message;
	}
	//}}}

	//{{{ restoreCheckers
	/**
	 * Restore checkers, setted and saved during GET request by {@link setChecker} method.
	 * Used only while POST request when system must check incoming data upon the rules.
	 * All checkers are saved in persistent {@link Storage}.
	 *
	 * Primarily for internal use by Controller class.
	 *
	 * @param null
	 * @return null
	 */
	protected function restoreCheckers()
	{
		$storage = Storage::createWithSession("controller".$this->getStoragePostfix());
		$_cr = $storage['checker_rules'];
		if(!empty($_cr) && is_array($_cr))
			$this->checker_rules = $_cr;
		unset($_cr);
		$_cm = $storage['checker_messages'];
		if(!empty($_cm) && is_array($_cm))
			$this->checker_messages = $_cm;
		unset($_cm);
		$_fr = $storage['file_rules'];
		if(!empty($_fr) && is_array($_fr))
			$this->file_rules = $_fr;
		unset($_fr);

	}
	//}}}

	// signatures

	//{{{ addSignature
	/**
	 * Adds form signature of non-anonymous user to the list of valid signatures. 
	 * While POST request incoming form singnature will be checked upon this list.
	 * It gives some defense from CSRF attacks.
	 *
	 * The list made as fixed length queue. Maximum number of elements defines with MAX_SIGNATURES 
	 * constant. Default is 32.
	 *
	 * @param string signature to be added
	 * @return null
	 */
	function addSignature($sig)
	{
		if(!isset($sig)) return;
		if(count($this->form_signatures) >= self::MAX_SIGNATURES)
		{
			$first_sig = array_shift($this->form_signatures);
			if(array_key_exists($first_sig,$this->checker_rules))
				unset($this->checker_rules[$first_sig]);
			if(array_key_exists($first_sig,$this->checker_messages))
				unset($this->checker_messages[$first_sig]);
		}
		$this->form_signatures[] = $sig;
	}
	//}}}

	//{{{ checkSignature
	/**
	 * Checks whenever given signature is valid, so located in list.
	 * If so, it will be removed and true value returned.
	 * 
	 * @param string signature to be checked
	 * @return bool if signature is valid
	 */
	protected function checkSignature($sig = null)
	{
		if(!isset($sig)) return false;
		if(($k = array_search($sig,$this->form_signatures)) !== false)
		{
			unset($this->form_signatures[$k]);
			$this->form_signatures = array_values($this->form_signatures);
			return true;
		}
		return false;
	}
	//}}}
	
	//{{{ restoreSignatures
	/**
	 * Restore signatures, setted and saved during GET request by {@link addSignature} method.
	 * Used only while POST request of non-anonymous user when system must check incoming signature.
	 * All checkers are saved in persistent {@link Storage}.
	 *
	 * Primarily for internal use by Controller class.
	 */
	protected function restoreSignatures()
	{
		$storage = Storage::createWithSession("controller");
		$this->form_signatures = $storage->get('signatures');

		if(!is_array($this->form_signatures))
			$this->form_signatures = array();
	}
	//}}}

	// TODO: move to vendors and plugins
    public function addNotify($text){
        if (!is_object($this->notifyStorage)) $this->notifyStorage = Storage::createWithSession('ControllerNotify');
        $notes = $this->notifyStorage['notify'];
        $notes[] = Language::encodePair($text);
        $this->notifyStorage['notify'] = $notes;
    }
    
    private function processNotifications(){
		$this->trigger("BeforeProcessNotifications",$this);

        if (!is_object($this->notifyStorage)) $this->notifyStorage = Storage::createWithSession('ControllerNotify');
        $notes = $this->notifyStorage['notify'];
        if (!is_array($notes) || empty($notes)) return '';

        $this->addScript("jquery.jgrowl.js");
        $this->addCSS("jquery.jgrowl.css");

        $tpl = new Template(Config::get('ROOT_DIR').'/includes/widgets/templates', 'notify.tpl');
        $tpl->setParamsArray(array('list' => $notes));
        unset($this->notifyStorage['notify']);
        return $tpl->getHTML();
    }

	//{{{ isAjax
	/**
	 * Need to detect whenever current request was made via AJAX
	 *
	 * @param null
	 * @return bool true if request made via AJAX
	 */
    function isAjax()
    {
        return $this->is_ajax;
	}
	//}}}


	// destructor
	//{{{ __destruct
	/**
	 * Managed some routine functions such as saving signatures, rules, 
	 * closes DB connection. Some methods are called only if current request was GET,
	 * so inited flag was setted.
	 *
	 * @param null
	 * @return null
	 */
	function __destruct()
    {
		$this->trigger("BeforeDestruct",$this);
        //do it only if init was completed
        if($this->inited)
		{
			$this->trigger("DestructInited",$this);

		    $storage = Storage::createWithSession("controller");
			$storage->set('signatures',$this->form_signatures);
		    $storage = Storage::createWithSession("controller".$this->getStoragePostfix());
		    $storage->set('checker_rules',$this->checker_rules);
		    $storage->set('file_rules',$this->file_rules);
		    $storage->set('checker_messages',$this->checker_messages);
            POSTErrors::flushErrors();
        }

		$this->trigger("Destruct",$this);

		DB::close();
	}
	//}}}
	
	//{{{ setStoragePostfix
	/**
	 * It sets postifx, that will be added to the storage name to store
	 * all internal data and structures. In some cases we should protect current 
	 * internal structures and set new. For example, while filling form and markdown 
	 * text field we want to insert picture via editor's browser. We need to save
	 * page's data but we also need to store data, used by the browser. Different 
	 * postfixes will solve this problem. 
	 *
	 * By default postfix equals to the current controller name
	 *
	 * @param string new postfix string
	 * @return null
	 */
	function setStoragePostfix($name)
	{
		if(!isset($name) || !is_string($name)) return ;
		$this->storage_postfix = (string)$name;
	}
	//}}}
	
	//{{{ getStoragePostfix
	/** 
	 * Returns currently used postfix string
	 *
	 * @param null
	 * @return string
	 * @see setStoragePostfix
	 */
	function getStoragePostfix()
	{
		return $this->storage_postfix;
	}
	//}}}
	
	//{{{ registerStep
	/**
	 * Sets flag that defines whenever to set or not current page to the
	 * {@link Navigator}.
	 *
	 * @param bool flag
	 * @return null
	 */
	function registerStep($reg = 1)
	{
		if(!isset($reg) || !is_scalar($reg)) return;

		$this->register_step = 0 + $reg;
	}
	//}}}
}
//}}}

//{{{ DisplayModeParams
/**
 * Holds params for displaying various widgets. 
 * Currently, only iterable containers use this class.
 */
class DisplayModeParams
{
	protected 
		/**
		 * Params for displaying.
		 * @var array
		 */
        $widget_params = array(),
		/**
		 * Index of selector that have been matched.
		 * @var int
		 */
        $matched_index = null
        ;
	public 
		/**
		 * Sets predicted limit for displaying iterable containers.
		 */
		$predicted_from = null,
		$predicted_limit = null
		;
		

	//{{{ set
	/**
	 * Set parameters for display for given widget.
	 * Primarily used by internal functions
	 *
	 * @param string id of widget
	 * @param int index of the first element of displayed container
	 * @param int number of items to show
	 * @param int total count of items that container holds
	 * @return null
	 */
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
	//}}}

	//{{{ getFrom
	/**
	 * Returns from component of parameters.
	 * 
	 * @param string id of widget to be looked up
	 * @return int 
	 */
	function getFrom($widget_id)
	{
		return !isset($this->widget_params[$widget_id])?$this->widget_params[$widget_id]['from']:0;
	}
	//}}}

	//{{{ getLimit
	/**
	 * Returns limit component of holding parameters. 
	 * It computes  considering count parameter.
	 *
	 * @param string id of widget to be looked up
	 * @return int
	 */
	function getLimit($widget_id)
	{
		if(!isset($this->widget_params[$widget_id])) return 0;
		if($this->widget_params[$widget_id]['from'] + $this->widget_params[$widget_id]['limit'] > $this->widget_params[$widget_id]['count'])
			return $this->widget_params[$widget_id]['count'] - $this->widget_params[$widget_id]['from'];
		return $this->widget_params[$widget_id]['limit'];
	}
	//}}}

	//{{{ getCurrent
	/**
	 * Returns index of currently processing widget.
	 *
	 * @param id of widget to be looked up
	 * @param string scope of index. Might be 'local' or 'global'
	 * @return int
	 */
	function getCurrent($widget_id,$scope)
	{
		if(!isset($this->widget_params[$widget_id])) return;
		if($scope == "global")
			return $this->widget_params[$widget_id]['current'];
		else
			return $this->widget_params[$widget_id]['current'] - $this->widget_params[$widget_id]['from'];
	}
	//}}}

	//{{{ incCurrent
	/**
	 * Increments current index for given widget
	 *
	 * @param string id of widget to be looked up
	 * @return null
	 */
	function incCurrent($widget_id)
	{
		if(!isset($this->widget_params[$widget_id])) return;
		if($this->widget_params[$widget_id]['current'] - $this->widget_params[$widget_id]['from']+1 > 
			$this->widget_params[$widget_id]['limit']) return;

		$this->widget_params[$widget_id]['current']++;
	}
	//}}}

	//{{{ resetCurrent
	/**
	 * Resets current parameter for given widget
	 *
	 * @param string id of widget to be looked up
	 * @return null
	 */
	function resetCurrent($widget_id)
	{
		if(!isset($this->widget_params[$widget_id])) return ;
		$this->widget_params[$widget_id]['current'] = $this->widget_params[$widget_id]['from'];
		
	}
	//}}}

	//{{{ isFirst
	/**
	 * It defines whenever current iteration is the first.
	 *
	 * @param string id of widget_params to be looked up
	 * @param string scope of current request. Might be 'local' or 'global'
	 * @return bool
	 */
	function isFirst($widget_id,$scope)
	{
		if(!isset($this->widget_params[$widget_id])) return false;
		if($scope == "global")
			return $this->widget_params[$widget_id]['current'] == 0;
		else
			return $this->widget_params[$widget_id]['current'] == $this->widget_params[$widget_id]['from'];
	}
	//}}}

	//{{{ isLast
	/**
	 * It defines whenever current iteration is the last.
	 *
	 * @param string id of widget_params to be looked up
	 * @param string scope of current request. Might be 'local' or 'global'
	 * @return bool
	 */
	function isLast($widget_id,$scope)
	{
		if(!isset($this->widget_params[$widget_id])) return false;
		if($scope == "global")
			return $this->widget_params[$widget_id]['current'] == $this->widget_params[$widget_id]['count']-1;
		return $this->widget_params[$widget_id]['current'] == 
			$this->widget_params[$widget_id]['from'] + $this->widget_params[$widget_id]['limit'] -1;
		
	}
	//}}}
	
	//{{{ getMatchedIndex
	/**
	 * Returns matched index
	 *
	 * @param null
	 * @return int
	 */
    function getMatchedIndex()
    {
        return $this->matched_index;
	}
	//}}}

	//{{{ setMatchedIndex
	/**
	 * Sets matched index.
	 *
	 * @param int index to set
	 * @return null
	 */
    function setMatchedIndex($ind = null)
    {
        if(!isset($ind)) return;
        $this->matched_index = $ind;
	}
	//}}}
}
//}}}

//{{{ AjaxController
/** 
 * Used when current request came via AJAX
 * Need to simplify workflow and remove unnecessary code blocks.
 */
class AjaxController extends Controller
{

	//{{{ __construct
	/**
	 * Constructor
	 */
    protected function __construct()
    {
        $this->is_ajax = true;
        parent::__construct();
    }
	//}}}

	//{{{ init
	/**
	 * Lite version of Controller::init method. 
	 * Navigator object isn't created and step isn't added, 
	 * standard JS and CSS isn't added to the head part.
	 *
	 * @param null
	 * @return nul
	 */
	function init()
    {
		$this->trigger("BeforeInit",$this);

		Boot::setupAll();
        
        $this->header = Header::get();
		$this->dispatcher = new WidgetEventDispatcher();
		$this->display_mode_params = new DisplayModeParams();
		$this->adjacency_list = new WidgetsAdjacencyList();


        $full_path = $this->findPage();

		$dom = new DomDocument;
        if($dom->load($full_path) === false)
            throw new ControllerException("Can not load XML ".$full_path);

		$this->restoreSignatures();
		$this->trigger("BeforeHandlePOST",$this);

        if($_SERVER['REQUEST_METHOD'] == "POST")
		{
			$this->restoreCheckers();
			$this->parsePageOnPOST($this->processPage($dom));
			$this->handlePOST();
			exit();
		}

        $this->parsePageOnGET($this->processPage($dom));
		$this->inited = true;
		$this->trigger("AfterInit",$this);
    }
	//}}}

	//{{{ head
	/**
	 * This method optimized for work with jQuert <code>$.post</code> and 
	 * <code>$.get</code> functions.
	 *
	 * If responce string was specified, given cotent will be outputed, depending
	 * of the <code>$echo</code> flag.
	 *
	 * While the regular GET request, JS and CSS links will be outputed with {@link Header}
	 * class to the &lt; head &gt; part of the document. 
	 * This method override such method. It will wrap CSS and JS links to with the
	 * JS wrapper function (defined in default.js), which will check if such file already exists in the document.
	 * This approach prevents eventual CSS rule overriding (and JS functions (re)defenitions)
	 * due to incorrect order. This makes loading of partial content less error prone.
	 *  
	 * @param bool unused. Leaved for compatibility with Controller::head
	 * @return string empty string
	 */
	function head($echo = 1)
	{

		$head = "";

		$this->trigger("BeforeHead",$this);
	
        if(isset($this->response_string))
            if ($echo) echo $this->response_string;
            else return $this->response_string;

		usort($this->scripts,create_function('$a,$b',
			'if($a["priority"] == $b["priority"]) return $a["ind"]-$b["ind"];
			 else return $a["priority"] - $b["priority"];'));

		foreach($this->scripts as $v)
		{
			if(isset($v['cond']))
				$head .= "<!--[if ".$v['cond']."]>\n";
			$head .= "<script type=\"text/javascript\">loadScript(\"{$v['src']}\");</script>\n";
			if(isset($v['cond']))
				$head .= "<![endif]-->\n";
		}

		usort($this->css,create_function('$a,$b',
			'if($a["priority"] == $b["priority"]) return $a["ind"]-$b["ind"];
			 else return $a["priority"] - $b["priority"];'));

		foreach($this->css as $v)
		{
			$f = null;
			if(isset($v['cond']))
				$head .= "<!--[if ".$v['cond']."]>\n";
			$head .= "<script type=\"text/javascript\">loadCSS(\"{$v['src']}\");</script>";
			if(isset($v['cond']))
				$head .= "<![endif]-->\n";

		}

		$this->trigger("AfterHead",array($this,&$v));

		if($echo)
			echo $head;
		else return $head;
	}
	//}}}

	//{{{ tail
	/**
	 * Returns empty tail, due to it doesn't need while AJAX request.
	 *
	 * @param bool unused. Leaved for compatibility with Controller::head
	 * @return string empty string
	 */
	function tail($echo = 1)
	{
        return "";
	}
	//}}}

	//{{{ handlePOST
	/**
	 * Handles POST data while AJAX request.
	 * It works like {Controller::handlePOST} and signaatures will be checked unless 
	 * form is on the current page and <code>no_check</code> attribute is not specified.
	 *
	 * Typical use-case of this function is posting form with javascript with 
	 * <code>$(form).serialize()</code> method or <code>ajaxSubmit</code> from
	 * jquert.form plugin.
	 *
	 * Instead of redirects, like in <code>Controller::handlePOST()</code>
	 * method, <code>exit()</code> calls will be used.
	 *
	 * Another difference is that no "save form values and show error boxes" sequence 
	 * is used. If some checker of validator error are present, <code>exit()</code>
	 * will terminate futher form processing.
	 *
	 * Additionally, unlike <code>Controller::handlePOST</code>, handler methods could specify 
	 * <code>responce_string</code> to return status of form processing. It will be echo'ed at once.
	 * 
	 * @param null
	 * @return null
	 */
	protected function handlePOST()
    {
		$this->trigger("BeforeHandlePOST",$this);

		if($this->post->isEmpty()) 
			Header::redirect(requestURI(true), Header::SEE_OTHER); 

		$formid = null;

		WidgetLoader::load("WForm");
		list($formid) = explode(":",$this->post->{WForm::signature_name});
		if(!empty($formid)  && !in_array($formid,$this->no_check_forms))
		{
			$this->trigger("BeforeCheckSignature",$this);
			if(!$this->checkSignature($this->post->{WForm::signature_name}))
				exit("Error while checking POST data 1");

			POSTErrors::flushErrors();

			$this->trigger("BeforeCheckByRules",array(&$this->post,$this->post->{WForm::signature_name}));

			POSTChecker::checkByRules($this->post->{WForm::signature_name},$this->checker_rules,$this->checker_messages);
			POSTChecker::checkFiles(  $this->post->{WForm::signature_name},$this->file_rules,$this->checker_messages);

			if(POSTErrors::hasErrors())
				//Header::redirect(requestURI(true), Header::SEE_OTHER); 
				exit("Error while checking POST data 2");

			$this->trigger("BeforeCallHandlers",array($this,&$formid));

			try
			{
				DataUpdaterPool::callCheckers($formid);
			}
			catch(CheckerException $e)
			{
				exit("Error ".$e->getMessage()." in widget ".$e->getWidgetName);
			}
			DataUpdaterPool::callHandlers($formid);
			DataUpdaterPool::callFinalize($formid);
		}
		else
		{
			try
			{
				DataUpdaterPool::callCheckers($formid);
			}
			catch(CheckerException $e)
			{
				exit("Error ".$e->getMessage()." in widget ".$e->getWidgetName);
			}
			DataUpdaterPool::callHandlers($formid);
			DataUpdaterPool::callFinalize($formid);
		}

		$this->trigger("AfterHandlePOST",$this);

        if(isset($this->response_string))
		{
			$this->trigger("AfterHeadBodyTailResponce",array($this,&$this->response_string));
            echo $this->response_string;
		}

	}
	//}}}
}
//}}}
?>
