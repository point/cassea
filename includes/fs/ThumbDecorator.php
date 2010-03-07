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

// $Id:$

//{{{ ThumbDecorator
class ThumbDecorator extends Decorator{

	protected $styles = array();
	function __construct($file, array $styles = array("thumb"=>"100x100"))
	{
		parent::__construct($file);
		$this->styles = preg_grep("/\d+x\d+/",$styles);
	}
	function createThumbs()
	{
		foreach($this->styles as $dir=>$dim)
		{
			list($w,$h) = explode("x",$dim);
			t(new ImageDecorator(
				$this->file->copy(
					$this->file->getParent()->mkdir($dir))))
			->resize(null,$w,$h);
		}
	}
	function getThumb($style="thumb")
	{
		if(!isset($this->styles[$style])) throw new DecoratorException("There is no such style '$style' ThumbDecorator");
		return $this->file->getParent()->getDir($style)->getFile($this->file->getName());
	}
	// if null delete file from all styles
	// if string - delete file only from this style
	// if array - delete from list of styles
	function delete($styles = null) 
	{
		if(!isset($styles))
			$styles = array_keys($this->styles);
		elseif(is_string($styles))
			$styles = array($styles);

		foreach($styles as $style)
			$this->file->getParent()->getDir($style)->getFile($this->file->getName())->delete();
	}
}//}}}
