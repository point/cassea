<?php
// $Id: WText.php 1020 2008-03-19 17:24:58Z point $
//
WidgetLoader::load("WComponent");
//{{{ WText
class WText extends WComponent
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
        $text = "",
        /**
        * @var      boolean
        */
		$is_br = 0,
        /**
        * @var      boolean
        */
		$is_simple = 0
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
		if((string)$params)
	    	$this->setText((string) $params);
		$this->addToMemento(array("text"));
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
    	foreach ($params->attributes() as $k => $v)
    	{
			if(preg_match("/is_\S+/",$k))
				$this->addToMemento(array($k));
    		switch ($k)
    		{
    			case "abbr": $this->is_abbr = 1; break 2;
    			case "acronym": $this->is_acronym = 1;  break 2;
				case "address": $this->is_address = 1;  break 2;
				case "blockqoute": $this->is_blockqoute = 1;   break 2;
				case "code": $this->is_code = 1;   break 2;
				case "dfn": $this->is_dfn = 1;   break 2;
				case "em": $this->is_em = 1;   break 2;
				case "h": $this->heading = ($v>0 and $v < 5)?$v:1; $this->is_h = 1;  break 2;
				case "kbd": $this->is_kbd = 1;   break 2;
				case "p": $this->is_p = 1;   break 2;
				case "pre": $this->is_pre = 1;   break 2;
				case "q": $this->is_q = 1;   break 2;
				case "samp": $this->is_samp = 1;   break 2;
				case "strong": $this->is_strong = 1;   break 2;
				case "var": $this->is_var = 1;   break 2;
				case "b": $this->is_strong = 1;   break 2;
				case "big": $this->is_big = 1;  break 2;
				case "hr": $this->is_hr = 1;  break 2;
				case "i": $this->is_i = 1;   break 2;
				case "small": $this->is_small = 1;   break 2;
				case "sub": $this->is_sub = 1;   break 2;
				case "sup": $this->is_sup = 1;   break 2;
				case "br" :$this->is_br = 1;   	break 2;
				case "simple" : $this->simple = 1;break 2;
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
		if(isset($this->dataset))
			$this->setData($this->dataset->getData());
		parent::preRender();
		foreach($this->_class_vars as $v)
		{
			if(preg_match("/^is_(\S+)$/",$v,$m) && $this->$v)
			{
				$this->setTemplate($m[1]);
				break;
			}
		}		
		$this->tpl = $this->createTemplate();
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
		$this->tpl->setParamsArray(array('value'=>$this->text));
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
    function setData($data)
	{
		$this->restoreMemento();
		if(empty($data)) return;
		if(isset($data[$this->id]))
			$t_data = $data[$this->id];
		else return;
		if(is_array($t_data))
		{
			$this->setTextStyle($t_data);
			if(isset($t_data['text']))
				$this->setText($t_data['text']);
		}
		elseif(isset($t_data) && is_scalar($t_data))
			$this->setText($t_data);

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
		if(!isset($text) || is_array($text))
		{
	   		$this->log->log(WHelper::alogf(__FILE__,__FUNCTION__,__LINE__,
				"Parameter text is empty or not set"),LOG_LEVEL_WARNING);
			return;
		}
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

}
//}}}

?>
