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


//{{{ DatabaseSession
/**
* @author       billy
*/
class DatabaseSession extends SessionEngine
{

    const TABLE = 'user_session';

    //{{{ getServerSession
    /**
    * @return   Object
    */
    public function getServerSession($sid)
    {
		$res = array();
		if($sid)
		{
			$sql = "select * from " . self::TABLE . " where id='" . $sid . 
				"' and time > unix_timestamp() LIMIT 1" ;
			$res = DB::query($sql);
		}
        return (count($res) == 1)?$res[0]:array("id"=>null,"cast"=>null,"ip"=>null);
    }// }}}
    
    //{{{ save
    /**
    */
    public function save($sid, array $params)
    {
		//$params should be array of "key"=>"value". So query will be "  update set `key`='value'  "

		$sql_k = $sql_v = array();
		foreach($params as $k=>$v)
			if(is_string($k))
			{
				$sql_k[] = "`$k`";
				$sql_v[] = "'".Filter::apply($v,Filter::STRING_QUOTE_ENCODE)."'";
			}

		if(empty($sql_k))
			throw new CasseaException("Cannot save session. Data array is empty");

		DB::query("replace into ".self::TABLE. " ( ".implode(", ",$sql_k)." ) values (".
			implode(", ",$sql_v).")");
    }// }}}
    
    //{{{ kill
    /**
    * @return   void
    */
    public function kill($sid)
    {
        $r = DB::query( "delete from " . self::TABLE . ' where id = "'.$sid.'"');
    }// }}}
    
    //{{{ deleteExpired
    /**
    * @return   int
    */
    public function deleteExpired()
    {
        $sql = 'delete from ' . self::TABLE . ' where `time` < unix_timestamp()';
        DB::query($sql);
        return DB::getMysqli()->affected_rows;
    }// }}}
    
}// }}}
