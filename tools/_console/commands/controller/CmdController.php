<?php

class cmdController extends Command{
    private $root_dir;


    public function __construct( $workingDir = '.', $info, $commandsSeq = array()){
        parent::__construct( $workingDir, $info, $commandsSeq);
        Console::initCore();
        $this->root_dir   = Config::get('ROOT_DIR');
        $this->models_dir = Config::get('models_dir');
    }


    // {{{ cmdLs
    public function cmdLs(){
        io::out('~WHITE~Controllers list~~~:');
        $r = glob($this->root_dir.'/controllers/*.php');
        foreach($r as $c) io::out("    ".basename($c,'.php'));
    }// }}}

    // {{{ cmdList
    public function cmdList(){ $this->cmdLs(); }// }}}

    // {{{ cmdAdd
    //
    public function cmdAdd(){
        $name = ArgsHolder::get()->shiftCommand();

        if ( $name === false )
            return io::out('Incorrect param count.', IO::MESSAGE_FAIL);
        if ( strlen($name) <= 3 || strpos($name, '.') || strpos($name, '/'))
            return io::out('Controller name must be least 3 chars and can\'t contain ".", "\\" and "/".', IO::MESSAGE_FAIL);


        
        if($sourceControllers=ArgsHolder::get()->getOption('copy')) 
            return $this->copyController($sourceControllers,$name);

        return $this->createController($name);
    }// }}}

    // {{{ createController
    //
    public function createController($name)
    {
        IO::out('~WHITE~Checking:~~~');

        if (file_exists($this->root_dir.'/controllers/'.$name.'.php'))
            return io::out( 'Controller with name ~WHITE~'.$name.'~~~ exist!',IO::MESSAGE_FAIL);
        io::done('    controllers ');
        
        if (file_exists($this->root_dir.$this->models_dir.'/'.$name))
            return io::out( 'Model with name ~WHITE~'.$name.'~~~ exist!',IO::MESSAGE_FAIL);
        io::done('    models');

        $pages = Config::get('xmlpages_dir');
        if (file_exists($this->root_dir.'/'.$pages.'/'.$name))
            return io::out( 'Pages for model with name ~WHITE~'.$name.' ~~~ exist!',IO::MESSAGE_FAIL);
        io::done('    pages');

        IO::out('~WHITE~Creating:~~~');
        // TODO models_dir
        if (mkdir($this->root_dir . $this->models_dir. '/' . $name, 0775)) IO::done("    $this->models_dir / " . $name);
        else return IO::out("Error while creating directory $this->models_dir / " . $name, IO::MESSAGE_FAIL);
        if ( copy(dirname(__FILE__) . '/templates/autoload.tpl', $this->root_dir . $this->models_dir . '/' . $name . '/autoload.php') ) io::done("    $this->models_dir /".$name.'/autoload.php');
        else return IO::out('Error while creating '.$this->models_dir.'/'.$name.'/autoload.php', IO::MESSAGE_FAIL);
        // TODO xmlpages_dir
        if ( mkdir($this->root_dir . '/pages/' . $name,0775))  IO::done('    /pages/' . $name);
        else return IO::out('Error while creating directory /pages/' . $name, IO::MESSAGE_FAIL);

        if ( copy(dirname(__FILE__) . '/templates/controller.tpl', $this->root_dir . '/controllers/' . $name . '.php') )io::done('    /controllers/' . $name . '.php');
        else return IO::out('Error while creating /controllers/' . $name . '.php', IO::MESSAGE_FAIL);
    }// }}}

    // {{{ copyController
    private function copyController($src,$name)
    {
        IO::out('~WHITE~Checking:~~~');

        if (file_exists($this->root_dir.'/controllers/'.$name.'.php'))
            return io::out( 'Controller with name ~WHITE~'.$name.'~~~ exist!',IO::MESSAGE_FAIL);
        io::done('    controllers ');
        
        if (file_exists($this->root_dir.$this->models_dir.'/'.$name))
            return io::out( 'Model with name ~WHITE~'.$name.'~~~ exist!',IO::MESSAGE_FAIL);
        io::done('    models');

        $pages = Config::get('xmlpages_dir');
        if (file_exists($this->root_dir.'/'.$pages.'/'.$name))
            return io::out( 'Pages for model with name ~WHITE~'.$name.' ~~~ exist!',IO::MESSAGE_FAIL);
        io::done('    pages');

        IO::out('~WHITE~Checking source:~~~');

        if(!file_exists($this->root_dir.'/controllers/'.$src.'.php'))
            return io::out( 'Controller with name ~RED~'.$src.'~~~ not exist ',IO::MESSAGE_FAIL);
        io::done('    controller ');

        if (!file_exists($this->root_dir.$this->models_dir.'/'.$src))
            io::out( 'Model with name ~RED~'.$src.'~~~ not exist!',IO::MESSAGE_WARN);
        io::done('    model ');
       
        if (!file_exists($this->root_dir.'/pages/'.$src))
            return io::out( 'Pages for model with name ~RED~'.$src.'~~~ not exist!',IO::MESSAGE_FAIL);
        io::done('    page ');

        IO::out('~WHITE~Copying:~~~');
        copy($this->root_dir . '/controllers/' . $src . '.php',$this->root_dir . '/controllers/' . $name . '.php');
        io::done('    controller');
        self::rCopy($this->root_dir . $this->models_dir. '/' . $src,$this->root_dir . $this->models_dir. '/' . $name);
        io::done('    model');
        self::rCopy($this->root_dir . '/pages/' . $src,$this->root_dir . '/pages/' . $name);
        io::done('    pages');
    }// }}}

    // {{{cmdRemove
    public function cmdRemove(){
        if (($name = ArgsHolder::get()->shiftCommand()) === false)
            return io::out('Incorrect param count', IO::MESSAGE_FAIL);

        if (file_exists($this->root_dir.'/controllers/'.$name.'.php'))
        {
            if (IO::YES == io::dialog('Realy Delete controller,models,pages with name ~WHITE~'.$name.'~~~?', IO::NO|IO::YES, IO::NO))
            {
                IO::out('~WHITE~Removing:~~~');
                self::rRem($this->root_dir . '/controllers/' . $name . '.php');
                io::done('     controllers/'.$name.'.php');
                self::rRem($this->root_dir . $this->models_dir. '/' . $name);
                io::done('     '.$this->models_dir.'/'.$name);
                self::rRem($this->root_dir . '/pages/' . $name);
                io::done('     pages/'.$name);
            }
        }
        else 
            io::out( 'Controller with name ~WHITE~'.$name.'~~~ not exist ',IO::MESSAGE_FAIL);
    }// }}}


    // {{{ Util     
    //
    private static function rCopy($src,$dist) 
    { 
        $dir = opendir($src); 
        if (!is_dir($dist)) @mkdir($dist,0775);
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
    
    private static function rRem($dir)
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
    // }}}
}
