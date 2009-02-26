<?php
// {{{ LTC
/**
 * Класс для работы с полями объектов зависимых от языков 
 *
 * @package LTC
 */ 
class LTC{
    
    const TABLE = 'ltc';
    /**
     * Кэш языковых констант
     *
     *
     * @var __CacheLang
     * @access private
     * @static
     */
    private static $__LTCCache = array();
  
    /**
    * Установка значения для объекта для языка
    * @param    int $oid    oid объекта
    * @param    array $values    Массив ключ => значение
    * @param    int $lang_id    Язык контента
    * @return   int Количество вставленных записей
    */
    static public function setVals($oid, $values, $lang_id = 0)//{{{
    {
        if ($lang_id == 0) $lang_id = Language::current();
        if ($lang_id == -1) $langs = Language::getLangList();
        else $langs = array($lang_id);
        if (count ($values) == 0) return 0;
        for ($i = 0; $i < count($langs); $i++){        
            foreach( $values as $key => $value ) {
                $precache2[$langs[$i]][$key] = $value;
                Filter::filter($key, 9);
                Filter::filter($value, 9);
                $precache1[$langs[$i]][$key] = $value;
                DB::query('replace into '.self::TABLE.' (oid, k, v, lang_id ) values ( '.$oid.',"'.$key.'","'.$value.'",'.$langs[$i].')');
           
            }
        }
        self::$__LTCCache[$oid][1] = $precache1;
        self::$__LTCCache[$oid][2] = $precache2;
        return $i;
        
    }//}}}
    
    
    /**
    * Возвращает переменные для заданного объекта
    * 
    * @param    int $oid    Объект
    * @param    int $lang_id    
    * @param    boolean $quote квотить выходящие ключи
    * @return   array
    */
    static public function getVals($oid, $quote = false, $lang_id = 0)//{{{
    {
        if ($lang_id == 0) $lang_id = Language::current();

        if (isset(self::$__LTCCache[$oid][($quote?1:2)][$lang_id])){
            return self::$__LTCCache[$oid][($quote?1:2)][$lang_id];
        }

        if (!is_numeric($oid )) return array();
        $res = DB::query('select k, v from '.self::TABLE.' where oid='.$oid.' and lang_id='.$lang_id);
        if (count($res) == 0 ) return self::$__LTCCache[$oid][($quote?1:2)][$lang_id] = array();
        $r = array();
        foreach($res as $row)
        {
            $k = $row['k'];
            $v = $row['v'];
            if (!$quote){
            //    Filter::unquote_text($k);
            //    Filter::unquote_text($v);                
            }
            $r[$k] = $v;
        }
        self::$__LTCCache[$oid][($quote?1:2)][$lang_id] = $r;
        return $r;            
     }//}}}

	 // For convinience only
	 function getVal($oid,$key,$quote = false, $lang_id = 0)
	 {
		 $l = LTC::getVals($oid,$quote,$lang_id);
         return (isset($l[$key]))?$l[$key]:"";
	 }
    
     /**
    * Удаляет переменные объекта.
    * @param    int $oid    
    * @return  
    */
    function deleteVals($oid)//{{{
    {
        DB::query('delete from '.self::TABLE.' where oid='.$oid);
        if (isset(self::$__LTCCache[$oid]))
            unset(self::$__LTCCache[$oid]);
        return ;

    }//}}}
    
   
    /**
    * Загружает укзанные oidы в местный кэш.
    * @param    array $oids    Массив oid'ов
    * @param    int $lang_id    
    * @return   boolean
    */
    function preload_objects($oids, $lang_id = 0)//{{{
    {
        if (!is_array($oids) || empty($oids)) return;
        if ($lang_id == 0) $lang_id = Language::current();
        $res = DB::query('select oid, k , v from '.self::TABLE.' where oid in ( '.implode(', ', $oids ).') and lang_id='.$lang_id);
        if ( count($res) == 0 ) return 0;
        foreach ($res as $val){
            $oid = $val['oid'];
            $k = $val['k'];
            $v = $val['v'];
        //    Filter::unquote_text($k);
   	    //    Filter::unquote_text($v);
            self::$__LTCCache[$oid][2][$lang_id][$k] = $v;
            self::$__LTCCache[$oid][1][$lang_id][$val['k']] = $val['v'];
        }
    }//}}}

   } // }}}

?>
