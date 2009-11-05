<?php

class InlineHTML
{
	static private $start = null;
	static private $end = null;
	static function processDOM($controller, $elem, $system)
	{
		if(substr($elem->getName(),0,1) !== "W")
		{
			$prev_tagname = $elem->getName();
			$elem = self::changeName($elem,"WInlineHTML");
			$elem->addAttribute("__real_tagname",(string)$prev_tagname);
		}
	}
	static function changeName($elem, $name) 
	{
		$dom_sxe = dom_import_simplexml($elem);
		$dom = new DOMDocument('1.0',"UTF-8");
		$dom->ecoding = "UTF-8";
		$node = $dom->importNode($dom_sxe, true);
		$node = $dom->appendChild($node);
		$newnode = $dom->createElement($name);

		foreach($node->childNodes as $child)
		{
			$child2 = $child->cloneNode(true);
			$newnode->appendChild($child2);
			unset($child2);
		}
		foreach ($node->attributes as $attrName => $attrNode) {
			$newnode->setAttribute($attrName, $attrNode->nodeValue);
		}
		
		//"//*[starts-with(name(),'B')]"
		$res = t(new DOMXPath($dom))->evaluate("//*[starts-with(name(),'W')]");
		
		if(!$res instanceof DOMNodeList || ($res instanceof DOMNodeList && $res->length == 0))
			$newnode->setAttribute("__use_cdata","1");

		$dom->replaceChild($newnode,$node);

		unset($dom_sxe);
		unset($node);
		unset($newnode);
		return simplexml_import_dom($dom);
	}
}
