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
WidgetLoader::load("WEdit");
//{{{ WSpinner
class WSpinner extends WEdit
{
    protected

        /**
        * @var      int	
        */
        $step = 1,
        /**
        * @var      mixed
        */
        $min = '0-Number.MAX_VALUE',
        /**
        * @var      mixed
        */
		$max = 'Number.MAX_VALUE',
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
		if(isset($elem['step']))
	       	$this->setStep((int)$elem['step']);
		if(isset($elem['min'])) 
			$this->setMin((int)$elem['min']);
		if(isset($elem['max']))
			$this->setMax((int)$elem['max']);
		if(isset($elem['text']))
			$this->setText((string)$elem['text']);
        $this->setSize(5);
		$this->addToMemento(array("step","min","max","text"));
		parent::parseParams($elem);		    	
    }
     // }}}
    // {{{ setMin
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $min
    * @return   void
    */
    function setMin($min)
    {
		if(!isset($min) || !is_scalar($min))
			return;
		$this->min = 0 + $min;
    }
    // }}}
    
    // {{{ getMin
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getMin()
    {
		return $this->min;
    }
    // }}}

    // {{{ setMax
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $max
    * @return   void
    */
    function setMax($max)
    {
		if(!isset($max) || !is_scalar($max))
			return;
		$this->max = 0 + $max;
    }
    // }}}
    
    // {{{ getMax
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getMax()
    {
		return $this->max;
    }
    // }}}

    // {{{ setStep
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $step
    * @return   void
    */
    function setStep($step)
    {
		if(!isset($step) || !is_scalar($step) || $step < 1)
			return;
		$this->step = 0+$step;
	}
    // }}}
    
    // {{{ getStep
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getStep()
    {
		return $this->step;
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
		parent::buildComplete();
		$this->setSize(6);
		$this->setType("text");
		$controller = Controller::getInstance();
		$controller->addScript("spinner.js");
		$controller->addCSS("spinner.css");
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
		$this->setStep($data->get('step'));
		$this->setMax($data->get('max'));
		$this->setMin($data->get('min'));
		$this->setText($data->get('text'));

		parent::setData($data);
    }
     //}}}
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
			"step"=>$this->getStep(),
			"min"=>$this->getMin(),
			"max"=>$this->getMax(),
			"text"=>Language::encodePair($this->getText()),
		));
		parent::assignVars();
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
		$this->text = $text;
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
}
//}}}

?>
