<?php


class CmdUserDel extends Command{
    
    private $confirm = false;
    private $dfromconfirm = null;
 
    protected function processOptions()
    {
        if ($r=ArgsHolder::get()->getOption('confirm-expires')) $this->confirm = true;
        if ($r=ArgsHolder::get()->getOption('confirm')) $this->dfromconfirm = $r;
    }

    function process(){
        Console::initCore();
        if (($c = ArgsHolder::get()->shiftCommand()) == 'help') return $this->cmdHelp();
        
        if($this->confirm){ 
            $this->deleteConfirm();
            return;
        }
  
        if($this->dfromconfirm){ 
           $this->deleteFromConfirm($this->dfromconfirm);
           return;
        }

        $login = $c;
        if ($login === false) {
            io::out('Incorrect param count', IO::MESSAGE_FAIL);
            return;
        }
        if (UserManager::get()->existsLogin($login))
        {
        try{
            if (IO::YES == io::dialog('~RED~Realy Delete user with login ~~~ '.$login.'?', IO::NO|IO::YES, IO::NO))
            {
                if(!$res=UserManager::get()->deleteUser($login))    
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
            io::out( 'No such user',IO::MESSAGE_FAIL);
    }

    function deleteConfirm()
    {
        try{
            $c=UserManager::get()->lookForNotConfirmed();
            if(count($c))
            {
                io::out('Users count:   '.count($c));
                if (IO::YES == io::dialog('~RED~Do You really  want to delete users~~~?', IO::NO|IO::YES, IO::NO))
                {
                    UserManager::get()->deletNotConfirmed();
                    io::done('Deleting Users...');
                }else
                    io::done('Canceling delete Users');
            }else
                io::out( 'No such users',IO::MESSAGE_FAIL);

        }catch(Exception $e){
            io::out( $e->getMessage(),IO::MESSAGE_FAIL);
            return;
        }
    }
    function deleteFromConfirm($login)
    {
        if(UserManager::get()->existsRegistration($login))
        {
            try{
                if (IO::YES == io::dialog('~RED~Do You really want to delete user with login ~~~ '.$login.'?', IO::NO|IO::YES, IO::NO))
                {
                    UserManager::get()->deleteFromRegistration($login);
                    io::done('Deleting User... '.$login);
                }   

            }catch(Exception $e){
                io::out( $e->getMessage(),IO::MESSAGE_FAIL);
                return;
            }
        }
        else
            io::out( 'No such user',IO::MESSAGE_FAIL);

    }

}
