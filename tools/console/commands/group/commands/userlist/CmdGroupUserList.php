<?php
class CmdGroupUserList  extends Command{

    function process()
    {        
        Console::initCore();
        if (($c = ArgsHolder::get()->shiftCommand()) == 'help') return $this->cmdHelp();
        if ($c) return $this->listByGroup($c);
        try{
            
            $groups=ACL::getGroups();
            $all=ACL::getUserByGroups();
            for($i=0;$i<count($groups);$i++)
            {
                IO::out("~GREEN~Group ".$groups[$i]."~~~:");
                for($j=0;$j<count($all);$j++)
                {
                    if(preg_match('/(^|:)'.$groups[$i].'($|:)/',$all[$j]['groups']))
                      IO::out(" ".$all[$j]['login']);
                }
            }

        }catch(Exception $e){
            io::out( $e->getMessage(),IO::MESSAGE_FAIL);
            return;
        }

    }

    private function listByGroup($group)
    {
        try{
            $id=ACL::getUserByGroups($group);
            if(!$id){io::out("No such group",IO::MESSAGE_FAIL);return;}

            IO::out("~GREEN~User(s) from group ".$group."~~~:");
            for($i=0;$i<count($id);$i++)
               IO::out(" ".UserManager::get()->getLogin($id[$i]['user_id']));
            IO::out("");


        }catch(Exception $e){
            io::out( $e->getMessage(),IO::MESSAGE_FAIL);
            return;
        }

            }




}
