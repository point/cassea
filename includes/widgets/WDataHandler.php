<?php
/*- vim:noet:shiftwidth=4:tabstop=4: 
{{{ LICENSE  
* Copyright (c) 2008,2009 Cassea Project
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
//{{{ WDataHandler
class WDataHandler extends WObject
{
	protected 
        $is_static = false,
        $form_ids = array(),
		$handler_object = null,
		$priority = 0
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
        if(isset($params['forms']))
            $this->setFormIds((string)$params['forms']);
		if(isset($params['static']))
			$this->setStatic(0+$params['static']);

		$this->handler_object = new DataHandlerObject($this->getStatic());
		$this->handler_object->parseParams($params);
		DataUpdaterPool::set($this->handler_object,$this->getPriority(),$this->getId(),$this->getFormIds());
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
    
	// {{{ setFormIds
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $ids
    * @return   void
    */
    function setFormIds($ids)
    {
        if(!is_scalar($ids)) return;
        $this->form_ids = array_map('trim',explode(",",$ids));
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
    function getFormIds()
    {
		return $this->form_ids;
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
