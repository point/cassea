<?php
/*- vim:expandtab:shiftwidth=4:tabstop=4: 
{{{ LICENSE  
* Copyright (c) 2008, Cassea Project
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

class DBException extends Exception
{
    private $query = null;

    function __construct( $message = NULL, $code = null, $query = NULL )
    {
        if ($query !== NULL) $this->query = $query;
        parent::__construct(wordwrap($message,75, "\r\n\t\t" ), $code);
    }

    function __toString(){
        $str = "\r\n\r\n";
        $str.= "Exception:\t".get_class($this)."\r\n";
        if ( $this->query !== NULL )
            $str .= "Query:\t".$this->query."\r\n";

        $str.= "Message:\t".$this->message."\r\n";
        $str.= "Code:\t".$this->code."\r\n";
        $str.= "Line:\t".$this->line."\r\n";
        $str.= "File:\t".$this->file."\r\n\r\n";

        $str.= parent::getTraceAsString();
        return $str;
    }
}


class DB{
  
    const FETCH_ASSOC = 1;
    const FETCH_NUM =   2;
    const FETCH_BOTH =  4;

    const DROP_EMPTY_RESULTSET =    0; 
    const SAVE_EMPTY_RESULTSET =    8; 

    const COMPRESS_RESULTS =        0;
    const DONT_COMPRESS_RESULTS =  16;

    const USE_RESULT =  32;
    const STOR_RESULT =  0;
 

    private static $mysqli;

    // {{{ __construct
    /**
     *
     *
     *
     */
    function __construct( $host, $username, $password, $dbname, $port = NULL, $socket = NULL ){
        if (is_object(self::$mysqli)) return;
        self::$mysqli = new mysqli( $host, $username, $password, $dbname, $port = NULL, $socket = NULL);
        if ( mysqli_connect_errno()) throw ( new DBException(mysqli_connect_error(), mysqli_connect_errno()));
    }// }}}

    // {{{ getMysqli
    /**
     *
     *
     */
    static public function &getMysqli(){
        return self::$mysqli;
    }// }}}

    // {{{ query
    /**
     *
     *
     *
     */
    static public function query( $query, $resulttype = MYSQLI_ASSOC){
        if ( $res = self::$mysqli->query($query) ){
            if (is_object($res)){
                $data = array();
                for ($i = 0; $i < $res->num_rows; $i++)
                    $data[] = $res->fetch_array($resulttype);
                $res->close();
                return $data;
            }
            return $res;
        }
        else
            throw (new DBException(self::$mysqli->error,self::$mysqli->errno,$query));
    }// }}}

    // {{{ multiQuery
    /**
     *
     *
     */
    //static public function multiQuery( $query, $compress = true,  $data_process = DB::STORE_RESULT,  $resulttype = MYSQLI_ASSOC){
    static public function multiQuery( $query, $flags = 0){
        // flag processing
        
        $data_process ='mysqli_store_result';
        if ($flags & DB::USE_RESULT) $data_process ='mysqli_use_result';

        $resulttype = MYSQLI_ASSOC;
        if ($flags & DB::FETCH_NUM ) $resulttype = MYSQLI_NUM;
        else if ($flags & DB::FETCH_BOTH ) $resulttype = MYSQLI_BOTH;

        $compress = true;
        if ( $flags & DB::DONT_COMPRESS_RESULTS )  $compress = false;;

        $skipNull = true;
        if ( $flags & DB::SAVE_EMPTY_RESULTSET ){
            // Если указано DB::SAVE_EMPTY_RESULTSET,
            // то необходимо отменить компрессию результатов, 
            // иначе пустые запросы не отобразятся в результирующем массиве.
            $skipNull = false;
            $compress = false;
        }



        $mysql = &self::$mysqli;
        $i = 0;
        if ( $res = $mysql->multi_query($query) ){
            $data = array();
            do{
                $res =  call_user_func($data_process, $mysql);
                //echo $i." ==> ";var_dump($res);

                if ( $res ){
                    $subdata = array();
                    $sdcount = 0;
                    while( $tr =  $res->fetch_array($resulttype) )
                        $subdata[$sdcount ++]= $tr;
                    $res->free();
                    $data[] = $subdata;
                }
                // store_result возвращает FALSE для запросов без выбоки
                // запросы без результатов
                elseif ( $mysql->errno == 0 && !$skipNull){ 
                    $data[] = null;
                }
               $i ++;
            }while(  $mysql->next_result());

            if ( $mysql->errno != 0){
                throw (new DBException(self::$mysqli->error,self::$mysqli->errno,"subquery ".($i)."\r\n".$query));
            }

            //print_r($data);
            if ($compress){
                $cdata = array();
                for ($i =0, $c = count($data);  $i < $c; $i++){
                    if (is_array($data[$i])){
                        for ($j = 0, $cc = count($data[$i]); $j < $cc; $j++ )
                            $cdata[] =$data[$i][$j];
                    }
                }
                return $cdata;
            }

            return $data;   
        }
        else{ 
             throw (new DBException(self::$mysqli->error,self::$mysqli->errno,"subuery ".($i)."\r\n".$query));
        }
    }// }}}

    // {{{ clearResultset
    /**
     * 
     *
     *
     */
    static public function clearResultset($result = null){
        if (is_object($result)) $result->free();    
        while(self::$mysqli->next_result()){
            if($result = self::$mysqli->store_result()){
                $result->free();
            }
        }
    } // }}}
}
?>
