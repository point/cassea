<?php
// {{{ Language

/**
 * Статический класс для внедрения мультиязычности на сайте 
 *
 * @package Language
 */ 
class Language{
    /**
     * Кэш языковых констант
     *
     *
     * @var __CacheLang
     * @access private
     * @static
     */
    const LANG_CONST_TABLE = 'langs';
    const LANGUAGE_TABLE = 'language';
    private static $__cacheLang;
    public static $current_language;
    public static $current_language_name;

    // {{{ getLangConst
    /**
     * По ключу и id языка получаем языковую константу.
     *
     *
     * @param int $lang_id id языка
     * @return void
     */
	public static function getLangConst($key, $package='common', $lang_id = -1){
		if($lang_id == -1)
			$lang_id = self::$current_language;
        if (empty(self::$__cacheLang[$lang_id][$package][$key])){
            //$r = DB::getStmt('select `v` from '.self::LANG_CONST_TABLE.' where `lang_id`='.$lang_id.' AND  `package`="'.$package.'" AND `k`="'.$key.'"');
            $r = DB::getStmt('select `v` from '.self::LANG_CONST_TABLE.' where `lang_id`=? AND  `package`=? AND `k`=?','iss')->execute(array($lang_id,$package,$key));
            if (empty($r)) return '';
            self::$__cacheLang[$lang_id][$package][$key] = $r[0]['v'];
        } 
        return self::$__cacheLang[$lang_id][$package][$key];    
    }// }}}

    /** {{{ validate
    * Проверяет существование запрашимаего языка, если такового не имеется, возвращаем язык по умолчанию
    * 
    * @param int $lang_id - id языка
    * @return int 
    */
	// DEPRECATED
	/*public static function validate($lang_id=0){
        if (!is_numeric($lang_id)) return self::getDefault();
        $r = DB::query('select id from '.self::LANGUAGE_TABLE.' where id='.$lang_id);
        if (empty($r[0]['id']))
            return self::getDefault();
		return $r[0]['id'];
	}*/
	// }}}
  
    /** {{{ getLangIdByName
    * Получаем id языка по короткому названию
    * 
    * @param string $lang_name - короткое название языка
    * @return int 
    */
    public static function getLangIdByName($lang_name){
        if (empty($lang_name)) return null;
        $r = DB::query('select id from '.self::LANGUAGE_TABLE.' where short_name="'.Filter::filter($lang_name,Filter::STRING_QUOTE_ENCODE).'"');
        return empty($r[0]['id'])?null:$r[0]['id'];
    }
	// }}}

    /** {{{ getLangNameById
    * Получаем id языка по короткому названию
    * 
    * @param string $lang_name - короткое название языка
    * @return int 
    */
    public static function getLangNameById($lang_id){
        if (empty($lang_id)) return null;
        $r = DB::query('select short_name from '.self::LANGUAGE_TABLE.' where id='.$lang_id);
        return empty($r[0]['short_name'])?null:$r[0]['short_name'];
    }
	// }}}

   /** {{{ getDefaultLangNameById
    * Получаем id языка по короткому названию
    * 
    * @param string $lang_id
    * @return string
    */
    public static function getDefaultLangName(){
        if (empty(self::$current_language)) return null;
        $r = DB::getStmt('select short_name from '.self::LANGUAGE_TABLE.' where id=?','i')->execute(array(self::$current_language));;
		Language::$current_language_name = empty($r[0]['short_name'])?null:$r[0]['short_name'];
    }
	// }}}


    /** {{{ getDefault
    * Возвращает язык по умолчанию
    * 
    * 
    * @return int 
    */
    public static function getDefault(){
		static $__default = null;
		if(isset($__default)) return $__default;
        $r = DB::query('select id from '.self::LANGUAGE_TABLE.' where `default`="1"');
        return $__default = (count($r))?$r[0]['id']:null ;
    }
	// }}}
 
