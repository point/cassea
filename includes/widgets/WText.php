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


// $Id$
//
WidgetLoader::load("WContainer");
//{{{ WText
class WText extends WContainer implements StringProcessable
{
    protected

        /**
        * @var      boolean
        */
        $is_abbr = 0,
        /**
        * @var      boolean
        */
        $is_acronym = 0,
        /**
        * @var      boolean
        */
        $is_address = 0,
        /**
        * @var      boolean
        */
        $is_blockquote = 0,
        /**
        * @var      boolean
        */
        $is_code = 0,
        /**
        * @var      boolean
        */
        $is_dfn = 0,
        /**
        * @var      boolean
        */
        $is_em = 0,
        /**
        * @var      int
        */
        $heading = null,
        /**
        * @var      boolean
        */
        $is_h = 0,

        /**
        * @var      boolean
        */
        $is_kbd = 0,
        /**
        * @var      boolean
        */
        $is_p = 0,
        /**
        * @var      boolean
        */
        $is_pre = 0,
        /**
        * @var      boolean
        */
        $is_q = 0,
        /**
        * @var      boolean
        */
        $is_samp = 0,
        /**
        * @var      boolean
        */
        $is_strong = 0,
        /**
        * @var      boolean
        */
        $is_var = 0,
        /**
        * @var      boolean
        */
        //$is_b = 0,
        /**
        * @var      boolean
        */
        $is_big = 0,
        /**
        * @var      boolean
        */
        $is_hr = 0,
        /**
        * @var      boolean
        */
        $is_i = 0,
        /**
        * @var      boolean
        */
        $is_small = 0,
        /**
        * @var      boolean
        */
        $is_sub = 0,
        /**
        * @var      boolean
        */
        $is_sup = 0,
        /**
        * @var      string
        */
        $text = null,
        $items = null,
        /**
        * @var      boolean
        */
		$is_br = 0,
        /**
        * @var      boolean
        */
		$is_simple = 0,

        /**
        * @var      array
        */
		$style_to_repeat = array('br','hr'),
        /**
        * @var      array
        */
		$repeat_count = 1

 ;
    
    // {{{ __construct
    /**
    * Method description
    *
    * More detailed method description
    * @param    int $id
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
		$this->setTextStyle($params);
		if(!count($params->children()))
	    	$this->setText((string) $params);
        else
            $this->items = new WidgetCollection($this->getId(),$params);
        foreach($this as $prop_name =>$prop_val)
            if(substr($prop_name,0,3) == "is_")
                $a[] = $prop_name;
        $a[] = "text";
        $this->addToMemento($a);
		parent::parseParams($params);		
    }
    // }}}
    // {{{ setTextStyle
    /**
    * Method description
    *
    * More detailed method description
    * @param    array $params
    * @return void
    */
    function setTextStyle($params)
	{
		$a = array();
		if($params instanceof SimpleXMLElement)
			$a = $params->attributes();
		elseif($params instanceof WidgetResultSet)
			$a = $params;

    	foreach ($a as $k => $v)
    	{
			$v = (int)$v;
			if(preg_match("/is_\S+/",$k))
				$this->addToMemento(array($k));
    		switch ($k)
    		{
    			case "abbr": $this->is_abbr = $v; break 2;
    			case "acronym": $this->is_acronym = $v;  break 2;
				case "address": $this->is_address = $v;  break 2;
				case "blockqoute": $this->is_blockqoute = $v;   break 2;
				case "code": $this->is_code = $v;   break 2;
				case "dfn": $this->is_dfn = $v;   break 2;
				case "em": $this->is_em = $v;   break 2;
				case "h": $this->heading = ($v>0 and $v <= 5)?$v:1; $this->is_h = $v;  break 2;
				case "kbd": $this->is_kbd = $v;   break 2;
				case "p": $this->is_p = $v;   break 2;
				case "pre": $this->is_pre = $v;   break 2;
				case "q": $this->is_q = $v;   break 2;
				case "samp": $this->is_samp = $v;   break 2;
				case "strong": $this->is_strong = $v;   break 2;
				case "var": $this->is_var = $v;   break 2;
				case "b": $this->is_strong = $v;   break 2;
				case "big": $this->is_big = $v;  break 2;
				case "hr": $this->is_hr = $v;  break 2;
				case "i": $this->is_i = $v;   break 2;
				case "small": $this->is_small = $v;   break 2;
				case "sub": $this->is_sub = $v;   break 2;
				case "sup": $this->is_sup = $v;   break 2;
				case "br" :$this->is_br = $v;   	break 2;
				case "simple" : $this->simple = $v;break 2;
    		}
    	}
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
        $this->checkAndSetData();
        $this->setTemplate('default');
		foreach($this->class_vars as $v)
		{
			if(preg_match("/^is_(\S+)$/",$v,$m) && $this->$v)
			{
				$this->setTemplate($m[1]);
				if(in_array($m[1],$this->style_to_repeat))
					$this->setRepeatCount($this->$v);
				break;
			}
		}		
		$this->tpl = $this->createTemplate();
		parent::preRender();
        if(!isset($this->text))
            $this->setText($this->items->generateAllHTML());
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
		if($this->is_h)
			$this->tpl->setParamsArray(array("heading"=>$this->heading));
		$this->tpl->setParamsArray(array('value'=>StringProcessorFactory::create($this->getStringProcess())->process(Language::encodePair($this->text)),
			'repeat_count'=>$this->getRepeatCount()
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
		$this->setTextStyle($data);
		$this->setText($data->getDef());
		$this->setText($data->get('text'));

		parent::setData($data);
    }
    //}}}
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
	
    // {{{ setRepeatCount
    /**
    * Method description
    *
    * More detailed method description
    * @param    int
    * @return   void
    */
    function setRepeatCount($repeat_count)
	{
		if(!isset($repeat_count) || !is_numeric($repeat_count)) return ;
		$this->repeat_count = 0 + $repeat_count;
    }
    // }}}
    // {{{ getRepeatCount
    /**
    * Method description
    *
    * More detailed method description
    * @param    void
    * @return   int
    */
    function getRepeatcount()
    {
		return $this->repeat_count;
    }
    // }}}

}
//}}}

?>
