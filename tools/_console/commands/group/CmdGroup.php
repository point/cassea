<?php
Console::initCore();

class CmdGroup extends Command{

    public function cmdAdduser(){
        $login = ArgsHolder::get()->shiftCommand();
        $group= ArgsHolder::get()->shiftCommand();
        if ($login === false || $group===false) 
            return io::out('Incorrect param count', IO::MESSAGE_FAIL) | 1;
        
        $id=UserManager::get()->getIdByLogin($login);
        if (UserManager::get()->exists($id)&&($id)){
            try{
                ACL::addUserToGroup($id,$group);
                io::done('Adding User '.$login.' to group '.$group);
            }catch(Exception $e){
                return io::out( $e->getMessage(),IO::MESSAGE_FAIL)| 2;
            }
        }
        else 
            return io::out( 'No such user',IO::MESSAGE_FAIL) | 3;
       
    }

    public function cmdDeluser(){
        $login = ArgsHolder::get()->shiftCommand();
        $group = ArgsHolder::get()->shiftCommand();
        if ($login === false || $group===false) 
            return io::out('Incorrect param count', IO::MESSAGE_FAIL) | 1;
        
        $id=UserManager::get()->getIdByLogin($login);
        // TODO Whatafuck
        if (UserManager::get()->exists($id)&&($id)){
            if (IO::YES == io::dialog('Remove user ~WHITE~ '.$login.'~~~ from group ~WHITE~'.$group.'~~~', IO::NO|IO::YES, IO::YES))
                ACL::delUserFromGroup($id,$group);
            io::done('Deleting');
        }
        else 
            return io::out( 'No such user',IO::MESSAGE_FAIL)| 3;
    }

    public function cmdList(){
        $groups=ACL::getGroups();
        if (!count($groups)) return io::out('There is no groups yet.');
        IO::out("~WHITE~List of groups~~~:");
        for($i=0;$i<count($groups);$i++)
            IO::out(" ".$groups[$i]);
    }

    public function cmdUserlist(){
        if ( ($c = ArgsHolder::get()->shiftCommand())) return $this->listByGroup($c);
            
        $groups=ACL::getGroups();
        if (!count($groups)) return io::out('There is no groups yet.');
        $all=ACL::getUserByGroups();
        for($i=0;$i<count($groups);$i++)
        {
            IO::out("~WHITE~Group ".$groups[$i]."~~~:");
            for($j=0;$j<count($all);$j++)
                if(preg_match('/(^|:)'.$groups[$i].'($|:)/',$all[$j]['groups']))
                    IO::out(" ".$all[$j]['login']);
        }
    }

    private function listByGroup($group)
    {
        $id=ACL::getUserByGroups($group);
        if(!$id) return io::out("No such group",IO::MESSAGE_FAIL) | 1 ;

        IO::out("~WHITE~User(s) from group ".$group."~~~:");
        for($i=0;$i<count($id);$i++)
            IO::out(" ".UserManager::get()->getLogin($id[$i]['user_id']));
    }
}

