<?php


class CmdUserDel extends Command{

    function process(){
        Console::initCore();
        if (($c = ArgsHolder::get()->shiftCommand()) == 'help') return $this->cmdHelp();
        $login = $c;;

        if ($login === false) {
            io::out('Incorrect param count', IO::MESSAGE_FAIL);
            return;
        }
        if (UserManager::get()->existsLogin($login))
        {
        try{
            if (IO::YES == io::dialog('~RED~Realy Delete user with login ~~~ '.$login.'?', IO::NO|IO::YES, IO::NO))
            {
                if(!$res=UserManager::get()->cleanUser($login))    
                    io::done('Deleting User '.$login);
                else 
                    io::done($res);
            }
            else 
                io::done('Canceling delete User '.$login);
        }catch(Exception $e){
            io::out( $e->getMessage(),IO::MESSAGE_FAIL);
            return;
        }
        }
        else 
            io::out( $e->getMessage(),IO::MESSAGE_FAIL);
    }

    //function cmddefault(){
    //}

}
