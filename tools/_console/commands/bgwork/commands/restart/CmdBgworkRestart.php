<?php
class CmdBgworkRestart extends Command{
    
    const TABLE ='delayed_jobs';
   
    private $count = false;
   
    function process()
    {
        Console::initCore();
        if (($c = ArgsHolder::get()->shiftCommand()) == 'help') return $this->cmdHelp();
        try{
            if(is_numeric($c))
                $this->RestartById($c);
            else
                io::out("You can input only id of work.",IO::MESSAGE_FAIL); return;
        }catch(Exception $e){
            io::out( $e->getMessage(),IO::MESSAGE_FAIL);
            return;
        }
    }

    private function log($str)
    {
        $logFile=Config::get('ROOT_DIR').'/logs/delayedjob.log';
        file_put_contents($logFile, PHP_EOL.$str ,FILE_APPEND);
    }

    public function RestartById($c)
    {
        if(!count(DB::query('SELECT * from '.self::TABLE.' WHERE id="'.$c.'"')))
        {io::out("Work with id=$c is not exists",IO::MESSAGE_FAIL); return;}

        $list=DB::query('SELECT * FROM '.self::TABLE.' where isnull(finished_at) and not 
            isnull(locked_at) and isnull(failed_at) and id='.$c.' ORDER BY run_at DESC');
        if(count($list)){IO::out("This is working now...You cant restart!",IO::MESSAGE_FAIL);return;}

        if(IO::YES == io::dialog('Do you really want to restart work with id '.$c.'?', IO::NO|IO::YES, IO::NO))
        {
           DB::query("UPDATE ".self::TABLE." set attempts='1',finished_at=null, locked_at=null, 
                failed_at=null, run_at=now()  WHERE  id='".$c."'");
           $php_path = exec("which php");
           if(empty($php_path))
                return $this->log("###".date("c")." Call from console PHP executable not found");
           if(!is_executable($php_path))
               return $this->log("###".date("c")." Call from console $php_path could not be executed");

           exec($php_path.' '.trim(escapeshellarg(
               Config::get('ROOT_DIR')."/vendors/delayedjob/JobHandler.php"),"'").' >> '.
               Config::get('ROOT_DIR').'/logs/delayedjob.log 2>&1 &');
           io::done('Restarting...');
        }
        else
           io::done('Cancel restart');
        IO::out("");
}

}
