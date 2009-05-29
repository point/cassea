<?php
class cmd  extends Command{
    private $showNotConfirmed = false;
    private $showCount = false;

    protected function processOptions()
    {
        if (ArgsHolder::get()->getOption('not-confirmed')) $this->showNotConfirmed = true;
        if (ArgsHolder::get()->getOption('count')) $this->showCount = true;
    
    }
   
    function process()
    {        
        if($this->showNotConfirmed) return $this->notConfirmedList();
        if($this->showCount) return $this->allCount();
        if ($c = ArgsHolder::get()->shiftCommand()) return $this->cmdHelp();
        $this->registredList();
    }

    private function registredList(){

        require_once( Config::getInstance()->root_dir.'/includes/user/UserManager.php');
        try{
            $list=UserManager::get()->getUsersList();
            IO::out("");
            IO::out('~WHITE~Registered users Count~~~:'.count($list));
            IO::out('~WHITE~Users List~~~:');
            IO::out(sprintf("%-20s %-30s %s", "~GREEN~Id~~~:", "~GREEN~Login~~~:", "~GREEN~EMail~~~:"));
            $format = "%-20s %-20s %s";
            for($i=0;$i<count($list);$i++)
                IO::out(sprintf($format, "~GREEN~".$list[$i]['id']."~~~", $list[$i]['login'], $list[$i]['email']));
            IO::out("");
        }catch(Exception $e){
            echo $e;
        }

    }

    private function notConfirmedList(){
       require_once( Config::getInstance()->root_dir.'/includes/user/UserManager.php');
       try{
            $options=array();
            $list =UserManager::get()->getNotConfirmed();
            IO::out("");
            IO::out('~WHITE~Not-confirmed  users Count~~~:'.count($list));
            IO::out('~WHITE~Not confirmed Users List~~~:');
            IO::out(sprintf("%-20s %-30s %s", "~GREEN~Regkey~~~:", "~GREEN~Login~~~:", "~GREEN~EMail~~~:"));
            $format = "%-20s %-20s %s";
            for($i=0;$i<count($list);$i++)
                IO::out(sprintf($format, "~GREEN~".$list[$i]['regkey']."~~~", $list[$i]['login'], $list[$i]['email']));
            IO::out("");
        }catch(Exception $e){
            echo $e;
        }
    }
    
    private function allCount(){
       require_once( Config::getInstance()->root_dir.'/includes/user/UserManager.php');
       try{
            $notconfirm =UserManager::get()->getNotConfirmed();
            $registered=UserManager::get()->getUsersList();
            IO::out("");
            IO::out('~WHITE~Count of users~~~:      ~GREEN~'.(count($registered)+count($notconfirm)).'~~~');
            IO::out('~WHITE~Registered users~~~:    ~GREEN~'.count($registered).'~~~');
            IO::out('~WHITE~Not-confirmed users~~~: ~GREEN~'.count($notconfirm).'~~~');
            IO::out("");
        }catch(Exception $e){
            echo $e;
        }
    }




}
