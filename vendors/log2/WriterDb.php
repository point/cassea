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
 * This file contains class WriterDB. WriterDB's responsibility is to record log data to a table in database.
 *
 * @author Skai <climbonn@gmail.com>
 * @link http://cassea.wdev.tk/
 * @version $Id: $
 * @package system
 * @since 
 * @see DB
 */

//{{{WriterDB
/**
* WriterDB's responsibility is to record log data to a table in database.
*/
class WriterDb extends WriterAbstract
{
    /**
     * Table for write in DB.
     * @var string
     */
    protected $table;
    
	/**
	 * Keu-value array to mapping event fileds to table fields.
     * @var array('db_field'=>'event_key')
     */
    protected $fields;

	private $stmt;

    //{{{ __construct
    /**
	 * If first parameter $table instance of ConfigBase parse field 'target' and 'fields' in it.
	 * Otherwise expect table name in $table and associative array for mapping 
	 * event fileds to table fields in $fields. 
	 *
	 * @param string $table     
	 * @param array 
     */
	public function __construct($table=null,$fields=null){
		if(is_array($table)){
				$params=$table;
				if(isset($params['target'])) $table=$params['target'];
				if(isset($params['fields']))
					foreach(explode(',',trim($params['fields'])) as $a)
						$fields[strtok($a,':')]=strtok(':');
		}
		if (!isset($table) || !is_array($fields))
			throw new LogException('Incorrect parameters in WriterDB constructor');

		$this->table = $table;
		$this->fields = $fields;
        $this->formatter=new FormatterSimple();
    }//}}}
   
     //{{{ write
    /**
     * Record log data to a database.
     * @param  array $event 
     * @return null 
     */
	public function write($event) {
		if (!is_object($this->stmt)){
			$c = count($this->fields);
			ksort($this->fields);	
			$sql =  'insert '.$this->table.' ( `'.implode('`, `', array_keys($this->fields)).'`)'.
				'values ('.implode(',', array_fill(0,$c,'?')).')';
			$this->stmt = DB::getStmt($sql, str_repeat('s',$c));
		}

		$res =array();
		foreach($this->fields as $key => $val)
			$res[]=isset($event[$val])? $event[$val]:'';

		$this->stmt->execute($res);
    }// }}}
} 
//}}} 
