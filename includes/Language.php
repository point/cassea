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

class LanguageException extends CasseaException {}

interface iLanguageProcessor{
	public function init();
	public function current();
	public function currentName();
	public function isDefault($lang = null);
	public function encodePair($value);
	public function getConst($key);
	public function getLangList($raw = false);
	public function getPluralConst($n, $key, $model = null);
}

interface iUpdatableLanguageProcessor{
	public function setLangConst($key, $val, $model='common', $lang);
	public function updateLangConst($oldkey, $key, $val, $model='common', $lang=0);
	public function deleteLangConst($key, $model ='common');
	public function getModelConst($model='common', $lang, $ruler);
	public function getModelConstCount($model='common', $lang=0);
    public function getLangName($lang_id = null);
}


class Language{
	private static $processor;
	private static $messages = array();

	static function init(){
		$processor = Config::getInstance()->language->processor;
		$classname = nameToClass($processor).'LanguageProcessor';
		Autoload::addVendor('language', $processor);
		self::$processor = new $classname();
		self::$processor->init();
	}


	static function message($class, $message){
		if(!isset(self::$messages[$class])){
			global $__l;
			unset($__l); $__l=array();
			$file = Config::getInstance()->root_dir.Config::getInstance()->data_dir.'/messages/'.$class.'.'.self::currentName().'.php';
			if (!is_file($file)) throw(new LanguageException('Message file  '.$file.' not found.'));
			require_once($file);
			self::$messages[$class] = $__l;
			unset($__l);
		}
		$val = isset(self::$messages[$class][$message])?self::$messages[$class][$message]:$message;
		$data =  array_slice(func_get_args(),2);
		return vsprintf($val, $data);
	}

	static function isDefault() { return self::$processor->isDefault();}
	static function current(){ return self::$processor->current();}
	static function currentName(){return self::$processor->currentName();}
	static function encodePair($value){ return self::$processor->encodePair($value);}
	static function getConst(){
		$args = func_get_args();
        return call_user_func_array(array(self::$processor,'getConst'), $args);
	}
	static function getLangList($raw = false){return self::$processor->getLangList($raw);}

	static function getPluralConst($n, $keys, $model = null ){
		$args = func_get_args();
        return call_user_func_array(array(self::$processor,'getPluralConst'), $args);
	}
    // {{{ getPluralForm
    /**
     * В соотвествии с числом $n и языком выбирает соответствующую форму множественного числа.
     *
     * Правила получений формы и дополнительная информация: 
     * http://www.gnu.org/software/gettext/manual/gettext.html.gz#Plural-forms
     * http://translate.sourceforge.net/wiki/l10n/pluralforms
     *
     *
     * @param int $n множественное число
     * @param string{2} $lang двухбуквенное сокращение языка
     * @return int номер формы
     */
    static function getPluralForm($n, $lang){
		$n = abs($n);
        if (!is_numeric($n) || $n < 0 ) return 0;
        switch($lang){
        // Croatian, Serbian, Russian, Ukrainian
        case 'hr':
        case 'sr':
        case 'ru':
        case 'ua':
            $f = ($n%10 == 1 && $n%100 != 11) ? 0 :
            ( ($n%10 >= 2 && $n %10 <= 4 && ($n % 100 < 10 || $n %100 >=20 ) )? 1: 2);
        break; 
        // Danish, Dutch, English, Faroese, German, Norwegian, Swedish
        // Estonian, Finnish
        // Greek
        // Hebrew
        // Italian, Portuguese, Spanish 
        case 'en':
        case 'de':
            $f = ($n != 1)?1:0;
            break;
        default: $f = 0;
        }
        return $f;
    }//}}} '

	// {{{ Admin Proxy
	static function getLangName($lang_id){
		if (self::$processor instanceof iUpdatableLanguageProcessor)
			return self::$processor->getLangName($lang_id);
	}
	static function setLangConst($key, $val, $model='common', $lang){
		if (self::$processor instanceof iUpdatableLanguageProcessor)
			return self::$processor->setLangConst($key, $val, $model, $lang);
	}
	static function updateLangConst($oldkey, $key, $val, $model='common', $lang=0){		
		if (self::$processor instanceof iUpdatableLanguageProcessor)
			return self::$processor->updateLangConst($oldkey, $key, $val, $model, $lang);
	}

	static function deleteLangConst($key, $model ='common'){
		if (self::$processor instanceof iUpdatableLanguageProcessor)
			return self::$processor->deleteLangConst($key, $model);
	}
	static function getModelConst($model='common', $lang, $ruler){
		if (self::$processor instanceof iUpdatableLanguageProcessor)
			return self::$processor->getModelConst($model, $lang, $ruler);
	}
	static function getModelConstCount($model='common', $lang=0){
		if (self::$processor instanceof iUpdatableLanguageProcessor)
			return self::$processor->getModelConstCount($model, $lang);
		
	}// }}}"
}

