<?php
Console::initCore();

class CmdGroup extends Command{

    public function cmdAdduser(){
        $login = ArgsHolder::get()->shiftCommand();
        $group= ArgsHolder::get()->shiftCommand();
        if ($login === false || $group===false) 
            return io::out('Incorrect param count', IO::MESSAGE_FAIL) | 1;
        
        if($id=UserManager::get()->getIdByLogin($login)){
            try{
                io::out('Adding User '.$login."($id) to group ".$group,false);
                ACL::addUser($id,$group);
                io::done();
            }catch(Exception $e){return io::out( $e->getMessage(),IO::MESSAGE_FAIL)| 2;}
        }
        else 
            return io::out( "No such user: $login",IO::MESSAGE_FAIL) | 3;
       
    }

    public function cmdDeluser(){
        
        $login = ArgsHolder::get()->shiftCommand();
        $group = ArgsHolder::get()->shiftCommand();
        if($login === false || $group===false) 
            return io::out('Incorrect param count', IO::MESSAGE_FAIL) | 1;
       
        if(!in_array($group,array_values((ACL::getGroups()))))
                    return io::out( "No such group $group",IO::MESSAGE_FAIL) | 3;

        if(!$id=UserManager::get()->getIdByLogin($login))
            return io::out( "No such user $login",IO::MESSAGE_FAIL)| 3;

        if (IO::YES == io::dialog('Remove user ~WHITE~ '.$login.'~~~ from group ~WHITE~'.$group.'~~~', IO::NO|IO::YES, IO::YES))
        {
            io::out('deleting...',false);
            ACL::delUser($id,$group);
            io::done();
        }   
    }

    public function cmdList(){
        $groups=ACL::getGroups();
        if (!count($groups)) return io::out('There is no groups yet.');
        IO::out("~WHITE~List of groups~~~:");
        for($i=0;$i<count($groups);$i++)
            IO::out(" ".$groups[$i]);
    }

    public function cmdUserlist(){
        if (!count(ACL::getGroups())) return io::out('There is no groups yet.');
        $users=ACL::getUsers();
        if (($group = ArgsHolder::get()->shiftCommand())){ 
            if(!in_array($group,array_values((ACL::getGroups()))))
                return io::out( "No such group $group",IO::MESSAGE_FAIL) | 3;
            IO::out("~WHITE~User(s) from group ".$group."~~~:");
            foreach($users[$group] as $u)
                IO::out(" ".$u);
        }else
            foreach(array_keys($users) as $g){
                IO::out("~WHITE~Group ".$g."~~~:");
                foreach($users[$g] as $u)
                    IO::out(" ".$u);
            }
    }
}

