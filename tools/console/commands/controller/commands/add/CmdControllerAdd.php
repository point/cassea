<?php
class CmdControllerAdd extends Command{
   
    private $copy = false;
    protected $root = null;

    protected function processOptions()
    {
        if ($r=ArgsHolder::get()->getOption('copy')) $this->copy = $r;
    }

    function process(){
        $this->root=Config::get('ROOT_DIR');
        if (($c = ArgsHolder::get()->shiftCommand()) == 'help') return $this->cmdHelp();
        $name = $c;
        if (($name === false)||(strpos($name,'.'))||(strpos($this->copy,'.'))){
            io::out('Incorrect param count', IO::MESSAGE_FAIL);
            return;
        }
        if($this->copy) return $this->cntrlCopy($this->copy,$name);

        if (!file_exists($this->root.'/controllers/'.$name.'.php'))
        {
        try{
           FS::createController($this->root,$name);
        }catch(exception $e)
        {
         io::out( $e->getmessage(),IO::MESSAGE_FAIL);
         return;
        }
        io::done('Creating controller '.$name);
        io::done('Creating model '.$name);
        io::done('Creating index page ');
        }
        else 
            io::out( 'Controller with name ~RED~'.$name.'~~~ exist',IO::MESSAGE_FAIL);
    }
    
    private function cntrlCopy($src,$name)
    {

        if(file_exists($this->root.'/controllers/'.$name.'.php'))
        {
            io::out( 'Controller with name ~RED~'.$name.'~~~ exist',IO::MESSAGE_FAIL);
            return;
        }
        if(!file_exists($this->root.'/controllers/'.$src.'.php'))
        {
            io::out( 'Controller with name ~RED~'.$src.'~~~ not exist ',IO::MESSAGE_FAIL);
            return;
        }
        
        try{
            FS::copyController($this->root,$src,$name);
        }catch(exception $e)
        {
         io::out( $e->getmessage(),IO::MESSAGE_FAIL);
         return;
        }
        io::done('Copying controller ');
        io::done('Copying models ');
        io::done('Creating pages ');
    }

}
