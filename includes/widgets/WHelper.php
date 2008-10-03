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
