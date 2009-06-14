<?php

class CmdBackup extends Command{
    
    private $separate = false;
      
    public function __construct( $workingDir = '.', $info, $commandsSeq = array())
    {
        parent::__construct( $workingDir, $info, $commandsSeq);
    }

    public function process()
    {
        Console::initCore();
        if ($r=ArgsHolder::get()->getOption('separate'))$this->separate=$r;
        if (($c = ArgsHolder::get()->shiftCommand()) == 'help') return $this->cmdHelp();
        try{
            if (IO::YES == io::dialog('~RED~Do You really want to do backup ~~~ ?', IO::NO|IO::YES, IO::NO))
            {
                $root=Config::get('ROOT_DIR');
                $name=basename($root);
                //Create MySQL dump
                $sqlname=$this->createMySQL($root,$name,$this->separate);
                //Create backup file
                $this->createTar($root,$name,$this->separate);
                if(!$this->separate)
                    unlink($root.'/'.$sqlname);

            }
            else 
                io::done('Canceling creating backup. ');
        }catch(Exception $e){
            io::out( $e->getMessage(),IO::MESSAGE_FAIL);
            return;
        }
    }

    public function createMySQL($root,$name,$separate=null)
    {
        if(file_exists($root.'/'.$name."_".date('Ymd').".sql.bz2"))
            $sqlname=$name."_".date('Ymd_H_i_s').'.sql';
        else
            $sqlname=$name."_".date('Ymd').'.sql';

        $db_db=Config::getInstance()->db->db;
        $db_user=Config::getInstance()->db->user;
        $db_password=Config::getInstance()->db->password;
        
        io::out("Creating Mysql dump .....");
        $cmd='mysqldump -R -q --single-transaction '.$db_db.' -u'.$db_user.' -p'.$db_password.' --result-file='.$root.'/'.$sqlname;
        exec($cmd,$out,$return);
        //if($return) return;//PERMISSIONS for SHOW functions and procedures... 
        if($separate)
        {
            $cmd='bzip2 -9 '.$root.'/'.$sqlname;
            exec($cmd,$out,$return);
            if($return) return;
        }
        io::done('~GREEN~The MySQL dump for  DB of project~~~ '.$name.'~GREEN~ was successfully created!~~~');
        return $sqlname;

    }
    
    public function createTar($root,$name,$separate=null)
    {
        if(file_exists($root.'/'.$name."_".date('Ymd').".tar.bz2"))
            $filename=$name."_".date('Ymd_H_i_s').'.tar';
        else
            $filename=$name."_".date('Ymd').'.tar';
        //$cmd="tar -cvf ".$root."/".$filename." ".$root." --exclude='*web*' --exclude='*~'";
        chdir($root);
        $cmd="tar --exclude='*web*' --exclude='*~' -cvf ".$root."/".$filename." * ";
        exec($cmd,$out,$return);
        if($return) return;
        foreach($out as $o)
            io::out($o);
        //$cmd="tar -rvf ".$root."/".$filename." ".$root."/web/css/ ".$root."/web/js/ --exclude='*~'";
        $cmd="tar --exclude='*~' -rvf ".$root."/".$filename." ./web/css/ ./web/js/ ";
        exec($cmd,$out,$return);
        if($return) return;
        foreach($out as $o)
            io::out($o);
        $cmd="bzip2 -9 ".$root."/".$filename;
        io::out("Creating file ".$filename.".bz2.....");
        exec($cmd,$out,$return);
            io::done('~GREEN~The backup for files of project~~~ '.$name.'~GREEN~ was successfully created!~~~');

    }
}
