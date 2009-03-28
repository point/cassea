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
require("../includes/DB.php");
require("../includes/LTC.php");
require("../includes/Language.php");
require("../includes/Filter.php");
DB::init('','intvideo','intvideo','intvideo');
function selector($path)
{
    global $files;
    if (is_dir($path))
		foreach(glob($path.'/*') as $p) 
            if (is_dir($p))
            {
                echo "\n Controller  ".basename($p)."\n";
                selector($p);
            }
            elseif (!preg_match("/~$/",$p))
               {
                   
                   $files[basename(dirname($p))][substr(basename($p),0,strpos(basename($p),".xml"))]=1;
                   $req=DB::query("SELECT oid  FROM help WHERE controller='".basename(dirname($p))."' AND page='".substr(basename($p),0,-4)."'");
                   if (!$req && !preg_match('/internal\s*=\s*[\'"`]\s*1\s*[\'"`]/',file_get_contents($p,null,null,0,100)))
                    {
                        $r = DB::query('SELECT f_get_next_oid() AS `oid`' );
                        $oid = $r[0]['oid'];
                        DB::query("INSERT INTO help (oid,controller, page) VALUES($oid,'".basename(dirname($p))."','".substr(basename($p),0,strpos(basename($p),".xml"))."')"); 
                        $ltcvals=array('content'=>'text text');
                        LTC::setVals($oid,$ltcvals,-1);
                    }
                   elseif($req && preg_match('/internal\s*=\s*[\'"`]\s*1\s*[\'"`]/',file_get_contents($p,null,null,0,100)))
                    {
                        DB::query("DELETE FROM help WHERE controller='".basename(dirname($p))."' AND page='".substr(basename($p),0,strpos(basename($p),".xml"))."'");
                        LTC::deleteVals($req[0]['oid']);
                    }
                
               }    
}
$files=array();
selector("../pages");
print_r($files);
$page=DB::query("SELECT * FROM help ");
foreach ($page as $a)
    if (!isset($files[$a['controller']][$a['page']]))
    {
        DB::query("DELETE FROM help WHERE controller='".$a['controller']."' AND  page='".$a['page']."'");
        LTC::deleteVals($a['oid']);
    }    

?>
