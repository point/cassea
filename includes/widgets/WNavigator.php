<?php
//
// $Id:$
//
WidgetLoader::load("WComponent");
//{{{ WNavigator
class WNavigator extends WComponent
{
    protected
        /**
	    * @var  string
        */
		$text = null,
        /**
        * @var  array
        */
		$steps = array()
        	   ;
    
    // {{{ WNavigator
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
    * @param array $params
    * @return void
    */
    function parseParams(SimpleXMLElement $elem)
    {
	    if(isset($elem['text']))
			$this->setText((string)$elem['text']);
		else 
			$this->setText(requestURI());

		$this->addToMemento(array("text"));

		parent::parseParams($elem);		    	
    }
    // }}}
    // {{{ setData 
    /**
    * Method description
    *
    * More detailed method description
    * @param    array $data
    * @return   void
    */
    function setData(ResultSet $data)
    {
		if($this->getId() != $data->getFor()) return;

		$this->restoreMemento();
		$this->setText($data->getDef());
		$this->setText($data->get('text'));
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
		$this->tpl = $this->createTemplate();
		$this->setStyleClass("__nav");
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
		$this->steps = $controller->getNavigator()->getSteps();
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
		$l =array_slice($this->steps,-1,1);
		$l = $l[0];
		$this->tpl->setParams(t(new TemplateParams())->set('steps',array_slice($this->steps,0,-1))->
			set('last_step',$l));

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
		$this->text = $text;
		$controller = Controller::getInstance();
		$controller->getNavigator()->setTitle(requestURI(1),$this->text);

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
