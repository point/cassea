<?php


class CmdGroupeAdd extends Command{
   
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
                ACL::addUserToGroup($id,$group);
                io::done('Adding User '.$login.' to Group '.$group);
            }catch(Exception $e){
                io::out( $e->getMessage(),IO::MESSAGE_FAIL);
                return;
            }
        }
        else 
            io::out( 'No such user',IO::MESSAGE_FAIL);
}}
