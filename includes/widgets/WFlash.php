<?php
//
// $Id:$
//
//{{{ WFlash
class WFlash extends WComponent
{
    protected

        /**
        * @var      string
        */
        $bgcolor = "#000000",
        /**
        * @var      string
        */
        $height = 100,
        /**
        * @var      string
        */
        $width = 100 ,
        /**
        * @var      string
        */
		$src = null
		;
    
    // {{{ __construct
    /**
    * Method description
    *
    * More detailed method description
    * @param    array $params
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
		if(!empty($elem['height']))
	       	$this->setHeight((string)$elem['height']);
		if(!empty($elem['width']))
	       	$this->setWidth((string)$elem['width']);
		if(!empty($elem['src']))
			$this->setSrc((string)$elem['src']);       	
		if(!empty($elem['bgcolor']))
			$this->setBgColor((string)$elem['bgcolor']);

		$this->addToMemento(array("height","width","src", "bgcolor"));

		parent::parseParams($elem);		    	
    }
    // }}}
    // {{{ setBgColor
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $alt    
    * @return   void
    */
    function setBgColor($bgcolor)
    {
		if(!isset($bgcolor) || !is_scalar($bgcolor)) 
			return ;
		$this->bgcolor = "".$bgcolor;
    }
    // }}}
    
    // {{{ getBgColor
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getBgColor()
    {
		return $this->bgcolor;
    }
    // }}}
    
    // {{{ setHeight 
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $height    
    * @return   void
    */
    function setHeight($height)
    {
		if(!isset($height) || !is_scalar($height)) 
			return ;
		$this->height = 0 + $height;
    }
    // }}}
    
    // {{{ getHeight 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getHeight()
    {
		return $this->height;
    }
    // }}}
    
    // {{{ setWidth 
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $width    
    * @return   void
    */
    function setWidth($width)
    {
		if(!isset($width) || !is_scalar($width)) 
			return ;
		$this->width = 0 + $width;
    }
    // }}}
    
    // {{{ getWidth 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getWidth()
    {
		return $this->width;
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
		$this->src = $src;
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
		$this->setSrc($data->getDef());
		$this->setSrc($data->get('src'));
		$this->setWidth($data->get('width'));
		$this->setHeight($data->get('height'));
		$this->setBgColor($data->get('bgcolor'));

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
		$controller = Controller::getInstance();
		$controller->addScript("swfobject.js");
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
			"width"=>$this->getWidth(),
			"height"=>$this->getHeight(),
			"src"=>$this->getSrc(),
			'bgcolor'=>$this->getBgColor()
		));
		parent::assignVars();
    }
	// }}}	
}
//}}}

?>
