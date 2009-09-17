<?php

ini_set('include_path', dirname(dirname(__FILE__)).'/');
require_once(dirname(__FILE__)."/../../../includes/Controller.php");
require_once('ArgsHolder.php');
require_once('IO.php');
require_once('System.php');
require_once('Command.php');

ini_set('display_errors', 1);
ini_set('error_reporting', 1);

error_reporting(E_ALL | E_STRICT);


class ConsoleException extends CasseaException {}

class Console{
    const CMD = 'console'; //имя выполняемого файла 

    private $cmdConsoleInfo = array(
        'name' => Console::CMD
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
    }
    static public function getRoot(){
        return self::$root;
    } 
    public function Init(){
        // TODO првоерка параметров php и переменных среды
        //output_buffering=0 \
        // basedir&
        // safe_mode
        //  register_argc_argv="On" \
        // auto_prepend_file="" \
        // auto_append_file="" \
        // error_reporting(0);

        IO::init(ArgsHolder::get());
    }

    public function process(){
        if (!count(ArgsHolder::get()->getCommands())) return IO::out('Type "~WHITE~'.Console::CMD.' help"~~~ for usage.');
        $cmdConsole = new Command(self::getRoot(), $this->cmdConsoleInfo);
        return $cmdConsole->process();
    }

    static function processException($e){
        if ($e instanceof IOException )
            IO::out('IO error : '.$e->getMessage(), IO::MESSAGE_FAIL);
        else IO::out($e, IO::MESSAGE_FAIL);
        
    }
    
    static function initCore(){
        static $isConnected = false;
        if ($isConnected) return;
        $v = IO::getVerboseLevel();
        if ($v > IO::MESSAGE_TEXT ) IO::out('Connecting to Cassea...', false);
        try{
            Controller::makeEnv();
        }
        catch(Exception $e){
            self::processException($e);
        }
        if ($v > IO::MESSAGE_TEXT ) IO::done();
        $isConnected = true;
    }

}
