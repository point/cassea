<?php
//
// $Id: $
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
		return WComponent::replaceWithLangConst($ret);
    }
    // }}}
}
//}}}

?>
