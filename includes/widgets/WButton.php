<?php
//
// $Id:$
//
WidgetLoader::load("WControl");
//{{{ WButton
class WButton extends WControl
{
    protected

        /**
        * @var      string
        */
		$type = "submit",
        /**
        * @var      string
        */
        $src = null,
        /**
        * @var      string
        */
		$alt = null   
		;

    
    // {{{ WButton 
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
       	if(isset($elem['src']) && $elem['type'] == "image")
			$this->setSrc((string)$elem['src']);
		if(isset($elem['type']))
			$this->setType((string)$elem['type']);
		if(isset($elem['alt']))
			$this->setAlt((string) $elem['alt']);

		$this->addToMemento(array("type","src","alt"));
		parent::parseParams($elem);		    	
    }
    // }}}
    // {{{ setType 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $type    
    * @return   void
    */
    function setType($type)
    {
		if($type == "submit" || 
			$type == "image" ||
			$type == "reset" ||
			$type == "button")

			$this->type = $type;
    }
    // }}}
    
    // {{{ getType 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getType()
    {
		return $this->type;
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
			return;	
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
		if(!isset($this->value)) $this->setValue("OK");
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

		if($this->getType() == "image")
		{
			$this->setTemplate("image");
			/*$controller = &CController::getInstance();
			$controller->addToCorrespMap($this->getName()."_x",$this->datahandler,1);
			$controller->addToCorrespMap($this->getName()."_y",$this->datahandler,1);*/
		}

		if(!isset($this->tpl))
			$this->tpl = $this->createTemplate();
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
			"src"=>$this->getSrc(),
			"type"=>$this->getType()
		));

		parent::assignVars();
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

		$this->setSrc($data->get('src'));
		$this->setAlt($data->get('alt'));
		$this->setType($data->get('type'));
		
		parent::setData($data);
    }
    //}}}
	
    // {{{ setAlt 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $alt    
    * @return   void
    */
    function setAlt($alt)
    {
		if(!isset($alt) || !is_scalar($alt))
			return;
		$this->alt = "".$alt;
		
    }
    // }}}
    
    // {{{ getAlt 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getAlt()
    {
		return $this->alt;
    }
    // }}}
}
//}}}

?>
