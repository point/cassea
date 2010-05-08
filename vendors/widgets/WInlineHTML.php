<?php
WidgetLoader::load("WContainer");
class WInlineHTML extends WContainer implements iNotSelectable
{
	protected 
		$real_tagname = "div",
		$use_cdata = false,
		$attributes = array(),
		$items = null,
		$text = ""
		;
    // {{{ parseParams
    /**
    * Method description
    *
    * More detailed method description
    * @param    array $params
    * @return void
    */
    function parseParams(SimpleXMLElement $elem)
    {
		foreach($elem->attributes() as $attr=>$attr_value)
			if(substr($attr,0,2) !== "__")
				$this->attributes[$attr] = (string)$attr_value;
		$this->real_tagname = (string)$elem['__real_tagname'];

		if(isset($elem['__use_cdata']))
			$this->use_cdata = (bool)(string)$elem['__use_cdata'];

		if($this->use_cdata)
		{
			$dom_node = dom_import_simplexml($elem);
			foreach($dom_node->childNodes as $cn)
				$this->text .= html_entity_decode($cn->ownerDocument->saveXML($cn),
					ENT_QUOTES, "UTF-8");
		}
		else
			$this->items = new MixedCollection($this->getId(),$elem);
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
			if(in_array($this->real_tagname,array("hr","br","img","input", "param", "col","area","frame","meta","link","base","basefont","isindex")))
				$this->tpl = $this->createTemplate(null,"short_tag.tpl");
			else
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
		$attr = array();
		$content = (!empty($this->text))?$this->text:(isset($this->items)?$this->items->generateAllHTML():"");
		foreach($this->attributes as $a_name => $a_val)
			$attr[] = $a_name.'="'.str_replace("\"","\\\"",$a_val).'"';
		$this->tpl->setParamsArray(array(
				"attributes"=>implode(" ",$attr),
				"real_tagname" => $this->real_tagname,
				"content"=>$content,
			));
		parent::assignVars();
    }
	// }}}	
}
