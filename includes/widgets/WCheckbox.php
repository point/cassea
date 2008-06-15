<?php
//
// $Id:$
//
WidgetLoader::load("WControl");
//{{{ WCheckbox
class WCheckbox extends WControl
{
    var

        /**
        * @var      boolean
        */
		$checked = null,
        /**
        * @var      string
        */
        $text = null
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
		if(!empty($elem['checked']))
	       	$this->setChecked((string)$elem['checked']);
		if(!empty($elem['text']))
			$this->setText((string)$elem['text']);

		$this->addToMemento(array("checked","text"));

		parent::parseParams($elem);		    	
    }
    // }}}
    
    // {{{ setChecked 
    /**
    * Method description
    *
    * More detailed method description
    * @param    boolean $checked    
    * @return   void
    */
    function setChecked($checked)
    {
		if(!isset($checked) || !is_scalar($checked))
			return;
        $this->checked = 0+$checked;
    }
    // }}}
    
    // {{{ getChecked 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   boolean
    */
    function getChecked()
    {
		return $this->checked;
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
		if(!isset($this->text))
			$this->setText($this->getValue());
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
			"text"=>$this->getText(),
			"checked"=>($this->getChecked())?'checked="1"':''
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
		$this->setChecked($data->get('checked'));
		$this->setText($data->get('text'));

		parent::setData($data);
    }
    //}}}
}
//}}}

?>
