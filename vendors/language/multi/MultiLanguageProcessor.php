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

// {{{ MultiLanguageProcessor
class MultiLanguageProcessor implements iLanguageProcessor, iUpdatableLanguageProcessor 
{
    const LANG_CONST_TABLE = 'langs';
    const LANGUAGE_TABLE = 'language';
    private static $langs_cache = array();
    private static $const_cache = array();
    private static $current;

    // {{{ init
    public function init(){
        if (Config::getInstance()->language->cache_langs)
            self::$langs_cache = Storage::create('__Language::list__');
        if (Config::getInstance()->language->cache_consts)
            self::$const_cache = Storage::create('__Language::consts__');

		if (!isset(self::$langs_cache['__default__']) || !isset(self::$langs_cache['__list__']) ){
			try{
				$r = DB::query('select * from '.self::LANGUAGE_TABLE); 
			}catch (DBException $e){
				if ($e->getCode() == 1146) throw new LanguageException('Table "'.self::LANGUAGE_TABLE.'" not exists.', 1);
				throw $e;
			}
            $list = array();
            for ($i = 0, $c = count($r); $i < $c;  $i++ ){
                $cl = $r[$i];
                $list[$cl['short_name']] = $cl['id'];
                self::$langs_cache[$cl['short_name']] = $cl['id'];
                if ($cl['default']) self::$langs_cache['__default__'] = $cl['id'];
            }
            self::$langs_cache['__list__'] = $list;
        }
		if (count(self::$langs_cache['__list__']) == 0) throw new LanguageException('There isn\'t langauges (rows) in table "'.self::LANGUAGE_TABLE.'"',2);
		if (!isset(self::$langs_cache['__default__'])) throw new LanguageException('Default language is not defined in table "'.self::LANGUAGE_TABLE.'"',3);
		self::determine();
    }// }}}

    // {{{ determine
    private static function determine(){
        $GETLang = isset($_GET['__lang'])? $_GET['__lang']: false;
        if (isset(self::$langs_cache[$GETLang])) self::$current = self::$langs_cache[$GETLang];
        elseif(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && is_string($_SERVER['HTTP_ACCEPT_LANGUAGE']))
        {
            $languages = strtolower(Filter::filter($_SERVER['HTTP_ACCEPT_LANGUAGE'],Filter::STRING_QUOTE_ENCODE));
            //$languages = 'ru;q=0.5, fr-ch;q=0.3, da, en-us;q=0.8, en;q=0.5, fr;q=0.3';
            $languages = str_replace( ' ', '', $languages );
            $languages = explode( ",", $languages );
            foreach($languages as $l ){
                if (strpos($l,';')) list($ln, $q) = explode(';', $l);
                else list($ln, $q) = array($l, 'q=1');
                $ll[substr($ln,0, 2)] = 0.0 + substr($q, 2);
            }
            asort($ll, SORT_NUMERIC);
            foreach($ll as $l => $q) if (isset(self::$langs_cache[$l])) self::$current = self::$langs_cache[$l];
        }
        if (is_null(self::$current)) self::$current = self::$langs_cache['__default__'];
    }//}}}
    
    // {{{ current
    public function current(){
        return self::$current;
    }// }}}

    // {{{ currentName
    public function currentName(){
        return $this->getLangName(self::$current);
	}// }}}

	// {{{ isDefault
	public function isDefault($lang = null){
		return (is_null($lang)?self::$current:$lang)  == self::$langs_cache['__default__']; 
	}// }}}

	// {{{ encodePair "
	/**
	 *
    */
    public function encodePair( $value ){
		if(strpos($value,'{') !== false){
			$value =  
            preg_replace_callback("/\{(\w+)\.(\w+)(\.(.+))*\}/",
                array($this,'encodeConst'), $value);
        }
        return $value;
    }// }}}

    // {{{ encodeConst
	private function encodeConst($matched){
		if (isset($matched[4])){ 
				// TODO вспомнить и записать для чего это сделано.
                $args = array($matched[2], $matched[1]);
                $args = explode('.', $matched[4]);
                array_unshift($args,$matched[2], $matched[1] );
                return call_user_func_array(array($this, 'getConst'), $args);
        }
        return $this->getConst($matched[2], $matched[1]);
    }// }}}

