<?php
class CmdBgworkDelete extends Command{
    
    const TABLE ='delayed_jobs';
   
    private $count = false;
   
    function process(){
        Console::initCore();
        if (($c = ArgsHolder::get()->shiftCommand()) == 'help') return $this->cmdHelp();
        try{
            if(is_numeric($c))
                $this->deleteById($c);
            else
                $this->deleteQueue($c);
        }catch(Exception $e){
            io::out( $e->getMessage(),IO::MESSAGE_FAIL);
            return;
        }
    }

    public function deleteById($c)
    {
        if(!DB::query('SELECT * from '.self::TABLE.' WHERE id="'.$c.'"'))
            {io::out("Work with id=$c is not exists",IO::MESSAGE_FAIL); return;}
        
        $list=DB::query('SELECT * FROM '.self::TABLE.' where isnull(finished_at) and not isnull(locked_at) 
            and isnull(failed_at) and id='.$c.' ORDER BY run_at DESC');
            if(count($list)){IO::out("This is working now...You cant delete!");return;}

        if(IO::YES == io::dialog('Do you really want to delete work with id '.$c.'? ', IO::NO|IO::YES, IO::NO))
        {
           DB::query("DELETE FROM ".self::TABLE." WHERE  id='".$c."'");
           io::done('Deleting...');
        }
        else
           io::done('Cancel delete');
    }
   
    public function deleteQueue($c)
    {
        if(!DB::query('SELECT id from '.self::TABLE.' WHERE queue="'.$c.'"'))
            {io::out("Queue $c is not exists",IO::MESSAGE_FAIL); return;}

        if(IO::YES == io::dialog('Realy you really want to delete all jobs with queue '.$c.'?', IO::NO|IO::YES, IO::NO))
        {
            DB::query("DELETE FROM ".self::TABLE." WHERE  queue='".$c."'");
            io::done('Deleting...');
        }
        else
           io::done('Cancel delete');
    }

}
