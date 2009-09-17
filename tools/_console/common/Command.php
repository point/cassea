<?php

class CommandException extends CasseaException {}


class Command {
    protected $name = '';
    protected $defaultCommand = 'help';
    protected $commandsSeq;
    protected $hasSubcommands = false;
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
        $this->hasSubcommands = count($this->getCommands());
    }

    private function parseInfo($info){
        $this->name = $info['name'];
        if (isset($info['default'])) $this->defaultCommand = $info['default'];
        $this->hasSubcommands = isset($info['hasSubcommands']);
        //$this->synonym = isset($info['synonym'])?$info['synonym']:null;
        $this->help = $info['help'];   
    }


    protected function processOptions(ArgsHolder $ah){
    
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
        $val  = array('class', 'name', 'default');
        foreach($val as $v) if(isset($xml->$v)) $info[$v] = (string)$xml->$v;
        // Help
        $info['help']= $this->parseHelpFile($this->root.'commands/'.$cmd.'/help.txt');
        return $info;
    }

    public function process(){
        //io::out('>>>'.$this->name.'<<<');
        $cmd = ArgsHolder::get()->shiftCommand();
        if ($cmd === false) $cmd = $this->defaultCommand; 

        if (in_array($cmd, $this->getCommands())){
            //IO::out('Proccess Subcommands');
            return $this->processSubcommands($cmd);
        }
        
        elseif ( false !== $this->findInclassCommand($cmd)){
            try{ return $this->processInclassCommand($cmd); }
            catch(Exception $e){
                $this->processException($e, get_class($this));
            }
        }
        else
            return $this->commandNotFound($cmd);
    }

    /**
     * Проверяет существование метода реализующего указнную комманду.
     *
     * @param string $cmd комманда
     * @return bool true if method callable
     */
    private function findInclassCommand($cmd){
        return is_callable(array($this,Command::cmdToMethod($cmd )));
    }

    private function processInclassCommand($cmd){
        if (ArgsHolder::get()->isHelp()) $this->cmdHelp($cmd);
        else
            return $this->{Command::cmdToMethod($cmd)}();
    }

    // {{{ cmdToMethod
    /**
     * Преобразовывает команду в имя метода.
     *
     * Праобразование происходит по схеме:
     * <code>
     * $cmd= 'mycommand';
     * echo cmdToMethod($cmd); // cmdMycommand
     * </code>
     *
     * @param string $cmd
     * @return string
     */
    protected static function cmdToMethod($cmd){
        return 'cmd'.strtoupper(substr($cmd,0,1)).substr($cmd,1);
    }//}}}

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
            if (ArgsHolder::get()->isHelp())  
                $cmd->cmdHelp();
            else{
                $cmd->processOptions(ArgsHolder::get());
                return  $cmd->process();
            }
        }
        catch(Exception $e){
            call_user_func(array($info['class'],'processException'),$e, $info['class']);
        }
    }
    protected function parseHelpFile($helpFile = null ){
        $helpFile = is_null($helpFile)?($this->root.'/help.txt'):$helpFile;
        if (false === ($h = file_get_contents( $helpFile)) ) throw new CommandException('help.txt for command '.get_class($this).' not found.'.PHP_EOL.'Help File: '.$helpFile);
        $h = str_replace('COMMAND_PATH', implode(' ', $this->commandsSeq), $h);
        $ha = explode(PHP_EOL, $h);
        $command = '';
        $part = '';
        $option = null;
        $help = array(
            'short' => '',
            'inclass' => array()
        );

        $help = array();
        foreach($ha as $l){            
            if (trim($l) == '' || substr($l,0,2) == '##') continue;
            if ($part = 'short') $part = 'main';

            if ( strpos($l, 'Short:')===0) {$part = 'short'; $l = trim(substr($l,strlen('Short:'))); $option=null;}
            elseif(strpos($l, 'Main:') ===0) {$part = 'main'; $l = trim(substr($l,strlen('Main:'))); $option=null;}
            elseif(strpos($l, 'Command:') ===0 &&  $command !=($newCommand = strtolower(trim(substr($l,strlen('Command:')))) )) {$command = $newCommand; $option=null; $l ='';}
            elseif(strpos($l, 'Option:') ===0 &&  $option !=($newOption = trim(substr($l,strlen('Option:'))) )){ $option = $newOption; $l='';}
            if ($l=='') continue;
            if (trim($l) == '.') $l = PHP_EOL;

            if (!is_null($option)){
                if ($l != ''){
                    if (!isset($help[$command]['options'][$option])) $help[$command]['options'][$option] = '';
                    $help[$command]['options'][$option]  .=$l.PHP_EOL;
                }
            }
            else {
                if (!isset($help[$command][$part])) $help[$command][$part] = '';
                $help[$command][$part]  .=$l.PHP_EOL;
            }
        }
        $r = $help[''];
        unset($help['']);
        $r['inclass'] = $help;

        if (empty($r['short'])) $r['short'] = '~RED~TODO~~~ Write Help in '.$helpFile;
        return $r;
    }


    protected function cmdHelp($command = null){
        $help = $this->parseHelpFile();
        $subCommands =  is_null($command)?$this->getCommands():array();

        if (!is_null($command))
            $help = $help['inclass'][$command];
            

        IO::out($help['short']);
        if (!empty($help['main'])) IO::out($help['main']);

        $list = array();
        foreach ($subCommands as $subCmd ){
            $i = $this->getCommandInfo($subCmd);
            $list[$subCmd] = trim($i['help']['short']);
        }
        if (isset($help['inclass']) && count($help['inclass']))
        foreach ($help['inclass'] as $inCmd => $v  ){
            $list[$inCmd] = trim($v['short']);
        }
        if (count($list)){
            ksort($list, SORT_STRING);
            IO::out("~WHITE~Commands~~~:");
            IO::outOptions($list);
            IO::out();
        }


        // Command Options
        if (isset($help['options']) && count($help['options'])){
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

    static function processException($e, $cmdName = ''){
        IO::out('Command Exception ~WHITE~'.$cmdName.'~~~', IO::MESSAGE_FAIL);
        Console::processException($e);
    }

    /*
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
    }  */
}
