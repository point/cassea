<?php
//
// $Id:$
//
WidgetLoader::load("WContainer");
//{{{ WHyperLink
class WHyperLink extends WContainer
{
    
	protected
        /**
        * @var      string
        */
        $href = "#",
        /**
        * @var      string
        */
        $rel = null,
        /**
        * @var      string
        */
        $rev = null,
        /**
        * @var      WidgetCollection
        */
        $items = null,
        /**
        * @var      string
        */
		$text = "link",
        /**
        * @var      string
        */
		$target = null
  	;
    
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
    	if(!empty($elem['href']))
			$this->setHREF((string)$elem['href']);
		if(!empty($elem['baseurl']))
			$this->setBaseURL((string)$elem['baseurl']);

		if(!empty($elem['rel']))
			$this->setAttribute('rel',(string)$elem['rel']);
		if(!empty($elem['rev']))
			$this->setAttribute('rev',(string)$elem['rev']);
		if(!empty($elem['target']))
			$this->setAttribute('target',(string)$elem['target']);
		if(count($elem))
			$this->items = new WidgetCollection($elem);
		elseif((string)$elem)
			$this->setText((string)$elem);
		$this->addToMemento(array("href","baseurl","label","rel","rev","target","text"));

		parent::parseParams($elem);		    	
    }
    // }}}
   // {{{ setData 
    /**
    * Method description
    *
    * More detailed method description
    * @param    mixed $data
    * @return   void
    */
    function setData(ResultSet $data)
    {
		if($this->getId() != $data->getFor()) return;
		$this->restoreMemento();
		$this->setHREF($data->getDef());
		$this->setAttribute('rel',$data->get('rel'));
		$this->setAttribute('rev',$data->get('rev'));
		$this->setAttribute('target',$data->get('target'));
		$this->setText($data->get('text'));

		$href = $data->get('href');
		if(isset($href))
			if(is_array($href))
			{
				$page = null;
				if(isset($href['page']))
					{$page = $href['page'];unset($href['page']);	}
				$this->setHREF(Controller::getInstance()->makeURL($page,$href));
			}
			else
				$this->setHREF($href);

		parent::setData($data);
    }
    //}}}
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

		parent::buildComplete();
	}    
	// }}}
    // {{{ preRender
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   void
    */
    function preRender()
    {
		if(isset($this->dataset))
			$this->setData($this->dataset->getData($this->getId()));
		parent::preRender();
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
			'href'=> $this->getHREF(), 
			"rev"=> (!empty($this->rev))?('rev="'.$this->rev.'"'):'',
			"rel" => (!empty($this->rel))?('rel="'.$this->rel.'"'):'',
			"target"=>(!empty($this->target))?('target="'.$this->target.'"'):''
		));
		if(!empty($this->items))
			$this->tpl->setParamsArray(array(
				"text"=>$this->items->generateAllHTML()
			));
		else
			$this->tpl->setParamsArray(array(
				"text"=>$this->getText()
			));
		parent::assignVars();
    }
	// }}}	

    // {{{ setHREF
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $href
    * @return   void
    */
    function setHREF($href)
    {
		if(!isset($href) || !is_scalar($href))
			return;
		$this->href = str_replace('&amp;',"&",
			str_replace('javascript:','',(string)$href));
    }
    // }}}
    
    // {{{ getHREF
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getHREF()
    {
		return $this->href;
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
        $this->text = (string)$text;
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

}
//}}}

?>
