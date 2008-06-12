<?php
//
// $Id:$
//
WidgetLoader::load("WControl");
//{{{ WEdit
class WEdit extends WControl
{
    protected

        /**
        * @var      int
        */
        $maxlength = 255, 
        /**
        * @var      int
        */
        $size = 60,
        /**
        * @var      string
        */
		$type = "text"
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
		if(isset($elem['maxlength']))
			$this->setMaxLength((string)$elem['maxlength']);
		if(isset($elem['size']))
			$this->setSize((string)$elem['size']);
		if(isset($elem['type']))
			$this->setType((string)$elem['type']);

		$this->addToMemento(array("maxlength","size","type"));

		parent::parseParams($elem);		    	
    }
    // }}}

    // {{{ setMaxLength 
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $maxlength    
    * @return   void
    */
    function setMaxLength($maxlength)
    {
		if(!isset($maxlength) || !is_scalar($maxlength) || (0+$maxlength > 1024))
			return;
		$this->maxlength = 0 + $maxlength;
    }
    // }}}
    
    // {{{ getMaxLength 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getMaxLength()
    {
		return $this->maxlength;
    }
    // }}}
    
    // {{{ setSize 
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $size    
    * @return   void
    */
    function setSize($size)
    {
		if(!isset($size) || !is_scalar($size) || (0+$size) > 1024)
			return;
		$this->size = 0 + $size;
    }
    // }}}
    
    // {{{ getSize 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getSize()
    {
		return $this->size;
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
		if(!isset($type) || ($type != "text" && $type != "password"))
			return;
        $this->type = "".$type;
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
		$this->setMaxLength($data->get('maxlength'));
		$this->setMaxLength($data->get('size'));
		$this->setType($data->get('type'));

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
			"type"=>$this->getType(),
			"maxlength"=>$this->getMaxLength(),
			"size"=>$this->getSize()	
		));
		parent::assignVars();
    }
	// }}}	
}
//}}}

?>
