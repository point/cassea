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
 * This file contains
 *
 * @author point <alex.softx@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id:$
 * @package system
 * @since 
 */

//{{{ LTC
class LTC
{
	/**
	 * Table to store values
	 */
    const TABLE = "ltc";

	public static $populate_on_insert = true;
	public static $populate_on_delete = true;

	static function insert($key, $value, $language = null) 
	{
		if($language === null)
			$language = Language::current();
		$key = Filter::apply($key,Filter::STRING_QUOTE_ENCODE);
		$value = Filter::apply($value, Filter::STRING_QUOTE_ENCODE);

		$insert_id = DB::query("insert into ".self::TABLE." set language='{$language}', `key`='{$key}', value='{$value}'");
		
		if(self::$populate_on_insert === true)
		{
			$stmt = DB::getStmt("insert into ".self::TABLE." set id=?, language=?, `key`=?, value=?",'isss');
			foreach(Language::getLangList() as $c_lang)
				if($c_lang != $language)
					$stmt->execute(array($insert_id, $c_lang, $key,$value));
		}
		return $insert_id;
	}

	static function set($id, $key,$value,$language = null)
	{
		if($language === null)
			$language = Language::current();
		$id = Filter::apply($id,Filter::INT);
		$key = Filter::apply($key,Filter::STRING_QUOTE_ENCODE);
		$value = Filter::apply($valuem, Filter::STRING_QUOTE_ENCODE);

		DB::query("update ".self::TABLE." set value='{$value}' where id='{$id}' and language='{$language}' and `key`='{$key}' ");
	}
	static function get($id, $key, $language = null)
	{
		if($language === null)
			$language = Language::current();
		$id = Filter::apply($id,Filter::INT);
		$key = Filter::apply($key,Filter::STRING_QUOTE_ENCODE);

		$r = DB::query("select value from ".self::TABLE." where id='{$id}' and language='{$language}' and `key`='{$key}' limit 1");
		if(count($r))
			return $r[0]['value'];
	}

	static function getForAllLaguages($id,$key)
	{
		$id = Filter::apply($id,Filter::INT);
		$key = Filter::apply($key,Filter::STRING_QUOTE_ENCODE);

		$ret = array();
		foreach(DB::query("select language, value from ".self::TABLE." where id='{$id}' and `key`='{$key}'") as $v)
			$ret[$v['language']] = $v['value'];
		return $ret;
	}

	static function remove($id,$key,$language = null)
	{
		if($language === null)
			$language = Language::current();
		$id = Filter::apply($id,Filter::INT);
		$key = Filter::apply($key,Filter::STRING_QUOTE_ENCODE);

		DB::query("delete from ".self::TABLE." where id='{$id}' and `key`='{$key}' ".
			self::$populate_on_delete!==true?" and language='{$language}' limit 1":"");
	}
}
// }}}
//require("Boot.php");
//Boot::setupLanguage();
?>
