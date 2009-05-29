<?php
class CmdGroupList  extends Command{
    function process()
    {        
        Console::initCore();
        require_once( Config::getInstance()->root_dir.'/includes/ACL.php');
        if ($c = ArgsHolder::get()->shiftCommand()) return $this->cmdHelp();
        $groups=ACL::getGroups();
            try{
                IO::out("~GREEN~List of site groups~~~:");
                for($i=0;$i<count($groups);$i++)
                    IO::out(" ".$groups[$i]);

                IO::out("");
        }catch(Exception $e){
            io::out( $e->getMessage(),IO::MESSAGE_FAIL);
            return;
        }


    }
}
