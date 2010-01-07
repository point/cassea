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
/**
 * This file contains class with simple template implementation.
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 */

//{{{ Template
/**
 * This class used for simple variable substitution into
 * various html or plain text files. It lies in the core of widget's 
 * output.
 *
 * Inside the template all PHP core functions might be used. Be its 
 * strongly recommended to use minimal subset. Core widgets are
 * using only "isset" for checks, string casting and foreach loops.
 *
 * Simple example:
 * <pre><code>
 * 
 * Random number - <?php echo rand();?> <br/>
 *
 * </code></pre>
 *
 * To output computed values, such as random values, use regular
 * echo, printf, etc.
 *
 * To assign variables into template, special object is injected into
 * template's scope.
 * 
 * For example, template code:
 * <pre><code>
 * 
 * Constant value - <?php echo (string)$p->constant_value; ?> <br/>
 *
 * </code></pre>
 *
 * To take output of this template, such code might be used:
 *
 * <pre><code>
 * 
 * $template = new Template("/tmp","template.html");
 * $template->setParamsArray("constant_value"=>"some constant");
 *  
 *  OR 
 * 
 * $template->setParams(t(new TemplateParams())->set("constant_value","some constant"));
 *
 * </code></pre>
 *
 * Note, that parameters in the template should be casted to string, because they are objects
 * by the nature.
 */
class Template
{
	private 
		/**
		 * @var string
		 * path to the file
		 */
		$path = null,
		/**
		 * @var string
		 * name of the file with template
		 */
		$filename = null,
		/**
		 * @var TemplateParams
		 * Holds params for current template
		 */
		$params = null;

	//{{{ __construct
	/**
	 * @param string real path to the template
	 * @param string template file name
	 */
	function __construct($path,$filename)
	{
		$path = rtrim($path,"/");
		$filename = ltrim($filename,"/");
		if(!file_exists($path."/".$filename))
			throw(new TemplateException("template '$path/$filename' does not exists"));
		$this->path = $path;
		$this->filename = $filename;
		$this->params = new TemplateParams;
	}
	//}}}

	//{{{ setParams
	/** 
	 * Inject early created parms to the 
	 * current template object.
	 *
	 * @param TemplateParams 
	 * @return null
	 */
	function setParams(TemplateParams $p)
	{
		$this->params->merge($p);
	}
	//}}}

	//{{{ setParamsArray
	/**
	 * Creates parameters from 2-dimensional array.
	 * Key is the name of parameter, value is the value to
	 * pass to the template
	 *
	 * @params array with parameters
	 * @return null
	 */
	function setParamsArray($arr)
	{
		foreach($arr as $k => $v)
			$this->params->set($k,$v);
	}
	//}}}

	//{{{ flushVars
	/**
	 * Cleans current parameters.
	 *
	 * @param null
	 * @return null
	 */
	function flushVars()
	{
		$this->params = new TemplateParams;
	}
	//}}}

	//{{{ getHTML
	/**
	 * Passes accumulated parametes into the template file 
	 * as the variable $p, and collects output of this file.
	 *
	 * @param null
	 * @return string evaluated template
	 */
	function getHTML()
	{
		ob_start();
		$p = $this->params;
		include($this->path."/".$this->filename);
		$s = ob_get_contents();
		ob_end_clean();
		unset($this->params);
		$this->params = new TemplateParams;
		return $s;
	}
	//}}}
}
//}}}