    // {{{ getConst
	public function getConst($key, $model = null){
		if(is_null($model)) $model = 'common';
        $lang = $this->current();
        $cacheKey = $model.'.'.$key.'.'.$lang;
		unset(self::$const_cache[$cacheKey]);
        if (isset(self::$const_cache[$cacheKey])) $val = self::$const_cache[$cacheKey];
		else{
			static $stmt;
            if (!is_object($stmt)) $stmt = DB::getStmt('select `v` from '.self::LANG_CONST_TABLE.' where `lang_id`=? AND  `package`=? AND `k`=?','iss');
            $r = $stmt->execute(array($lang,$model,$key));
            if (count($r) == 1)
                $val =  self::$const_cache[$cacheKey] = $r[0]['v'];
            else 
                return self::$const_cache[$cacheKey] = '{'.$model.'.'.$key.'}';
        } 
		if (strpos( $val, '%') !== false){
			$args = array_slice(func_get_args(),2);
			$val = count($args)?vsprintf($val, $args):$val;
		}
        return $val;
    }// }}}

	// {{{ getPluralConst
    /**
     * 
     *
     * Пример:
     * <code>
     *  Language::getPluralConst($i, 'video_count', 'video', $i);
     * </code>
     * последний аргумент функции $i будет передан в функцию Language::getConst 
     * в качестве аргументя для подстановки.
     *
     * Константы для обозначения множественного числа имееют суффикс вида "-[число]",
     * где число обозначает форму.
     * В примере значения языковых констант такие:
     * video_count-0 = %s ролик (1, 51 ролик)
     * video_count-1 = %s ролика (2, 24 ролика)
     * video_count-2 = %s роликов  (10, 100 роликов)
     * 
     * Для различных языков количество форм различное, это необходимо 
     * учитывать при локализации.
     */
	function getPluralConst($n, $key, $model = null){
		//if (is_array($key)) $key = array_shift($key);
		if (is_null($model)) $model = 'common';
        $f= Language::getPluralForm($n,$this->currentName());
        $args = array_slice(func_get_args(),1); $args[0] = $key.'-'.$f;
        return  call_user_func_array(array($this,'getConst'), $args);
    }// }}}

    // {{{ ===== Admin ==== 
    // }}}

    // {{{ getLangList
    public function getLangList($raw = false){
        if ($raw) return self::$langs_cache['__list__'];
        return array_values(self::$langs_cache['__list__']);
    }
	// }}}

    // {{{ getLangName
    public function getLangName($lang_id = null){
        $la = array_flip(self::$langs_cache['__list__']);
        return  isset($la[$lang_id])?$la[$lang_id]:null;
    }
    // }}}

    // {{{ getModelConst
    public function getModelConst($model='common', $lang, $ruler){
        $r = DB::query("select `k`, `v` from ".self::LANG_CONST_TABLE." where  `package`='".$model."' AND `lang_id`=".$lang."   ORDER by `k` LIMIT ".$ruler['from'].",".$ruler['limit']);
        if (empty($r)) return '';
        foreach($r as $k => $v)
            $ret[$v['k']] = self::$const_cache[$model.'.'.$v['k'].'.'.$lang] = $v['v'];
        return $ret;
    }// }}}

    // {{{ setLangConst
    public function setLangConst($key, $val, $model='common', $lang){
        $r = DB::query('replace into '.self::LANG_CONST_TABLE.' (`k`, `v`, `package`, `lang_id`) VALUES ("'.$key.'", "'.$val.'", "'.$model.'" ,'.$lang.')');
        self::$langs_cache[$model.'.'.$key.'.'.$lang] = $val;
    }// }}}

    // {{{ updateLangConst
    public function updateLangConst($oldkey, $key, $val, $model='common', $lang=0){
        if ( $oldkey != $key ){
            $r = DB::query('delete from '.self::LANG_CONST_TABLE.' where lang_id=' .$lang.' AND `k`="'.$oldkey.'" AND `package`="'.$model.'"');
            unset(self::$langs_cache[$model.'.'.$oldkey.'.'.$lang]);
        }
        elseif(isset(self::$langs_cache[$model.'.'.$key.'.'.$lang]) && self::$langs_cache[$model.'.'.$key.'.'.$lang] == $val ) return;
        DB::query('replace into '.self::LANG_CONST_TABLE.' (`k`, `v`, `package`, `lang_id`) VALUES ("'.$key.'", "'.$val.'", "'.$model.'" ,'.$lang.')'); 
        self::$langs_cache[$model.'.'.$key.'.'.$lang] = $val;
    }// }}}

    // {{{ deleteLangConst
    public function deleteLangConst($key, $model ='common'){
        $r = DB::query('delete from '.self::LANG_CONST_TABLE.' where package="' .$model.'" AND `k`="'.$key.'"');
        foreach(self::getLangList(true) as $name => $id) unset(self::$langs_cache[$model.'.'.$key.'.'.$id]) ;
    }// }}}

    // {{{ getModelConstCount
    public function getModelConstCount($model='common', $lang=0){
        if ($lang == 0) self::current();
        $r = DB::query("select count(*) as `c` from ".self::LANG_CONST_TABLE." where  `package`='".$model."' AND `lang_id`=".$lang);
        return $r[0]['c'];
    }// }}}

}//}}}

