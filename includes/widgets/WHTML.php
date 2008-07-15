<?php
//
// $Id:$
//
WidgetLoader::load("WComponent");
//{{{ WBlock
class WHTML extends WComponent
{
	protected
		/**
		* @var string
		*/
		$src = null,
		/**
		* @var string
		*/
		$text = null
		;
	private $page_text;
    // {{{ __construct
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    */
    function __construct($id = null)
    {
		parent::__construct($id);
    }
    // }}}
    // {{{ parseParams
    /**
    * Method description
    *
    * More detailed method description
    * @param    array
    * @return void
    */
    function parseParams(SimpleXMLElement $elem)
    {
		if(isset($elem['src']))
			$this->setSrc((string)$elem['src']);
		else
			$this->setText(trim((string)$elem));

		$this->addToMemento(array("src","text"));
		parent::parseParams($elem);		    	
    }
    // }}}
   
    // {{{ buildComplete
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   void
    */
	function buildComplete()
	{
		if(!isset($this->tpl))
			$this->tpl = $this->createTemplate();
		if(Config::get("CACHE_STATIC_PAGES"))
		{
			$page = Controller::getInstance()->getPage();
			$cn = Controller::getInstance()->getControllerName();
			$storage = 	Storage::create("WHTML cache");
			if(!$storage->is_set($cn."_".$page."_".$this->getId()))
			{
				$this->page_text = 
					$this->getSrc()?file_get_contents($this->getSrc()):
						($this->getText()?$this->getText():"");
				$storage->set($cn."_".$page."_".$this->getId(),array("text"=>$this->page_text,"cache_time"=>time()));
			}
			else
			{
				$p = $storage->get($cn."_".$page."_".$this->getId());
				$mtime = 0;
				$changed = 0;
				if($this->getSrc())
				{
					if(pageChanged($this->getSrc(),$p['cache_time']))
						$changed = 1;
				}
				elseif($this->getText())
				{
					if(Controller::getInstance()->XMLPageChanged($p['cache_time']))
						$changed = 1;
				}
				if($changed)
				{
					$this->page_text = 
						$this->getSrc()?file_get_contents($this->getSrc()):
						($this->getText()?$this->getText():"");
					$storage->set($cn."_".$page."_".$this->getId(),array("text"=>$this->page_text,"cache_time"=>time()));
				}
				else 
					$this->page_text = $p['text'];
			}
		}
		else
			$this->page_text = 
					$this->getSrc()?file_get_contents($this->getSrc()):
						($this->getText()?$this->getText():"");
		parent::buildComplete();
	}    
	// }}}
    // {{{ assignVars
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   void
    */
    function assignVars()
    {
		$this->tpl->setParamsArray(array(
				"content"=>$this->page_text
			));
		parent::assignVars();
    }
	// }}}	

    // {{{ setText 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $text
    * @return   void
    */
    function setText($text)
    {
		if(!isset($text) || !is_scalar($text))
			return;

		$storage = 	Storage::create("WHTML cache");
		if($storage->is_set($cn."_".$page."_".$this->getId())) return;

		$this->text = "".$text;
    }
    // }}}
    
    // {{{ getText
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getText()
    {
		return $this->text;
    }
    // }}}
	
    // {{{ setSrc 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $src    
    * @return   void
    */
    function setSrc($src)
    {
		if(!isset($src) || !is_scalar($src)) 
			return ;
		if(file_exists($src))
			$this->src = $src;
		elseif(file_exists(Config::get("ROOT_DIR").$src))
			$this->src = Config::get("ROOT_DIR").$src;
		elseif(file_exists(Config::get("HTML_DIR").$src))
			$this->src = Config::get("HTML_DIR").$src;
    }
    // }}}
    
    // {{{ getSrc 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getSrc()
    {
		return $this->src;
    }
    // }}}
}
//}}}
?>