    /** {{{ getLangList
    *
    * Получаем список доступных языков 
    * 
    *  @return array
    */
    public static function getLangList(){
        $l = DB::query('select `id` from '.self::LANGUAGE_TABLE);
        for($i = 0, $c = count($l); $i < $c; $i++)
            $ret[$i] = $l[$i]['id'];
        return $ret;
    }
	// }}}
    
    /** {{{ currentLanguage
	* Gets language name taken from current user
	* @return language name needed to include language files
	*/
	// DEPREACATED
	// use Language::$current_language
    /*public static function currentLanguage(){
        return self::getDefault();
	}*/
	// }}}

    // {{{ setLangConst
    /**
     * Создание новой языковой константы 
     *
     * @param int $key ключ константы
     * @param int $val значение константы
     * @param int $lang_id id языка
     * @return void
     */
    public static function setLangConst($key, $val, $package='common', $lang_id=0){
        $r = DB::query('replace into '.self::LANG_CONST_TABLE.' (`k`, `v`, `package`, `lang_id`) VALUES ("'.$key.'", "'.$val.'", "'.$package.'" ,'.$lang_id.')');
        self::$__cacheLang[$lang_id][$package][$key] = $val;
    }// }}}
    
	// {{{ encodePair
    /**
    * Function conver string like {model.const} to language constant
    *
    * If language constant not found post debug message
	*
    */
	public static function encodePair( $value ){
		if(strpos($value,'{') !== false)
			return 
			preg_replace_callback("/\{(\w+)\.(\w+)\}/",
			create_function('$m','$lc = Language::getLangConst($m[2],$m[1],Language::$current_language);return empty($lc)?"{".$m[1].".".$m[2]."}":$lc;'),
			$value);
		else return $value;
    }// }}}

	// {{{ getEnableLang
    /**
    * 
    * Из списка языков возвращает существующий первый 
    * 
    */
    public static function getEnableLang(array $langs){
        for ($i = 0; $i < count($langs); $i++){
            $r = DB::query('select id from '.self::LANGUAGE_TABLE.' where short_name="'.$langs[$i].'"');    
            if (!empty($r[0]['id'])) return $r[0]['id'];
        }
        return  self::getDefault();

    }///}

    // {{{ getLangConst
    /**
     * По ключу и id языка получаем языковую константу.
     *
     *
     * @param int $lang_id id языка
     * @return void
     */
	public static function getModelLangConst($package='common'){
        $r = DB::query("select `k`, `v`, `lang_id` from ".self::LANG_CONST_TABLE." where  `package`='".$package."' ORDER by `k`");
        if (empty($r)) return '';
        foreach($r as $k => $v)
            $ret[$v['k']][$v['lang_id']] = $v['v'];
        return $ret;
    }// }}}
   
    // {{{ updateLangConst
    /**
     * Обновить языковую константу
     *
     * @param int $key ключ константы
     * @param int $val значение константы
     * @param int $lang_id id языка
     * @return void
     */
    public static function updateLangConst($oldkey, $key, $val, $package='common', $lang_id=0){
        $r = DB::query('delete from '.self::LANG_CONST_TABLE.' where lang_id=' .$lang_id.' AND `k`="'.$oldkey.'" AND `package`="'.$package.'"');
        $r = DB::query('replace into '.self::LANG_CONST_TABLE.' (`k`, `v`, `package`, `lang_id`) VALUES ("'.$key.'", "'.$val.'", "'.$package.'" ,'.$lang_id.')');
    }// }}}

    // {{{ deleteLangConst
    /**
     * Удалить языковую константу
     *
     * @param int $key ключ константы
     * @param int $val значение константы
     * @param int $lang_id id языка
     * @return void
     */
    public static function deleteLangConst($key, $package='common'){
        $r = DB::query('delete from '.self::LANG_CONST_TABLE.' where package="' .$package.'" AND `k`="'.$key.'"');
    }// }}}


} // }}}

?>
