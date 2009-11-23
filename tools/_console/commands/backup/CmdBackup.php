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
        $this->separate = ArgsHolder::get()->getOption('separate');
        $this->all = ArgsHolder::get()->getOption('all');
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
                io::out("Sending email to ~WHITE~".$this->email_address.'~~~', false);
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
                    io::out('',IO::MESSAGE_FAIL);
                else {
                    io::done();
                    if ( ArgsHolder::get()->getOption('clean')){
                        IO::out('Cleaning ', false);
                        if($this->separate) {
                            IO::out($sqlname.' ',false);
                            unlink($root.'/'.$sqlname);
                        }
                            IO::out($filename.' ',false);
                            unlink($root.'/'.$filename);
                            IO::done();
                    }
                }
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

        $db=Config::getInstance()->db;
        $db = parse_url($db);
        
        io::out("Creating Mysql dump .....", false);
        $cmd='mysqldump -R -q --single-transaction '.trim($db['path'],'/').' -u'.$db['user'].' --password='.(isset($db['pass'])?$db['pass']:"").' --result-file='.$root.'/'.$sqlname.'  2>&1';
        exec($cmd,$out,$return);
        if($return){
            io::OUt();
            if (IO::getVerboseLevel()>IO::MESSAGE_FAIL){
                io::out('mysqldump return code '.print_r($return,true), IO::MESSAGE_FAIL);
                io::out('~WHITE~Command~~~: '. $cmd);
                IO::out('~WHITE~Output ~~~:'.implode(PHP_EOL,$out)); 
            }
            throw new ConsoleException('mysqldump finished with errors: '.PHP_EOL.implode(PHP_EOL,$out));
        }
        if($separate)
        {
            $cmd='bzip2 -9 '.$root.'/'.$sqlname.' 2>&1';
            exec($cmd,$out,$return);
            if(!$return) $sqlname.='.bz2';
            else{
                io::OUt();
                if (IO::getVerboseLevel()>IO::MESSAGE_FAIL){
                    io::out('Bzip  return code '.print_r($return,true), IO::MESSAGE_FAIL);
                    io::out('~WHITE~Command~~~: '. $cmd);
                    IO::out('~WHITE~Output ~~~:'.implode(PHP_EOL,$out)); 
                }
                throw new ConsoleException('Bzip finished with errors: '.PHP_EOL.implode(PHP_EOL,$out));
            }
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
        IO::out('Packing main... '."\t", false);
        if($this->all)
            $cmd="tar --exclude='.svn' --exclude='*~' -cvf ".$root."/".$filename." * 2>&1";
        else
            $cmd="tar --exclude='web' --exclude='.svn' --exclude='*~' -cvf ".$root."/".$filename." * 2>&1";
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

        if(!$this->all)
        {
            $cmd="tar --exclude='*~'  --exclude='.svn'  -uvf ".$root."/".$filename." ./web/css/ ./web/js/  2>&1";
            io::out('Adding web/css, web/js...', false);
        }
        exec($cmd,$out,$return);
        if (($return ==0) && !$this->all) io::done();
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
