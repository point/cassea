<?php

class IOException extends Exception{}

class IO{

    /* Dialog Const */
    const NONE = 32;
    const OK = 1;
    const CANCEL = 2;
    const YES = 4;
    const NO = 8;
    const ALL = 16;

    /* Input const */
    const TYPE_INT = '1';
    const TYPE_STRING = '2';
    const TYPE_FLOAT = '3';
    const TYPE_CHAR = '4';

    /* Message const */
    const MESSAGE_OK   = -1;
    const MESSAGE_FAIL = 1;
    const MESSAGE_TEXT = 2;
    const MESSAGE_WARN = -2;
    const MESSAGE_INFO = 4;

    static private $answText = array(
        IO::NONE => "Press Enter",
        IO::OK => 'Ok',
        IO::CANCEL => 'Cancel',
        IO::YES => 'Yes',
        IO::NO => 'No',
        IO::ALL => 'All'
    );

    static private $verboseLevel = IO::MESSAGE_TEXT;
    static private $assumeYes = false;

    static private $useColor = true;
    static private $colors = array(
        '/~~~/' => "\033[0m",
        '/~RED~/' => "\033[31m",
        '/~GREEN~/' => "\033[32m",
        '/~BROWN~/' => "\033[33m",
        '/~BLUE~/' => "\033[34m",
        '/~PURPLE~/' => "\033[35m",
        '/~CYAN~/' => "\033[36m",
        '/~SILVER~/' => "\033[37m",
        
        '/~GRAY~/' => "\033[1;30m",
        '/~WHITE~/' => "\033[1;37m",


        '/~DEF~/' => "\033[39m",
        );

    static function init(ArgsHolder $ah){
        if ($ah->getOption('v')) self::$verboseLevel = IO::MESSAGE_INFO;
        if ($ah->getOption('q')) self::$verboseLevel = IO::MESSAGE_FAIL;
        if ($ah->getOption('C')) self::$useColor = false;
        if ($ah->getOption('y') || self::$verboseLevel == IO::MESSAGE_FAIL) self::$assumeYes = true;
    }

    static function getVerboseLevel(){
        return self::$verboseLevel;
    }
    static function dialog($message, $answ = IO::NONE, $default = null){
        IO::out($message, false);
        $a = array();
        if ($answ & IO::NONE) $answ =  $default = $a[0] = IO::NONE;
        else{
            if ($answ & IO::YES) $a[] = IO::YES;
            if ($answ & IO::OK) $a[] = IO::OK;
            if ($answ & IO::NO) $a[] = IO::NO;
            if ($answ & IO::CANCEL) $a[] = IO::CANCEL;
            if ($answ & IO::ALL) $a[] = IO::ALL;
        }
        if (is_null($default)) $default = $a[0]; 
        for($i = 0, $c = count($a); $i < $c; $i++){
            $avaible[$i] = self::$answText[$a[$i]];
            if ($a[$i] == $default) $avaible[$i] = '~WHITE~'.$avaible[$i].'~~~';
        }

        $str = ' [ '.implode(' / ',$avaible).' ] ';
        IO::out($str, false);

        if (self::$assumeYes){ IO::out(null);  return $default;}

        $in = IO::in(IO::TYPE_CHAR);
        if ($answ == IO::NONE) return null;
        if (is_null($in)) return $default;

        for($i = 0, $c =count($a); $i < $c; $i++)
            if (strtolower($in) == substr(strtolower(self::$answText[$a[$i]]),0,1)) return $a[$i];
        
        throw new IOException('Incorrect input');
    }

    static function out($message = '', $nl = true){
        $type = IO::MESSAGE_TEXT;
        if (!is_bool($nl))  $type=$nl and $nl=true;
        if (self::$verboseLevel < $type) return;
        switch($type){
            case IO::MESSAGE_TEXT:break;
            case IO::MESSAGE_WARN:$message = "[ ~BROWN~WARN~~~ ] ".$message;break;
            case IO::MESSAGE_FAIL:$message = "[ ~RED~FAIL~~~ ] ".$message;break;
            case IO::MESSAGE_INFO:$message = "[ ~BLUE~INFO~~~ ] ".$message;break;
            case IO::MESSAGE_OK:$message = "[ ~GREEN~ OK ~~~ ] ".$message;break;
        }

        //process colors
        if (IO::$useColor) $r = self::$colors;
        else $r = array('/~[A-Z]+~|~~~/' =>  '');
        $message = preg_replace(array_keys($r),array_values($r), $message);
        if ($nl) $message.="\r\n";
        $stdout = fopen('php://stdout', 'w');
        fwrite($stdout, $message);
        //echo $message;
    }

    static function done($message='', $type = IO::MESSAGE_TEXT){
        return IO::out($message."\t ~GREEN~Ok~~~",$type);
    }


    static function in($type = IO::TYPE_STRING){
        $stdin = fopen('php://stdin', 'r'); 
        //$line = trim(fgets($stdin)); 
        fscanf($stdin, "%s\n", $string); 
        if (is_null($string)) return null;
        switch ($type){
        case IO::TYPE_INT: $string = is_numeric($string)? 0+$string:null; break;
        case IO::TYPE_FLOAT:$string = is_numeric($string)? 0.0+$string:null; break;
        case IO::TYPE_CHAR: $string = $string[0];break;
        }
        return $string;
    }


    static function outOptions($opts){
        $maxKey = 0;
        $maxValue = 0;
        foreach ($opts as $k => $v){
            if ($maxKey < ($m = strlen($k))) $maxKey = $m;
            if ($maxValue < ($m = strlen($v))) $maxValue = $m;
        }

        $format = "  %-".$maxKey."s  %s";
        foreach($opts as $k => $v){
            IO::out(sprintf($format, $k, $v));
        }
    
    }

    static function help(){
        return <<<HELP
~WHITE~Input/Output options~~~:
  -v    vebose output
  -q    be quiet: show only error messages, choose default answer for all questions
  -C    don't use VT colour
  -y    choose default answers for all questions

HELP;
    }
}

