<?php


class CmdUserPassword extends Command{
   
    private $confirm = false;
   
    function process(){
        Console::initCore();
        if (($c = ArgsHolder::get()->shiftCommand()) == 'help') return $this->cmdHelp();
        $login = $c;
        $password= ArgsHolder::get()->shiftCommand();
        
        if ($login === false || $password=== false) {
            io::out('Incorrect param count', IO::MESSAGE_FAIL);
            return;
        }
        if (usermanager::get()->existslogin($login))
        {
        try{
            if (IO::YES == io::dialog('~RED~Realy set NEW PASSWORD for user with login ~~~ '.$login.'?', IO::NO|IO::YES, IO::NO))
            {
                usermanager::get()->setpassword(usermanager::get()->getidbylogin($login),$password);
                io::done('Setting password for user '.$login);
            }
            else 
                io::done('Canceling set password for user '.$login);
        }catch(exception $e){
            io::out( $e->getmessage(),IO::MESSAGE_FAIL);
            return;
        }
        }
        else 
            io::out( 'no such user',IO::MESSAGE_FAIL);

    }

}
