<?php

Console::initCore();

class CmdEnv extends Command{

    function CmdList(){
        io::out('~WHITE~Current env~~~: '.($env = EnvManager::getCurrent()));
        io::out();
        IO::OUt('~WHITE~Avaible enviroments:~~~');
        foreach (EnvManager::envList() as $e )
            if($e != $env) io::out('    '.$e);
    }
}

class EnvManager{

    static public function getCurrent(){
        $file =file_get_contents(Config::get('ROOT_DIR').'/config/config.ini');
        if (preg_match('#^\s*\[\s*config\s*:\s*(\S+)\s*\]\s*$#m', $file, $m)) return $m[1];
        return false;
    } 

    static public function envList(){
        if (preg_match_all('#^\s*\[\s*(\S+)\s*:\s*base\s*\]\s*$#m',
            file_get_contents(Config::get('ROOT_DIR').'/config/config.ini'), $m))
            return $m[1];
        else return false; 
    }
    
}
