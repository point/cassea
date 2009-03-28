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
