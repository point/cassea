<?php

class CommandException extends Exception {}


class Command {
    protected $name = '';
    protected $commandsSeq;
    protected $hasSubcommands = false;
    //protected $synonym = null;
    protected $help = null;
    /**
     * Working dirrectory of command
     *
     * @var string
     */
    protected $root = '';

    public function __construct( $workingDir = '.', $info, $commandsSeq = array()){
        $this->root = $workingDir;
        $this->parseInfo($info);
        $this->processOptions(ArgsHolder::get());
        $this->commandsSeq = $commandsSeq;
        $this->commandsSeq[] = $this->name;
        //io::out($this->name.' Working Dir: '. $workingDir);
    }

    private function parseInfo($info){
        $this->name = $info['name'];
        $this->hasSubcommands = isset($info['hasSubcommands']);
        //$this->synonym = isset($info['synonym'])?$info['synonym']:null;
        $this->help = $info['help'];   
    }


    protected function processOptions(){
    
    }

    private function getCommands($path = null){
        $path = rtrim(is_null($path)?$this->root:$path, '/\\').'/commands/';
        //IO::out($this->name."___FUNCTION___:".$path, IO::MESSAGE_INFO);
        $d = System::ls($path, System::LS_DIR);
        $ret = array();
        foreach($d as $cmd)
            if (System::is_file($path.$cmd.'/command.xml')) $ret[] = $cmd;
        sort($ret);
        return ($ret);        
    }
    private function getCommandInfo($cmd){
        $cmd_path = $this->root.'commands/'.$cmd.'/command.xml';
        if (!System::is_file($cmd_path)) throw new ConsoleException('Command '.$cmd.' not found in '.$cmd_path);
        $xml = new SimpleXMLElement(file_get_contents($cmd_path));
        $info = array();

        //var_dump($xml);
        $val  = array('class', 'name', 'hasSubcommands');
        foreach($val as $v) if(isset($xml->$v)) $info[$v] = (string)$xml->$v;
        // Help
        $info['help']['short'] = isset($xml->help->short)?$xml->help->short:'';
        if (isset($xml->help))
            $info['help']['src'] = $xml->help;
        //print_r($info);
        return $info;
    }



    public function process(){
            
        //io::out('>>>'.$this->name.'<<<');
        $cmd = ArgsHolder::get()->shiftCommand();
        if ($cmd === false) $cmd = 'default'; 

        if ($this->hasSubcommands && in_array($cmd, $this->getCommands())){
            //IO::out('Proccess Subcommands');
            $ret = $this->processSubcommands($cmd);
        }
        elseif (method_exists($this, ($f='cmd'.strtoupper(substr($cmd,0,1)).substr($cmd,1))))
            $this->$f();
        else
            $this->commandNotFound($cmd);
    }

    private function processSubcommands($cmd){
        //IO::out('~RED~ process Subcommand :'.$cmd.'~~~');
        $info = $this->getCommandInfo($cmd);
        $cmdRoot = $this->root.'commands/'.$cmd.'/';
        //IO::out('cmdRoot: '.$cmdRoot);
        $cmdPath = $cmdRoot.$info['class'].'.php';
        //IO::out('cmdPath: '.$cmdPath);
        //IO::out('info');
        //io::out();
        if (!System::is_file($cmdPath)) throw new ConsoleException('Cant find command file "'.$cmdPath.'" for command "'.$cmd.'"');
        require_once($cmdPath);
        try{
            //IO::out($info['class'].' Process');
            $cmd = new $info['class']($cmdRoot, $info, $this->commandsSeq);
            $cmd->processOptions(ArgsHolder::get());
            $cmd->process();
        }
        catch(Exception $e){
            
            call_user_func(array($info['class'],'processException'),$e);
        }
    }




    protected function cmdHelp(){
        //var_dump($this->help);
        $help = Command::parseHelpNode($this->help['src']);
        IO::out($help['main']);
        IO::out();
        if (!empty($help['description'])){ IO::out($help['description']);IO::out();}

        // SubCommands Short Help
        if ($this->hasSubcommands || count($help['SubCommands'])){
            IO::out("~WHITE~Commands~~~:");
            if ($this->hasSubcommands){
                $subCommands = array(); 
                foreach ($this->getCommands() as $subCmd ){
                    $i = $this->getCommandInfo($subCmd);
                    $subCommands[$subCmd] = $i['help']['short'];
                }
                ksort($subCommands);
                $help['SubCommands'] += $subCommands;
            }
            IO::outOptions($help['SubCommands']);
            IO::out();
        }

        // Command Options
        if (count($help['options'])){
            io::out('~WHITE~Options~~~:');
            io::outOptions($help['options']);
            io::out();
        }

        // Common IO Help
        IO::out(IO::help());

    }


    private function commandNotFound($cmd = null){
        if ($cmd !== 'default') IO::out('Incorrect command ~WHITE~'.$cmd.'~~~', IO::MESSAGE_FAIL);

        return IO::out('Type "~WHITE~'.implode(' ',$this->commandsSeq).' help"~~~ for usage.');


    }


    static function processException($e){

    }

    function parseHelpNode($helpXmlNode){
        $arrHelp = array();
        //$['short'] = isset($xml->help->short)?$xml->help->short:'';o
        $arrHelp['main'] = isset($helpXmlNode->main)?$helpXmlNode->main:'';
        $arrHelp['description'] = isset($helpXmlNode->description)?$helpXmlNode->description:'';
        //parse inClass SubCommands
        $arrHelp['SubCommands'] = array();
        if(isset($helpXmlNode->subcommands) && ($c = count($helpXmlNode->subcommands->cmd)))
            for ($i=0; $i < $c; $i++){
                $attr = $helpXmlNode->subcommands->cmd[$i]->attributes();
                $name = ((string)$attr['name']);
                if (!is_null($name)) $arrHelp['SubCommands'][$name] = ((string)$attr['description']);
            }
        
        // parse options
        $arrHelp['options'] = array();
        if(isset($helpXmlNode->options) && ($c = count($helpXmlNode->options->option)))
            for ($i=0; $i < $c; $i++){
                $attr = $helpXmlNode->options->option[$i]->attributes();
                $name = ((string)$attr['name']);
                if (!is_null($name)) $arrHelp['options'][$name] = ((string)$attr['description']);
            }
        return $arrHelp;
    }
}


