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

// $Id: Language.php 95 2009-05-12 12:12:01Z point $
//

class SingleLanguageProcessor implements iLanguageProcessor
{
	private $current = 0;
	private $currentName;
	function init(){
		$this->currentName = Config::getInstance()->language->single_name;
	}

	function current(){return $this->current;}
	function currentName(){return $this->currentName;}
	function isDefault($lang = null){return $lang === null?true:$lang==$this->currentName;}
	function encodePair($value){ return $value;}
	function getConst($key){ return $key;}
	function getLangList($raw = false){
		$a=array($this->currentName => 0);
		return $raw?$a:array_values($a);
	}

	function getLangName($lang_id = null){
		return $this->currentName;
	}

 	// {{{ getPluralConst0
    /**
     * По заданному числу $n возвращает соотвествующую форму из масива $forms
     *
     * Результирующую строку пропускает через printf, с параметрами следующими за $forms
     *
     * @param int $n
	 * @param array $forms
	 * @param $model = null необходима для совместимости с интефейсом iLanguageProcessor
     * @param .... sprintf argumetns.
     */
	function getPluralConst0($n, $forms, $model = null){
		if (!is_array($forms)) return $forms;
		$f= Language::getPluralForm($n,$this->currentName());
        $str = $forms[$f];
		if (strpos( $str, '%') !== false){
			$args = array_slice(func_get_args(),3);
			$str = vsprintf($str,$args);
		}
        return $str;
    }//}}}

	// {{{ getPluralConst 
	function getPluralConst($n, $key, $model = null){
        return Language::getPluralKey($n,$key);
    }// }}}
}
