<?php

class CmdBackup extends Command{
    
    private $separate = false;
	private $send_email = false;
	private $email_address = "";
      
    public function __construct( $workingDir = '.', $info, $commandsSeq = array())
    {
        parent::__construct( $workingDir, $info, $commandsSeq);
    }

    public function process()
    {
        Console::initCore();
        if ($r=ArgsHolder::get()->getOption('separate'))$this->separate=$r;
		if ($r=ArgsHolder::get()->getOption('send-email'))
		{
			$this->send_email = true;
			$this->email_address = (string)$r;
			if(!preg_match(POSTChecker::$email_regexp,$this->email_address))
			{
				io::out( "Incorrect email address format",IO::MESSAGE_FAIL);
				return;
			}
		}
        if (($c = ArgsHolder::get()->shiftCommand()) == 'help') return $this->cmdHelp();
        try{
            $root=Config::get('ROOT_DIR');
            $name=basename($root);
            //Create MySQL dump
            $sqlname=$this->createMySQL($root,$name,$this->separate);
            //Create backup file
            $filename = $this->createTar($root,$name,$this->separate);
            if(!$this->separate)
                unlink($root.'/'.$sqlname);

            if($this->send_email)
            {
                io::out("~GREEN~Sending email to ~~~".$this->email_address);
                $a=Mail::CreateMail();
                $a->toAdd($this->email_address);
                $a->setSubject("Backup ".$filename);
                $a->setFromname(Config::getInstance()->mail->default_from_name);
                $a->setFrom(Config::getInstance()->mail->default_from);
                $a->Message("Backup with filename ".$filename);
                $a->attachAdd($root."/".$filename);
                if($this->separate)
                    $a->attachAdd($root."/".$sqlname);
                $r = $a->send();
                if($r === false)
                    io::out("Sending email to ".$this->email_address." failed",IO::MESSAGE_FAIL);
                else io::done("Email sended to ".$this->email_address);
            }
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
        
        io::out("Creating Mysql dump .....", false);
        $cmd='mysqldump -R -q --single-transaction '.$db_db.' -u'.$db_user.' --password='.$db_password.' --result-file='.$root.'/'.$sqlname;
        exec($cmd,$out,$return);
        //if($return) return;//PERMISSIONS for SHOW functions and procedures... 
        if($separate)
        {
            $cmd='bzip2 -9 '.$root.'/'.$sqlname;
            exec($cmd,$out,$return);
            if($return) return;
        }
        io::done();
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
        IO::out('Packing main... ', false);
        $cmd="tar --exclude='web' --exclude='.svn' --exclude='*~' -cvf ".$root."/".$filename." *";
        exec($cmd,$out,$return);
        if ($return ==0) io::done();
        if (IO::getVerboseLevel() == IO::MESSAGE_INFO || $return)
            foreach($out as $o)
                io::out($o);
        if($return){
            io::out('Return code '.$return, IO::MESSAGE_FAIL);
            io::out('Executed command: '.$cmd);
            return;
        }

        io::out('Adding web/css, web/js...', false);
        $cmd="tar --exclude='*~'  --exclude='.svn'  -uvf ".$root."/".$filename." ./web/css/ ./web/js/ ";
        exec($cmd,$out,$return);
        if ($return ==0) io::done();
        if (IO::getVerboseLevel() == IO::MESSAGE_INFO || $return)
            foreach($out as $o)
                io::out($o);

        if($return){
            io::out('Return code '.$return, IO::MESSAGE_FAIL);
            io::out('Executed command: '.$cmd);
            return;
        }

        io::out('Bzip '.$filename.'...', false);
        $cmd="bzip2 -9 ".$root."/".$filename;
        exec($cmd,$out,$return);
        if ($return ==0) io::done();
        if (IO::getVerboseLevel() == IO::MESSAGE_INFO || $return)
            foreach($out as $o)
                io::out($o);

        if($return){
            io::out('Return code '.$return, IO::MESSAGE_FAIL);
            io::out('Executed command: '.$cmd);
            return;
        }
		return $filename.".bz2";

    }
}
