<?php
Console::initCore();

class CmdUser extends Command{

    public function cmdAdd(){
        Language::init(); // 
        $login = ArgsHolder::get()->shiftCommand();
        $password= ArgsHolder::get()->shiftCommand();
        $email = ArgsHolder::get()->shiftCommand();
        if ($login === false || $email === false || $password=== false) 
            return io::out('Incorrect param count', IO::MESSAGE_FAIL)| 1;
        
        $confirm = ArgsHolder::get()->getOption('confirmation');
        // TODO  Confirn not working
        // TODO: Registration link without instance of Controller
        $confirm = false;
        io::out('Adding user: ~WHITE~'.$login.' <'.$email.'>~~~', false);
        try{
            UserManager::get()->addUser($login, $password, $email, $confirm);        
        }catch (UserManagerException $e) {
            return io::out(PHP_EOL.$e->getMessage(), IO::MESSAGE_FAIL) | 127;
        }
        io::done();
    }

    public function cmdDelExpired(){
        try{
        $c=UserManager::get()->lookForNotConfirmed();
        if(count($c))
        {
            if (IO::YES == io::dialog('Finded ~RED~'.count($c).'~~~ users.'.PHP_EOL.'You really want to remove them?', IO::NO|IO::YES, IO::NO))
            {
                io::out('Deleting userd...', false);
                UserManager::get()->deletNotConfirmed();
                io::done();
            }
        }else io::out( 'There is no such users.');
        }catch (UserManagerException $e) {  return io::out($e->getMessage(), IO::MESSAGE_FAIL) | 127;  }

    }

    public function cmdDel(){
        if (($login = ArgsHolder::get()->shiftCommand()) === false) 
            return io::out('Incorrect param count', IO::MESSAGE_FAIL) | 1;
        if( IO::YES != io::dialog('Do You really want to delete user ~RED~'.$login.'~~~?', IO::NO|IO::YES, IO::NO))
            return io::out( 'Cancelled ',IO::MESSAGE_FAIL) | 2 ;

        try{
        if(ArgsHolder::get()->getOption('confirm')) 
            if (UserManager::get()->existsRegistration($login)){ 
                io::out('Deleting User... ', false);
                UserManager::get()->deleteFromRegistration($login);
                return io::done();
            }   
            else  
                return io::out( 'There is no user ~WHITE~'.$login.'~~~',IO::MESSAGE_FAIL) | 2 ;


        if (UserManager::get()->existsLogin($login)){
            io::out('Deleting user ', false);
            $res=UserManager::get()->deleteUser($login);
            io::done();
        }
        else 
            return io::out( 'There is no user ~WHITE~'.$login.'~~~',IO::MESSAGE_FAIL) | 2 ;
        }catch (UserManagerException $e) {  return io::out($e->getMessage(), IO::MESSAGE_FAIL) | 127;  }
    }

    public function cmdInfo(){
        if (($login = ArgsHolder::get()->shiftCommand()) === false)
            return io::out('Incorrect param count', IO::MESSAGE_FAIL) | 1;
        
        try{
        $um = UserManager::get();
        if(is_numeric($login)) $id=$login;
        elseif(!$id= $um->getIdByLogin($login))
            $id=$um->getIdByEmail($login);

        if ($um->exists($id)&&($id))
        {
            //$p=new Profile($id);    
            IO::out("");
            IO::out("~WHITE~User Info~~~:");
            $info = array(
                'Id' => $id,
                'Login' => $um->getLogin($id),
                'E-mail' => $um->getEmail($id),
                'State'=> $um->isBanned($id)?'~RED~Banned~~~':
                            ($um->isActive($id)?'~GREEN~Active~~~':'~CYAN~Deleted~~~')
                );

            if (Usermanager::Get() instanceof CasseaUserManager){
                if (IO::getVerboseLevel()> IO::MESSAGE_TEXT){
                    $info[''] = '';
                    $info['~WHITE~CasseaUserManager~~~'] = '';
                }
                $info['Joined'] = $um->getDateJoined($id);
                $info['Last seen'] = (( $l = $um->getLastLogin($id)) != '0000-00-00 00:00:00')?$um->getLastLogin($id): 'never';
            }
            
            IO::outOptions($info);
            //TODO SHow user profile
        }
        else 
            return io::out( 'There is no user ~WHITE~'.$login.'~~~',IO::MESSAGE_FAIL) | 2 ;
        }catch (UserManagerException $e) {  return io::out($e->getMessage(), IO::MESSAGE_FAIL) | 127;  }
    }


    public function cmdList(){
        try{
            if (ArgsHolder::get()->getOption('count')){
                $notconfirm =UserManager::get()->getNotConfirmed();
                $registered =UserManager::get()->getUsersList();
                IO::out('~WHITE~Count of users~~~:      ~GREEN~'.(count($registered)+count($notconfirm)).'~~~');
                IO::out('~WHITE~Registered users~~~:    ~GREEN~'.count($registered).'~~~');
                IO::out('~WHITE~Not-confirmed users~~~: ~GREEN~'.count($notconfirm).'~~~');
                return;
            }

            $list= (ArgsHolder::get()->getOption('not-confirmed'))?UserManager::get()->getNotConfirmed():UserManager::get()->getUsersList();
            io::out('~CYAN~');
            IO::out(sprintf("%-20s %-20s %s", "Id", "Login", "EMail"));
            IO::out('~~~',false);
            
            $format = "%-20s %-20s %s";
            for($i=0;$i<count($list);$i++)
                IO::out(sprintf($format, $list[$i]['id'], $list[$i]['login'], $list[$i]['email']));
            IO::out('~WHITE~Total~~~:'.count($list));
        }catch (UserManagerException $e) {  return io::out($e->getMessage(), IO::MESSAGE_FAIL) | 127;  }
    }


    public function cmdPassword(){
        try{
            $login = ArgsHolder::get()->shiftCommand();
            $password= ArgsHolder::get()->shiftCommand();

            if ($login === false ) return io::out('Incorrect param count', IO::MESSAGE_FAIL) | 1;

            if (usermanager::get()->existslogin($login))
            {
                if (!$password){
                    IO::out('New password: ', false);
                    $password = IO::in(IO::TYPE_STRING);
                    IO::out('Confirm New password: ', false);
                    $p2 = IO::in(IO::TYPE_STRING);

                    if($password != $p2) return io::out('Passwords not match.',IO::MESSAGE_FAIL ) | 2;
                }                
                usermanager::get()->setpassword(usermanager::get()->getidbylogin($login),$password);
            }
            else 
                return io::out( PHP_EOL.'User ~WHITE~'.$login.'~~~ not found',IO::MESSAGE_FAIL)| 3;

        }catch (UserManagerException $e) {  return io::out($e->getMessage(), IO::MESSAGE_FAIL) | 127;  }
    }

}    
