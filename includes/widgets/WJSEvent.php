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
//{{{ WJSEvent
class WJSEvent 
{
    private

        /**
        * @var      array
        */
		$conditional = array(),
        /**
        * @var      array
        */
		$plain = array()		
			;
	
        
     // {{{ __construct
    /**
    * Method description
    *
    * More detailed method description
    * @param    array $params
    */
    function __construct($params = null )
    {
		if(isset($params))
			$this->add($params);
    }
    // }}}
	
    // {{{ addToPlain
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $func_call
    */
    function addToPlain($func_call)
    {
		if(empty($func_call)) return;
		$this->plain[] = $func_call;
    }
    // }}}
	
    // {{{ addToConditional
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $func_call
    */
    function addToConditional($func_call)
    {
		if(empty($func_call)) return;
		$this->conditional[] = $func_call;
    }
    // }}}
	
    // {{{ add
    /**
    * Method description
    *
    * More detailed method description
    * @param    string $params_str
    */
	function add($params_str)
	{
		/*simple implementation
		 **allowed:
		 ** return f1 ('qw') && f2('xc');
		 **
		 **not allowed:
		 ** return f1(true && true) && f2(true && false)
		 */
		if(strpos($params_str,"&&") !== false)
		{
			$params_str = str_replace("return","",$params_str);
			$funcs = split("&&",$params_str);
			foreach($funcs as $v)
				if((trim($v))!= '')
					$this->addToConditional(str_replace("\"","'",trim($v)));
		}
		else
		{
			$this->addToPlain($params_str);
		}
	}
	// }}}
	
    // {{{ generateJS 
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   string
    */
    function generateJS()
    {
		$ret = "";
		if(count($this->plain))
			$ret .= implode(";",$this->plain)."; ";
		if(count($this->conditional))
			$ret .= "return ".implode(" && ",$this->conditional);
		return $ret;
    }
    // }}}
}
//}}}

?>
