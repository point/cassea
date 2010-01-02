<?php


class ErrorsHandler{
    static private $displayErrors = false;
    static private $logErrors = false;
    static private $errorLog = '';
    static private $webTemplate;
    static private $textTemplate;
    static private $rootDir;
    static $inited = false;

    // {{{ init
    static function init(){
        if (self::$inited) return;
		self::$displayErrors = self::iniToBool(ini_get('display_errors'));
        self::$logErrors = self::iniToBool(ini_get('log_errors'));
        self::$errorLog = ini_get('error_log');
        try{
            $c = Config::getinstance();
            $root_dir = $c->root_dir;
            $data_dir = $c->data_dir;
        }catch(Exception $e){
            $root_dir = dirname(dirname(__FILE__));
            $data_dir = '/data';
        }
        self::$webTemplate = $root_dir.$data_dir.'/exceptionTemplate.web.php';
        self::$textTemplate = $root_dir.$data_dir.'/exceptionTemplate.text.php';
        self::$rootDir = $root_dir;
        self::$inited = true;
    }// }}}

    // {{{ setup
    static function setup(){
        set_exception_handler(__CLASS__.'::exceptionHandler');
        set_error_handler(__CLASS__.'::errorHandler');
    }// }}}

    // {{{ exceptionHandler
    static function exceptionHandler(Exception $e){
        self::init();
		$data = array();
		if(method_exists($e, 'getExtra')) $data['extra'] = $e->getExtra();
		$data['type'] = get_class($e);
		$data['message'] = $e->getMessage();
		$data['code'] = $e->getCode();
		$data['file'] = self::trimRootDir($e->getFile());
		$data['line'] = $e->getLine();
		$data['trace'] = self::trimRootDir($e->getTraceAsString());
		self::processError($data);
    }// }}}

    // {{{ errorHandler
    /**
     *
     */
    static function errorHandler($code, $message, $file, $line, $context){
        if (error_reporting() == 0 ) return; 
        self::init();

        //todo:  do it better(binary shifts)
		$code2str['1']= 'E_ERROR';
		$code2str['2'] = 'E_WARNING';
		$code2str['4'] = 'E_PARSE';
		$code2str['8'] = 'E_NOTICE';
		$code2str['16'] = 'E_CORE_ERROR';
		$code2str['32'] = 'E_CORE_WARNING';
		$code2str['64'] = 'E_COMPILE_ERROR';
		$code2str['128'] = 'E_COMPILE_WARNING';
		$code2str['256'] = 'E_USER_ERROR';
		$code2str['512'] = 'E_USER_WARNING';
		$code2str['1024'] = 'E_USER_NOTICE';
		$code2str['2048'] = 'E_STRICT';
		$code2str['4096'] = 'E_RECOVERABLE_ERROR';
		$code2str['8192'] = 'E_DEPRECATED';
		$code2str['16384'] = 'E_USER_DEPRECATED';
		$code2str['30719'] = 'E_ALL';

		$data = array(
			'type' => 'PHP error ('.$code2str[$code].')',
			'message' => $message,
			'code' => $code2str[$code],
			'file' => self::trimRootDir($file),
			'line' => $line,
			'trace' => str_replace(' called at [',' [',self::trimRootDir(self::getTrace(), false))
			);
		self::processError($data);
    }// }}}

    // {{{ processError
    private static function processError($data){
        if (self::$displayErrors)
            echo self::fillTemplate(php_sapi_name() == 'cli'?self::$textTemplate:self::$webTemplate, $data);
        if (self::$logErrors && is_writable(self::$errorLog))
            @file_put_contents(self::$errorLog, '['.date('d-M-Y H:i:s').'] '.self::fillTemplate(self::$textTemplate, $data), FILE_APPEND);
    }// }}}

    // {{{ getTrace
    private static function getTrace(){
		ob_start();
		debug_print_backtrace();
		$trace = ob_get_contents();
		ob_end_clean();
        $ar = array_slice(explode(PHP_EOL, $trace), 2);
        for($i = 0, $c = count($ar); $i < $c; $i++)
            $ar[$i] = preg_replace('/^#\d+ /', '#'.$i.' ' , $ar[$i]);
		return implode(PHP_EOL, $ar);
    }// }}}

    // {{{ fillTemplate
    private static function fillTemplate($templateFile, $data){
        if (!is_file($templateFile) || !is_readable($templateFile)){
            $str = 'Unable Find or Read template File '.$templateFile.PHP_EOL.print_r($data, true);
            $err = fopen('php://stderr', 'w');
            fwrite($err, print_r($data, true));
            fclose($err);
            echo $str;
        }
		ob_start();
		include($templateFile);
		$out = ob_get_contents();
		ob_end_clean();
        return $out;
    }// }}}

    // {{{ iniToBool
    private static function iniToBool($val){
        return  ($val == 'on' || $val == 'yes' || $val == 1 || $val=== true );
    }// }}}

    // {{{ trimRootDir
    private static function trimRootDir($str){
        return preg_replace('#'.self::$rootDir.'/#', '', $str);
    }// }}}
}
