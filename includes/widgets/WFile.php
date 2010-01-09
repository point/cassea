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
WidgetLoader::load("WControl");
//{{{ WEdit
class WFile extends WControl implements iFileUploader
{
    protected
        $size = 40,
        $maxFileSize = 0;
    // {{{ __construct
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    */
    function __construct($id = null)
    {
        $this->setMaxFileSize(sizeFromString(ini_get('upload_max_filesize')));
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
		if(isset($elem['size']))
            $this->setSize((string)$elem['size']);

		if(isset($elem['max_file_size']))
            $this->setMaxFileSize((string)$elem['max_file_size']);

		$this->addToMemento(array("size", 'max_file_size'));
		parent::parseParams($elem);		    	
    }
    // }}}
    
    // {{{ setSize 
    function setMaxFileSize($size)
    {
		if(!isset($size) || !is_scalar($size))
            return;
        $this->maxFileSize = sizeFromString($size);
    }
    // }}}
    
    // {{{ getSize 
    function getMaxFileSize()
    {
		return $this->maxFileSize;
    }
    // }}}
    
    // {{{ setSize 
    function setSize($size)
    {
		if(!isset($size) || !is_scalar($size) || (0+$size) > 1024)
			return;
		$this->size = 0 + $size;
    }
    // }}}
    
    // {{{ getSize 
    function getSize()
    {
		return $this->size;
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
        $this->setMaxFileSize($data->get('max_file_size'));
        parent::setData($data);
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
            "size"=>$this->getSize(),
            'max_file_size' => $this->getMaxFileSize()
		));
		parent::assignVars();
    }
	// }}}	

}// }}}
