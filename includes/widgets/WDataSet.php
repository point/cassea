<?php
//
// $Id$
//
WidgetLoader::load("WObject");
//{{{ WDataSet
class WDataSet extends WObject
{
	protected 
		$data_object = null,
		$priority = 0

		    ;
    // {{{ WDataSet 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    */
    function WDataSet($id = null)
    {
		parent::__construct($id);
    }
    // }}}
	// {{{ parseParams
    /**
    * Method description
    *
    * More detailed method description
    * @param    array $params
    * @return void
    */
    function parseParams(SimpleXMLElement $params)
    {
		if(isset($params['name']))
			$this->setId($params['name']);
		$this->data_object = new DataSourceObject();
		$this->data_object->parseParams($params);
		if(isset($params['priority']))
			$this->setPriority(0+$params['priority']);
    }
    // }}}

    // {{{ GetData
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    */
    function getData($w_id)
	{
		if(($v = $this->data_object->getData($w_id)) !== false)
			if($v instanceof ResultSet)
				ResultSetPool::set($v,$this->getPriority());
			else
				ResultSetPool::set(t(new ResultSet())->forid($w_id)->def($v),$this->getPriority());
		return ResultSetPool::get($w_id);
	}
    //}}}
        
    // {{{ getName 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getName()
    {
		return $this->getId();
    }
    // }}}
	
	// {{{ setPriority
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $priority
    * @return   void
    */
    function setPriority($priority = 0)
    {
		$this->priority = 0+$priority;
    }
    // }}}

	// {{{ getPriority
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getPriority()
    {
		return $this->priority;
    }
    // }}}

}
//}}}
?>
