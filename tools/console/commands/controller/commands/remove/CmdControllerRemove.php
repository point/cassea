<?php


class CmdControllerRemove extends Command{

    function process(){
        $this->root=Config::get('ROOT_DIR');
        if (($c = ArgsHolder::get()->shiftCommand()) == 'help') return $this->cmdHelp();
        $name = $c;;

        if (($name === false)||(strpos($name,'.'))){
            io::out('Incorrect param count', IO::MESSAGE_FAIL);
            return;
        }

        if (file_exists($this->root.'/controllers/'.$name.'.php'))
        {
        try{
            if (IO::YES == io::dialog('~RED~Realy Delete controller,models,pages with name ~~~ '.$name.'?', IO::NO|IO::YES, IO::NO))
            {
                FS::removeController($this->root,$name);
                io::done('Removing Controller '.$name);
                io::done('Removing  models '.$name);
                io::done('Removing pages for '.$name);

            }
            else 
                io::done('Canceling remove Controller '.$name);
        }catch(Exception $e){
            io::out( $e->getMessage(),IO::MESSAGE_FAIL);
            return;
        }
        }
        else 
            io::out( 'Controller with name ~RED~'.$name.'~~~ not exist ',IO::MESSAGE_FAIL);
    }

}
