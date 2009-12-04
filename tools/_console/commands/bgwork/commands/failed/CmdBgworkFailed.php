<?php

class CmdBgworkFailed extends Command{
    
    const TABLE ='delayed_jobs';
   
    private $count = false;
   
    function process(){
        Console::initCore();
        if ($r=ArgsHolder::get()->getOption('count')) $this->count = $r;
        if (($c = ArgsHolder::get()->shiftCommand()) == 'help') return $this->cmdHelp();
        try{
            IO::out("");
            $sql='SELECT * FROM '.self::TABLE.' where isnull(finished_at) and not 
                isnull(locked_at) and not isnull(failed_at) ORDER BY run_at DESC';
            if($this->count)
                $list=DB::query($sql.' LIMIT '.$this->count);
            else
                $list=DB::query($sql);
            if(!count($list)){IO::out("Hooray no failed work here!");return;}
                io::out(sprintf("%-10s %-7s %-3s %-20s %-20s %-19s %-4s %-5s","~CYAN~id","queue",
                    "pr","run_at","locked_at","failed_at","att","call_to~~~"));
            foreach($list as $l)
            {
                $handler=unserialize($l["handler"]);
                io::out(sprintf("%-4s %-7s %-3s %-20s %-20s %-20s %-3s %-10s",$l["id"],$l["queue"],
                    $l["priority"],$l["run_at"],$l["locked_at"],$l["failed_at"],$l["attempts"],
                    $handler["class"]."::".$handler["method"]."(...)"));
            }
        }catch(Exception $e){
            io::out( $e->getMessage(),IO::MESSAGE_FAIL);
            return;
        }
        IO::out("");
    }

}
