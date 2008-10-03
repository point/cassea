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
// $Id:$
//
WidgetLoader::load("WContainer");
//{{{ WFieldSet
class WFieldSet extends WContainer
{
    var

        /**
        * @var      WidgetCollection&
        */
        $items = null,
        /**
        * @var  string
        */
		$legend_text = null
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
		if(isset($elem['legend']))
			$this->setLegend((string)$elem['legend']);
		$this->items = new WidgetCollection($this->getId(),$elem);
		
		$this->addToMemento(array("legend_text"));

		parent::parseParams($elem);		    	
    }
    // }}}
    
    // {{{ setLegend 
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $legend    
    * @return   void
    */
    function setLegend($legend)
    {
		if(!isset($legend) || !is_scalar($legend))
			return;
		$this->legend_text = (string)$legend;
    }
    // }}}
    
    // {{{ getLegend 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function getLegend()
    {
		return $this->legend_text;
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
		$this->setData(DataRetriever::getData($this->getId()));
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
			"legend"=>$this->getLegend(),
			"fieldset_content"=>$this->items->generateAllHTML()
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
    function setData(WidgetResultSet $data)
    {
		$this->restoreMemento();
		$this->setLegend($data->get('legend'));
    	parent::setData($data);
    }
    //}}}

}
//}}}

?>
