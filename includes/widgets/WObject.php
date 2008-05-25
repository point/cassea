<?php
// $Id: $

//{{{ WObject
abstract class WObject
{
 
 	private $log;   
	private static $s_counter = 0;
	/**
    * @var      string
    */
	protected $id = null;

	// {{{ __econstruct
    function __construct($id = null)
    {
    	$this->log = null; //&WLog::getInstance();
		$this->setID($id);
    }
	// }}}
	
	// {{{ getID 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getID()
    {
		return $this->id;
    }
    // }}}
    
    // {{{ setID 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $id    
    * @return   void
    */
    function setID($id = null)
	{
		if(!isset($id))
		{
			$id = "__s".(self::$s_counter++);
			$controller = Controller::getInstance();
			$w = $controller->getWidget($id);
			if($w instanceof WComponent)
				$this->setId();
		}
		$this->id = $id;
    }
    // }}}
}
//}}}

?>
