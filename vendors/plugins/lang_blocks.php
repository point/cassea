<?php

Controller::getInstance()->onBeforePageClearingBlocks = array("LangBlockRemover","remove");

class LangBlockRemover
{
	static function remove($controller, $dom)
	{
        $node_list = $dom->getElementsByTagName('block');
        for($i = 0; $i < $node_list->length; $i++)
		{
			$node = $node_list->item($i);
			foreach($node->attributes as $attrName => $attrNode)
			{
				if($attrName == "lang" && $attrNode->nodeValue 
					&& $attrNode->nodeValue !== Language::currentName())
				{
					$node->parentNode->removeChild($node);
					break;
				}
			}
        }
	}
}
