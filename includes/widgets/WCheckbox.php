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
		if(isset($elem['checked']))
	       	$this->setChecked((string)$elem['checked']);
		if(isset($elem['text']))
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
        if(empty($this->value) && !is_numeric($this->value))
            $this->setValue('checkbox');
		parent::buildComplete();
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
			"text"=>Language::encodePair($this->getText()),
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
    function setData(WidgetResultSet $data)
    {
		$this->setChecked($data->get('checked'));
		$this->setText($data->get('text'));

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
            ResultSetPool::set(
                t(new ResultSet())
                ->f("wcheckbox[name=".$this->getName()."]")->set('checked',1),ResultSetPool::SYSTEM_PRIORITY,true);
    }
    // }}}
}
//}}}

?>
