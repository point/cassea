<?php


class CmdBgworkNow extends Command{
    const TABLE ='delayed_jobs';
   
    private $count = false;
   
    function process(){
        Console::initCore();
        if ($r=ArgsHolder::get()->getOption('count')) $this->count = $r;
        if (($c = ArgsHolder::get()->shiftCommand()) == 'help') return $this->cmdHelp();
        try{

            IO::out("");
            $sql='SELECT * FROM '.self::TABLE.' where isnull(finished_at) and not isnull(locked_at)
                and isnull(failed_at) ORDER BY run_at DESC';
            if($this->count)
                $list=DB::query($sql.' LIMIT '.$this->count);
            else
                $list=DB::query($sql);
            if(!count($list)){IO::out("No work now!");IO::out();return;}
                io::out(sprintf("%-10s %-1s %-20s %-19s %-4s %-5s","~CYAN~id","pr","run_at",
                    "locked_at","att","call_to~~~"));
            foreach($list as $l)
            {
                $handler=unserialize($l["handler"]);
                io::out(sprintf("%-4s %-2s %-20s %-20s %-3s %-1s",$l["id"],$l["priority"],
                    $l["run_at"],$l["locked_at"],$l["attempts"],$handler["class"]."::".$handler["method"]."(...)"));
            }
        }catch(Exception $e){
            io::out( $e->getMessage(),IO::MESSAGE_FAIL);
            return;
        }
        IO::out("");
    }

}
