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
			User::add(array("login"=>$login, "password"=>$password, "email"=>$email)/*, $confirm*/ );        
        }catch (UserException $e) {
            return io::out(PHP_EOL.$e->getMessage(), IO::MESSAGE_FAIL) | 127;
        }
        io::done();
    }

	/* meaningless. System deletes expired user automatically
	 * public function cmdDelExpired(){
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

	}*/

    public function cmdDel(){
        if (($login = ArgsHolder::get()->shiftCommand()) === false) 
            return io::out('Incorrect param count', IO::MESSAGE_FAIL) | 1;
        if( IO::YES != io::dialog('Do You really want to delete user ~RED~'.$login.'~~~?', IO::NO|IO::YES, IO::NO))
            return io::out( 'Cancelled ',IO::MESSAGE_FAIL) | 2 ;

        try{
        if(ArgsHolder::get()->getOption('confirm')) 
			if(OneTimeTokenAuth::exists(($user_id = User::findIdBy('login',$login)))){
                io::out('Deleting User... ', false);
				OneTimeTokenAuth::deleteByUserId($user_id);
                return io::done();
            }   
            else  
                return io::out( 'There is no user ~WHITE~'.$login.'~~~',IO::MESSAGE_FAIL) | 2 ;


        if (($user = User::findBy("login",$login))){
            io::out('Deleting user ', false);
			$user->delete();
            io::done();
        }
        else 
            return io::out( 'There is no user ~WHITE~'.$login.'~~~',IO::MESSAGE_FAIL) | 2 ;
        }catch (UserException $e) {  return io::out($e->getMessage(), IO::MESSAGE_FAIL) | 127;  }
    }

    public function cmdInfo(){
        if (($login = ArgsHolder::get()->shiftCommand()) === false)
            return io::out('Incorrect param count', IO::MESSAGE_FAIL) | 1;
        
        try{
		$user = null;
        if(is_numeric($login)) $user = User::findBy('id',$login);
        elseif(!$user= User::findBy('login',$login))
            $user=User::findBy('email',$login);

        if ($user)
        {
            //$p=new Profile($id);    
            IO::out("");
            IO::out("~WHITE~User Info~~~:");
            $info = array(
                'Id' => $user->getId(),
                'Login' => $user->getLogin(),
                'E-mail' => $user->getEmail(),
                'State'=> $user->getState() == "banned"?'~RED~Banned~~~':
                            ($user->getState() == "active"?'~GREEN~Active~~~':'~CYAN~Deleted~~~')
                );

			/*What is this ?
				if (IO::getVerboseLevel()> IO::MESSAGE_TEXT){
					$info[''] = '';
					$info['~WHITE~CasseaUserManager~~~'] = '';
				}*/
			$info['Joined'] = $user->getDateJoined();
			$info['Last seen'] = (( $l = $user->getLastLogin()) != '0000-00-00 00:00:00')?$l: 'never';
            
            IO::outOptions($info);
            //TODO SHow user profile
        }
        else 
            return io::out( 'There is no user ~WHITE~'.$login.'~~~',IO::MESSAGE_FAIL) | 2 ;
        }catch (UserException $e) {  return io::out($e->getMessage(), IO::MESSAGE_FAIL) | 127;  }
    }


    public function cmdList(){
        try{

			$all_users_count = count(User::getAll());
			$active_users_count = count(User::getAll("active"));
			$not_confirmed_users_count = count(User::getAll("not_confirmed"));

            if (ArgsHolder::get()->getOption('count')){
                IO::out('~WHITE~Count of users~~~:      ~GREEN~'.($all_users_count).'~~~');
                IO::out('~WHITE~Active users~~~:    ~GREEN~'.count($active_users_count).'~~~');
                IO::out('~WHITE~Not-confirmed users~~~: ~GREEN~'.count($not_confirmed_users_count).'~~~');
                return;
            }

			$list= (ArgsHolder::get()->getOption('not-confirmed'))?
				User::getAll("not_confirmed",true):User::getAll("all",true);
            io::out('~CYAN~');
            IO::out(sprintf("%-20s %-20s %s", "Id", "Login", "EMail"));
            IO::out('~~~',false);
            
            $format = "%-20s %-20s %s";
            for($i=0;$i<count($list);$i++)
                IO::out(sprintf($format, $list[$i]['id'], $list[$i]['login'], $list[$i]['email']));
            IO::out('~WHITE~Total~~~:'.count($list));
        }catch (UserException $e) {  return io::out($e->getMessage(), IO::MESSAGE_FAIL) | 127;  }
    }


    public function cmdPassword(){
        try{
            $login = ArgsHolder::get()->shiftCommand();
            $password= ArgsHolder::get()->shiftCommand();

            if ($login === false ) return io::out('Incorrect param count', IO::MESSAGE_FAIL) | 1;

            if (($user = User::findBy("login",$login)))
            {
                if (!$password){
                    IO::out('New password: ', false);
                    $password = IO::in(IO::TYPE_STRING);
                    IO::out('Confirm New password: ', false);
                    $p2 = IO::in(IO::TYPE_STRING);

                    if($password != $p2) return io::out('Passwords not match.',IO::MESSAGE_FAIL ) | 2;
                }
                $user->setPassword($password);
            }
            else 
                return io::out( PHP_EOL.'User ~WHITE~'.$login.'~~~ not found',IO::MESSAGE_FAIL)| 3;

        }catch (UserException $e) {  return io::out($e->getMessage(), IO::MESSAGE_FAIL) | 127;  }
    }

}
