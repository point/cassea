<?php

ini_set('include_path', dirname(dirname(__FILE__)).'/');
//print_r(ini_get('include_path'));
require_once('ArgsHolder.php');
require_once('IO.php');
require_once('System.php');
require_once('Command.php');
require_once('FS.php');


class ConsoleException extends Exception {}

class Console{
    const CMD = 'console';

    private $cmdConsoleInfo = array(
        'name' => Console::CMD,
        'shortHelp'=> '',// не нужен для консоли
        'hasSubcommands' => true,
        'help' => array()
        );

    static private $instance = null;
    static private $root ='';

    static function getInstance(){
        if (is_null(self::$instance))
            self::$instance = new Console();
        return self::$instance;
    }

    private function __construct(){
        self::$root = dirname(dirname(__FILE__)).'/';
        $xml = new SimpleXMLElement(file_get_contents(self::$root.'/commands/ConsoleCommand.xml'));
        $this->cmdConsoleInfo['help']['src'] = $xml->help;
    }
    static public function getRoot(){
        return self::$root;
    } 
/*
    static public function getCommands($path = null){
        $path = rtrim(is_null($path)?self::getRoot():$path, '/\\').'/commands/';
        $d = System::ls($path, System::LS_DIR);
        $ret = array();
        foreach($d as $cmd)
            if (System::is_file($path.$cmd.'/command.xml')) $ret[] = $cmd;
        sort($ret);
        return ($ret);        
    }

    static public function getCommandInfo($path, $cmd){
        $cmd_path = $path.'commands/'.$cmd.'/command.xml';
        if (!System::is_file($cmd_path)) throw new ConsoleException('Command '.$cmd.' not found in '.$cmd_path);
        $xml = new SimpleXMLElement(file_get_contents($cmd_path));
        $info = array();

        //var_dump($xml);
        $val  = array('class', 'name', 'hasSubcommands', 'shortHelp');
        foreach($val as $v) if(isset($xml->$v)) $info[$v] = (string)$xml->$v;
        $info['help'] = $xml->help;
        //print_r($info);
        return $info;
    }

*/  
    public function Init(){

        // првоерка параметров php и переменных среды
        //output_buffering=0 \
        // basedir&
        // safe_mode
        //  register_argc_argv="On" \
        // auto_prepend_file="" \
        // auto_append_file="" \
        error_reporting(0);
        
        IO::init(ArgsHolder::get());
        /* 2trash */
        /*
        IO::out('fack',IO::MESSAGE_FAIL);
        IO::out(' Warning',IO::MESSAGE_WARN);
        IO::out(' првоерка параметров php и переменных среды',false);
        IO::done();
        IO::out('auto_prepend_file=""', IO::MESSAGE_INFO);
        IO::done('auto_prepend_file=""', IO::MESSAGE_INFO);

        $res = IO::dialog('first dialog', IO::YES | IO::NO | IO::ALL, IO::ALL);
        var_dump($res);
        $res = IO::dialog('first dialog');
        var_dump($res);
        IO::out(null);
        */
    }

    public function process(){
        if (!count(ArgsHolder::get()->getCommands())) return IO::out('Type "~WHITE~'.Console::CMD.' help"~~~ for usage.');
        
        $cmdConsole = new Command(self::getRoot(), $this->cmdConsoleInfo);
        return $cmdConsole->process();

    }

    static function processException($e){
        if ($e instanceof IOException )
            IO::out('IO error : '.$e->getMessage(), IO::MESSAGE_FAIL);
        else echo $e;
    }
    
    static function initCore(){
        static $isConnected = false;
        if ($isConnected) return;
        IO::out('Connecting to Cassea...', false);
        
        require_once(dirname(dirname(dirname(dirname(__FILE__))))."/includes/Controller.php");
        Config::init(new IniDBConfig("config.ini","config"));
        $config = Config::getInstance();
        DB::init($config->db->host,$config->db->user,$config->db->password,$config->db->db);
        Language::init();
        
        IO::done();
        $isConnected = true;
    }

}
