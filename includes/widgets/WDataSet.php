<?php
/*- vim:expandtab:shiftwidth=4:tabstop=4: 
{{{ LICENSE  
* Copyright (c) 2008, Cassea Project
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*     * Redistributions of source code must retain the above copyright
*       notice, this list of conditions and the following disclaimer.
*     * Redistributions in binary form must reproduce the above copyright
*       notice, this list of conditions and the following disclaimer in the
*       documentation and/or other materials provided with the distribution.
*     * Neither the name of the Cassea Project nor the
*       names of its contributors may be used to endorse or promote products
*       derived from this software without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY CASSEA PROJECT ''AS IS'' AND ANY
* EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
* DISCLAIMED. IN NO EVENT SHALL CASSEA PROJECT BE LIABLE FOR ANY
* DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
}}} -*/


//
// $Id$
//
WidgetLoader::load("WObject");
//{{{ WDataSet
class WDataSet extends WObject
{
	protected 
		$data_object = null,
		$priority = 0,
		$delayed = false,
		$is_static = false

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
    * @param    array $params
    * @return void
    */
    function parseParams(SimpleXMLElement $params)
	{

		if(isset($params['priority']))
			$this->setPriority(0+$params['priority']);
		if(isset($params['delayed']))
			$this->setDelayed(0+$params['delayed']);
		if(isset($params['static']))
			$this->setStatic(0+$params['static']);

		$this->data_object = new DataSourceObject($this->getStatic());
		$this->data_object->parseParams($params);

		if($this->data_object->hasDatasourceParamFrom('limit'))
			$this->setDelayed(1);

		if(!$this->getDelayed())
			$this->manageData();
    }
    // }}} 
	// {{{ manageData
	private function manageData()
	{
		if($this->data_object->hasDatasourceMethod())
		{
			if(($v = $this->data_object->getData()) !== null && $v instanceof ResultSet)
				ResultSetPool::set($v,$this->getPriority());
		}
		else
			DataObjectPool::set($this->data_object,$this->getPriority());
	}
	// }}}

	function loadDelayed()
	{
		if(!$this->getDelayed()) return;
		$this->manageData();
	}
	
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
		if($priority < 0 || $priority > 999 || !is_numeric($priority)) return;
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

	// {{{ setDelayed
    /**
    * Method description
    *
    * More detailed method description
    * @param    bool $delayed
    * @return   void
    */
    function setDelayed($delayed = false)
    {
		$this->delayed = (bool)$delayed;
    }
    // }}}

	// {{{ getDelayed
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   bool
    */
    function getDelayed()
    {
		return $this->delayed;
    }
    // }}}
	
	// {{{ setStatic
    /**
    * Method description
    *
    * More detailed method description
    * @param    bool $static
    * @return   void
    */
    function setStatic($static = false)
	{
		$this->is_static = (bool)$static;
    }
    // }}}

	// {{{ getStatic
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   bool
    */
    function getStatic()
    {
		return $this->is_static;
    }
    // }}}
}
//}}}
?>
