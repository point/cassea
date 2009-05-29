<?php
class CmdUserInfo extends Command{

    function process(){
        Console::initCore();
        if (($c = ArgsHolder::get()->shiftCommand()) == 'help') return $this->cmdHelp();
        $login = $c;;

        if ($login === false) {
            io::out('Incorrect param count', IO::MESSAGE_FAIL);
            return;
        }
        
        if(is_numeric($login))
            $id=$login;
        elseif(!$id=UserManager::get()->getIdByLogin($login))
            $id=UserManager::get()->getIdByEmail($login);
        
        if (UserManager::get()->exists($id)&&($id))
        {
        try{
        $p=new Profile($id);    
        IO::out("");
        IO::out("~WHITE~User's Profile~~~:");
        IO::out("~GREEN~Id~~~:        ".$id);
        IO::out("~GREEN~Login~~~:     ".UserManager::get()->getLogin($id));
        IO::out("~GREEN~Firstname~~~: ".$p->firstname);
        IO::out("~GREEN~Lastname~~~:  ".$p->lastname);
        IO::out("~GREEN~Email~~~:     ".UserManager::get()->getEmail($id));
        IO::out("~GREEN~Email2~~~:    ".$p->email2);
        IO::out("");

        }catch(Exception $e){
            io::out( $e->getMessage(),IO::MESSAGE_FAIL);
            return;
        }
        }
        else 
            io::out( 'No such user',IO::MESSAGE_FAIL);
    }

}
