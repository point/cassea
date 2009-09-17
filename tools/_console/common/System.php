<?php
class SystemException  extends CasseaException{}

class System{

    CONST LS_DIR = 1;
    CONST LS_FILE = 2;
    CONST LS_ALL = 3;

    static public function ls( $dir, $flag = System::LS_ALL ){
        //$dir = dirname($dir);
        if (!is_dir($dir)) return array();
        $dh  = opendir($dir);
        $list= array();
        while (false !== ($filename = readdir($dh)))
            if ($filename != '.' && $filename != '..'){
                if ( (is_file($dir.'/'.$filename) && $flag & System::LS_FILE)
                    || (is_dir($dir.'/'.$filename) && $flag & System::LS_DIR)
                ) 
                $list[] = $filename;
            }
        closedir($dh);
        return $list;


    }
    static public function is_file($file){ return is_file($file);}
    


}

//print_r(System::ls('.', System::LS_ALL));



