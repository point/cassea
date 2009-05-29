<?php
define( 'UMASK', 18 );
/**
 * Константа определяет сколько раз необходимо 
 * пытаться скопировать папку на применяя рекурсивную функцию
 */
define( 'TRY', 2);
/**
 *  Константа определяет сколько попыток 
 *  дается для рекурсивного окпирования
 */
define( 'MAX_TRY', 10);
/**
 * Класс Файловых маркеров
 */
class FileMarker{
    /**
     *
     */
    var $name = '';

    function FileMarker($name, $path = ''){
        if (substr($path,-1) != '/')
            $path.= '/';
        $this->name = $path.$name.'.marker';
    }

    function create(){
        $h = fopen($this->name, 'w');
        fwrite($h, 0);
        fclose($h);
    }
    
    /**
     * возвращает количестко попцток переписать файл или директорию
     *
     * @return int
     */
    function getTry(){
        return  file_get_contents($this->name);
    }

    /**
     * увеличивает значение заданного маркера
     */
    function inc(){
        $t = $this->getTry();
        $h = fopen($this->name, 'w');
        fwrite($h, $t+1);
        fclose($h);
        return $t;
    }

    function delete(){
        unlink($this->name);
    }

    /**
    * Функция определяет,  является ли 
    * переданный файл маркером 
     */
    function is_marker($name){
        return substr($name,-strlen('.marker')) == '.marker';
        
    }

    /**
    *  Возвращает имя объекта для маркера
    */
    function get_corresp($name){
        return substr($name, 0, -strlen('.marker'));
    }


}

function CopyEx($src, $dest, $show = 0){
//    if (!is_dir($dest)) mkdir($dest, UMASK);
    if (!is_dir($dest)) mkdir($dest);
/*    if ($show == 0) { 
        global $root_path;
        print_pre( str_replace($root_path, "now copy&nbsp;" ,$dest));flush();
        $show--;
    }
 */
    // получаем список файлов.
    $dh  = opendir($src);
    $list = array();
    // массив для маркеров
    $markers = array();
    $marked = array();

    while (false !== ($filename = readdir($dh))) {
        if ($filename != '.' && $filename != '..'){

            if (FileMarker::is_marker($filename))
                $marked[] = FileMarker::get_corresp($filename);
            else
                $list[] = $filename;
        }
    }
    closedir($dh);
       
    // если нет маркеров, то это значит, что данную директорию еще не копировали
    // создаем маркера с try = 0 
    // 
    if (!count($marked)){
        $marked = $list;
        for ($i = 0; $i < count($list); $i++){
            $m = new FileMarker($list[$i],$src);
            $m->create();
        }

    }
    // если есть маркера, то 
    // цикл по маркерам
    for ($i = 0; $i < count($marked); $i++){
        $m = new FileMarker($marked[$i], $src);
        $try = $m->inc();
        if ($try >= MAX_TRY) return false;
        // копируем файлы 
        if (is_file($src.$marked[$i])){
            copy($src.$marked[$i], $dest.$marked[$i]);
            $m->delete();
        }
        else{
            if ($show > 0){
                global $root_path;
                echo ( str_replace($root_path, "", $dest.$marked[$i].'/<br>'));
            }
            $r = CopyEx($src.$marked[$i].'/', $dest.$marked[$i].'/', $show - 1);
            if ($r!== true) return $r;
            $m->delete();
        }
    }
    return true;
}

function UnlinkEx($dir){
    if (is_file($dir)){ return unlink($dir); }

    if (!is_dir($dir)) return true;
    // получаем список файлов.
    $dh  = opendir($dir);
    $res = true;
    while (false !== ($filename = readdir($dh))) {
        if ($filename != '.' && $filename != '..'){
            if (is_file($dir.$filename))
                unlink($dir.$filename);
            if (is_dir($dir.$filename))
                $res = $res && UnlinkEx($dir.$filename.'/');
        }
    }
    closedir($dh);
    $res = $res && @rmdir($dir);
    return $res;
}


/**
* ls  
* 
* @param mixed $dir 
* @return void
*/
function ls($dir){
    if (!is_dir($dir)) return array();
    $dh  = opendir($dir);
    $list= array();
    while (false !== ($filename = readdir($dh))) 
        if ($filename != '.' && $filename != '..')
            $list[] = $filename;
    closedir($dh);
    return $list;
}

function lsEx($dir, &$dirs, &$files, &$unknow){
    $files = array();
    $dirs = array();
    $unknow = array();
    $arr = ls($dir);
    
    foreach ($arr as $f){
        if (is_file($dir.$f))
            $files[] = $f;
        elseif(is_dir($dir.$f))
            $dirs[] = $f;
        else
            $unknow[] = $f;
    }
}

/**
 * Рекурсивно 
 *
 * @return false or array of files
 *
 */

function lsReqursive($dir){
    if (!is_dir($dir)) return false;

    $r = array();
    $d = array();
    $f = array();
    lsEx( $dir, &$d, &$f, &$u);

    for ($i = 0; $i< count($f); $i ++)
        $r[] = $dir.$f[$i];
 
    for ($i = 0; $i < count($d); $i ++){
//        $r[] = $dir.$d[$i];
        array_add($r, lsReqursive($dir.$d[$i].'/'));
    }
    return $r;
}

function array_add(&$array1, $array2){
    for($i = 0; $i< count($array2); $i ++)
        $array1[] = $array2[$i];
}

function trim_basedir($files, $basedir){
    $new = array();
    for( $i = 0; $i < count($files); $i ++ ){
        $new[] = preg_replace('#^'.$basedir.'#', '', $files[$i]);
    }
    return $new;
}


// test
/*
$src = $install_path.'src/core/';
$dest = $root_path;

CopyEx($src, $dest);
*/
?>
