<?php
//
// $Id:$
//
WidgetLoader::load("WEdit");
//{{{ WCalendar
class WCalendar extends WEdit
{
	protected

        /**
        * @var      string
        */
        $date_format = "dd-mm-yy"
/*
From v3.1, the format is combinations of the following: 
d - day of month (no leading zero) 
dd - day of month (two digits) 
m - month (no leading zero) 
mm - month (two digits) 
y - year (two digits) 
yy - year (four digits) 
D - name of day (short) 
DD - name of day (long) 
M - name of month (short) 
MM - name of month (long) 
'...' - literal text 
'' - single quote 
anything else - literal text
*/
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
		if(!empty($elem['dateformat']))
	       	$this->setDateFormat((string)$elem['dateformat']);

		$this->addToMemento(array("dateformat"));
		parent::parseParams($elem);
    }
    // }}}
    
    // {{{ setDateFormat
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $dateformat
    * @return   void
    */
    function setDateFormat($dateformat)
    {
		if(!isset($dateformat))
			return;
		$this->date_format = $dateformat;
    }
    // }}}

    // {{{ getDateFormat
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getDateFormat()
    {
		return $this->date_format;
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

		if(isset($data['dateformat']))
		$this->setDateFormat((string)$data['dateformat']);

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
		$this->setSize(12);
		if($this->getState() && $this->getVisible())
		{
			$controller = Controller::getInstance();
			$controller->addCSS("ui.datepicker.css");
			$controller->addScript("ui.datepicker.js");
			$controller->addScript("ui.datepicker-ru.js");
		}
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
			"date_format"=>$this->getDateFormat()
		));
		parent::assignVars();
    }
	// }}}	
}
//}}}

?>
