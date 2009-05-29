<?php


class CmdGroupDelUser extends Command{
    function process(){
        Console::initCore();
        if (($c = ArgsHolder::get()->shiftCommand()) == 'help') return $this->cmdHelp();
        $login = $c;;
        $group= ArgsHolder::get()->shiftCommand();
        if ($login === false || $group===false) {
            io::out('Incorrect param count', IO::MESSAGE_FAIL);
            return;
        }
        
        $id=UserManager::get()->getIdByLogin($login);
        if (UserManager::get()->exists($id)&&($id)){
            try{
                if (IO::YES == io::dialog('~RED~Realy Delete user with login ~~~ '.$login.'~RED~ from group~~~ '.$group, IO::NO|IO::YES, IO::NO))
                {
                    ACL::delUserFromGroup($id,$group);
                    io::done('Deleting  User '.$login.' from Group '.$group);
                }
                else
                    io::done('Canceling delete User '.$login.' from group');
            }catch(Exception $e){
                io::out( $e->getMessage(),IO::MESSAGE_FAIL);
                return;
            }
        }
        else 
            io::out( 'No such user',IO::MESSAGE_FAIL);


 
    }
}
