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
//{{{ WPageHandler
class WPageHandler extends WObject
{
    var

        /**
        * @var      string
        */
        $handler_object = null,
        /**
        * @var      mixed
        */
        $goto_url = null,
        /**
        * @var      mixed
        */
        $object_from_id = null,
        /**
        * @var      mixed
        */
        $object_method = null

     ;
    
    // {{{ parseParams 
    /**
    * Method description
    *
    * More detailed method description
    * @param    object $params    
    * @return   void
    */
    function parseParams(SimpleXMLElement $params)
	{
		if(isset($params['goto']))
			$this->setGotoURL((string)$params['goto']);
		
        elseif(isset($params['object_from']) && isset($params['method']))
        {
            $this->setObjectFrom((string)$params['object_from']);
            $this->setMethod((string)$params['method']);
        }
        else
        {
		    $this->handler_object = new PageHandlerObject();
            $this->handler_object->parseParams($params);
        }
    }
    // }}}
    
    // {{{ setGotoURL
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $goto_url
    * @return   void
    */
    function setGotoURL($goto_url)
    {
    	if(!is_numeric($goto_url) || abs($goto_url) == 0)
			return;
		$this->goto_url = abs($goto_url);
    }
    // }}}
    
    // {{{ getGotoURL 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   array
    */
    function getGotoURL()
    {
		return $this->goto_url;
    }
    // }}}
    
    // {{{ handle
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   array
    */
    function handle()
    {
		if(isset($this->goto_url))
			return $this->getGotoURL();
        elseif(isset($this->object_from_id))
        {
            if(($dh = DataUpdaterPool::getById($this->getObjectFrom())) != null &&
                ($o = $dh->getObject()) != null && method_exists($o,$this->getMethod()))
                return  $o->{$this->getMethod()}();
        }
        else
	    	return $this->handler_object->handle(null);
    }
    // }}}
    // {{{ setObjectFrom
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $id
    * @return   void
    */
    function setObjectFrom($id)
	{
		if(!isset($id) || !is_string($id)) return;

		$this->object_from_id = $id;
    }
    // }}}
    
    // {{{ getObjectFrom
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getObjectFrom()
    {
		return $this->object_from_id;
    }
    // }}}
    // {{{ setMethod
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $method
    * @return   void
    */
    function setMethod($method)
	{
		if(!isset($method) || empty($method)) return;

		$this->method = (string)$method;
    }
    // }}}
    
    // {{{ getMethod
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   array
    */
    function getMethod()
    {
		return $this->method;
    }
    // }}}
    
}
//}}}

?>
