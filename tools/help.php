<?php
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
