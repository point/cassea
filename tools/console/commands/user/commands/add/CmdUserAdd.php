<?php


class CmdUserAdd extends Command{
   
    private $confirm = false;
   
    protected function processOptions()
    {
        if (ArgsHolder::get()->getOption('confirmation')) $this->confirm = true;
    }

    function process(){
        Console::initCore();
        if (($c = ArgsHolder::get()->shiftCommand()) == 'help') return $this->cmdHelp();
        $login = $c;;

        $password= ArgsHolder::get()->shiftCommand();
        $email = ArgsHolder::get()->shiftCommand();
        if ($login === false || $email === false || $password=== false) {
            io::out('Incorrect param count', IO::MESSAGE_FAIL);
            return;
        }
        try{
            if($this->confirm)
                UserManager::get()->addUser($login, $password, $email, true);        
            else
                UserManager::get()->addUser($login, $password, $email, false);        
        }catch(Exception $e){
            if (($e instanceof ControllerException) && $e->getCode() == 1 )
                return io::out( "TODO: Registration link without instance of Controller",IO::MESSAGE_FAIL);
            //echo $e;
            io::out( $e->getMessage(),IO::MESSAGE_FAIL);
            return;
        }
        io::done('Adding User');
    }

}
