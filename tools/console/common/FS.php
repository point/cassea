<?php
class FS{
    public static function lsExtPhp($dir)
    {
        if (!is_dir($dir)) return array();
        $dh  = opendir($dir);
        $list= array();
        while (false !== ($filename = readdir($dh))) 
            if ($filename != '.' && $filename != '..')
            {
                if((@array_pop(explode('.',$filename)))==='php')
                $list[] = basename($filename,'.php');
            }
        closedir($dh);
        return $list;
    }
    
    public static function create($name,$content=null)
    {
        umask(0);
        $h = fopen($name, 'w');
        if($content===null)
            fwrite($h, 0);
        else
            fwrite($h, $content);
        fclose($h);
    }
    public static function createController($root,$name)
    {
        $mod="class ".$name."{} ?>";
        self::create($root . '/controllers/' . $name . '.php',file_get_contents(dirname(__FILE__) . '/templates/controller.tpl'));
        mkdir($root . '/models/' . $name,0777);
        mkdir($root . '/pages/' . $name,0777);
        self::create($root . '/models/' . $name . '/' . $name . '.php',file_get_contents(dirname(__FILE__) . '/templates/model.tpl') . $mod);
        self::create($root . '/pages/' . $name . '/index.xml',file_get_contents(dirname(__FILE__) . '/templates/page.tpl'));
        
    }

    public static function rCopy($src,$dist) 
    { 
        $dir = opendir($src); 
        if (!is_dir($dist)) @mkdir($dist,0777);
        while(false !== ( $file = readdir($dir)) ) 
        { 
            if (( $file != '.' ) && ( $file != '..' )) 
            { 
                if (is_dir($src . '/' . $file) ) 
                   self::rCopy($src . '/' . $file,$dist . '/' . $file); 
                else 
                    copy($src . '/' . $file,$dist . '/' . $file); 
            } 
        } 
    closedir($dir); 
    }
    
    public static function copyController($root,$src,$name)
    {
        copy($root . '/controllers/' . $src . '.php',$root . '/controllers/' . $name . '.php');
        self::rCopy($root . '/models/' . $src,$root . '/models/' . $name);
        self::rCopy($root . '/pages/' . $src,$root . '/pages/' . $name);
    }
    
    public static function rRem($dir)
    {
        if (is_file($dir)){ return unlink($dir); }
        if (!is_dir($dir)) return true;
        $dh  = opendir($dir);
        $res = true;
        while (false !== ($filename = readdir($dh))) {
            if ($filename != '.' && $filename != '..'){
                if (is_file($dir.'/'.$filename))
                    unlink($dir.'/'.$filename);
                if (is_dir($dir.'/'.$filename))
                    $res = $res && self::rRem($dir.'/'.$filename.'/');
            }
        }
        closedir($dh);
        $res = $res && rmdir($dir);
        return $res;
    }

    public static function removeController($root,$name)
    {
        self::rRem($root . '/controllers/' . $name . '.php');
        self::rRem($root . '/models/' . $name);
        self::rRem($root . '/pages/' . $name);
    }

}
?>
