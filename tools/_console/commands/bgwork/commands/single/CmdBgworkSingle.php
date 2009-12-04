<?php

class CmdBgworkSingle extends Command{
    
    const TABLE ='delayed_jobs';
   
    private $count = false;

    private function emp($var)
    {
        return  empty($var) ? "- - - - - - - - - - " : $var;
    }

    static function walker($array)
    {
        $r='';
        foreach($array as $a)
        {
            if(is_array($a))
                $r.=" array(".trim(self::walker($a),',')."),";
            else
                $r.=$a.' ,';
        }
        return $r;
    }
   
    function process(){
        Console::initCore();
        if (($c = ArgsHolder::get()->shiftCommand()) == 'help') return $this->cmdHelp();
        try{
            $format="%-25s %s";

            IO::out("");
            $s=DB::query('SELECT * FROM '.self::TABLE.' where id="'.$c.'"');
            if(!count($s))
            {
            io::out("Work with id=$c is not exists",IO::MESSAGE_FAIL); return;}
            io::out(sprintf($format,"~CYAN~id~~~",$c));
            io::out(sprintf($format,"~CYAN~queue~~~",$this->emp($s[0]['queue'])));
            io::out(sprintf($format,"~CYAN~priority~~~",$this->emp($s[0]['priority'])));
            io::out(sprintf($format,"~CYAN~run at~~~",$this->emp($s[0]['run_at'])));
            io::out(sprintf($format,"~CYAN~locked at~~~",$this->emp($s[0]['locked_at'])));
            io::out(sprintf($format,"~CYAN~finished at~~~",$this->emp($s[0]['finished_at'])));
            io::out(sprintf($format,"~CYAN~failed at~~~",$this->emp($s[0]['failed_at'])));
            io::out(sprintf($format,"~CYAN~attemts~~~",$this->emp($s[0]['attempts'])));
            $handler=unserialize($s[0]["handler"]);
            io::out(sprintf($format,"~CYAN~call~~~",$handler["class"]."::".$handler["method"])."(...)");
            if(isset($handler["param"]))io::out(sprintf($format,"~CYAN~params~~~",
                trim(self::walker($handler['param']),',')));

        }catch(Exception $e){
            io::out($e->getMessage(),IO::MESSAGE_FAIL);
            return;
        }
        IO::out("");
    }
}
