<?php

class AutoloadException extends Exception{
}

class Autoload{
    private static $dirs = array();
    private static $rd, // root dirs
                    $vd;// vendors dirs

    public static function init(){

        spl_autoload_register('Autoload::load');
        $c=  Config::getInstance();
        self::$rd = $c->root_dir;
        self::$vd = self::$rd.$c->vendors_dir;

    	//require(self::$rd.'/includes/widgets/autoload.php');
    	//require(self::$vd.'/widgets/autoload.php');

    }

    public static function addDir($path){
        $dir = is_file($path)?dirname($file):$path;
        if (!in_array($dir, self::$dirs)) array_unshift(self::$dirs, $dir);
    }

    private static function getVendorPath($name, $name2 = ''){
        return self::$vd.'/'.$name.(empty($name2)?'':'/').$name2;
    }

    /**
     * Подключает autoload.php для указанного вендора.
     *
     */
    public static function addVendor( $name, $name2=''){
        if (is_file($p = self::getVendorPath($name,$name2).'/autoload.php')) require($p);
        else throw new AutoloadException('File autoload.php not found for vendor '.$name.(empty($name2)?'':'/').$name2);
    }


    /**
     * Добавляет директорию вендора в список для поиска классов
     *
     */
    public static function addVendorDir( $name, $name2=''){
        self::addDir(self::getVendorPath($name, $name2));
    }

    public static function load($class){
        foreach(self::$dirs as $d)
            if (is_file($f= $d.'/'.$class.'.php'))
                return require($f);
        print_pre('Class "'.$class.'"\r\n not found.');
        print_pre('Directory List:');
        foreach(self::$dirs as $d) print_prE($d);

        print_prE(debug_backtrace());
        die();

    }

    /**
     * Utils: Return list of directories
     *
     *
     */
    public static function getDirs(){
        return self::$dirs;
    }




}
