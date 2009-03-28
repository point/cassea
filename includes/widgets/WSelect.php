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
WidgetLoader::load("WContainer");
//{{{ WSelect
class WSelect extends WControlContainer
{
    var

        /**
        * @var      boolean
        */
        $multiple = null,
        /**
        * @var      int
        */
        $size = null,
        /**
        * @var      WidgetCollection&
        */
		$items = null
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
       	if(isset($elem['multiple']))
			$this->setMultiple((string)$elem['multiple']); 
		if( isset($elem['size']))
			$this->setSize((string)$elem['size']); 
		$this->items = new WidgetCollection($this->getId(),$elem);

		$this->addToMemento(array("multiple","size"));
		
		parent::parseParams($elem);		    	
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

		$this->items->filter(array("WSelectOption","WRoll"));
		parent::buildComplete();
	}    
	// }}}
  
    // {{{ setMultiple
    /**
    * Method description
    *
    * More detailed method description
    * @param    boolean $multiple
    * @return   void
    */
    function setMultiple($multiple)
    {
		if(!isset($multiple) || !is_scalar($multiple)) return;

		$this->multiple = 0+$multiple;
        if($this->multiple)
            $this->name_w_braces = 1;
    }
    // }}}
    
    // {{{ getMultiple
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   boolean
    */
    function getMultiple()
    {
		return $this->multiple;
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
		if(!isset($size) || !is_scalar($size)) return;

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
            "size"=>!empty($this->size)?"size=\"".$this->getSize()."\"":"",
            "multiple"=>!empty($this->multiple)?"multiple=\"".$this->getMultiple()."\"":"",
			"select_content"=>$this->items->generateAllHTML()
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
        $this->setSize($data->get('size'));
        $this->setMultiple($data->get('multiple'));

    	parent::setData($data);
    }
    //}}} 
    
    // {{{ restorePOST
    /**
    * Method description
    *
    * More detailed method description
    * @param    mixed $post
    * @param    array $errors
    * @return   string
    */
    function restorePOST()
	{
        $errors = POSTErrors::getErrorFor($this->getName(),$this->getAdditionalID());
    	if($errors !== null)
        {
			$this->setFilterError(implode("<br/>",$errors));
        }
        $post_data = POSTErrors::getPOSTData($this->getName(),$this->getAdditionalID());
        if(isset($post_data))
        {
            ResultSetPool::set(
                t(new ResultSet())
                //->f("wselect[name=".$this->getName()."] > wselectoption")->selected(0)
                ->f1("wselect[name=".$this->getName()."]  wselectoption[value=".$post_data."]")
                ->set('selected',1),ResultSetPool::SYSTEM_PRIORITY);
        }
    }
    // }}}
}
//}}}

?>
