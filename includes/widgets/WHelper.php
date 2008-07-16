<?php
class BuilderException extends Exception {}
class ComplexBuilderException extends Exception {}
class WBuilder
{
		protected
			$tag_name,
			$attrs,
			$value
		;

			static $dom = null;
		function __construct($tag_name, $attrs=null, $value=null)
		{
			if(!isset($tag_name) || !is_scalar($tag_name)) throw new BuilderException("Tag name must be scalar and specified");
			if(!isset(self::$dom))
				self::$dom = new DOMDocument('1.0', 'iso-8859-1');
			$this->tag_name = $tag_name;
			$this->setAttrs($attrs);
			$this->setValue($value);
		}
		protected function setAttrs($attrs)
		{
			if(!is_array($attrs) || empty($attrs))
				return;
			$this->attrs = $attrs;
		}
		protected function setValue($value)
		{
			if(!is_array($value) || !isset($value) || count($value) != 1) return;
			$this->value = $value;
		}
		protected function buildDOM()
		{
			$dn2 = self::$dom->createElement($this->tag_name, isset($this->value)?$this->value:null);
			foreach($this->attrs as $k=>$v)
				$dn2->setAttribute($k,$v);
			return $dn2;
		}
		function build()
		{
			$dn = self::$dom->createElement("fake");
			$dn2 = $this->buildDOM();
			$dn->appendChild($dn2);
			return simplexml_import_dom($dn);
		}
}
class WComplexBuilder extends WBuilder
{
	protected
		$values = array()
	;
	function __construct($tag_name,$attrs = null,$value=null)
	{
		parent::__construct($tag_name,$attrs);
		$this->value = null;
	}
	function addValue(WBuilder $value)
	{
		$this->values[] = $value;
	}
	protected function buildDOM()
	{
		$dn = parent::buildDOM();
		foreach($this->values as $k => &$v)
			$dn->appendChild($v->buildDOM());
		return $dn;
	}
}

?>
