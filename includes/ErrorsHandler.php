<?php


class ErrorsHandler{
	/**
	 * Template to display Errors/Exceptions to user
	 *
	 * If value is null Errors/Exception will not be displayed.
	 *
	 * The variable $displayTemplate setting up in ErrorsHandler::init()
	 *
	 * @var string|null
	 */
	static private $displayTemplate = null;
	/**
	 * Template to store Errors/Exceptions in log
	 *
	 * If value is null Errors/Exception will not be logged.
	 *
	 * The variable $logTemplate setting up in ErrorsHandler::init()
	 *
	 * @var string|null
	 */
	static private $logTemplate = null;
	/**
	 * Root directory
	 *
	 * self::$rootDir is null means that ErrorsHandler wasn't initalized
	 *
	 * @var string|null
	 */
    static private $rootDir = null;

	// {{{ init
	/**
	 * Initialization
	 *
	 * Determine error handling configuration ans setup correspondent properties.
	 *
	 * This functions wiil be executes one time before first error/eception or never
	 * if error/exception hasn't occur.
	 */
	static function init(){
		if (self::$rootDir) return;
        try{
            $c = Config::getInstance();
            $root_dir = $c->root_dir;
            $data_dir = $c->data_dir;
        }catch(Exception $e){
            $root_dir = dirname(dirname(__FILE__));
            $data_dir = '/data';
		}
		self::$rootDir = $root_dir;
		$textTemplate = $root_dir.$data_dir.'/exceptionTemplate.text.php';
		$webTemplate = $root_dir.$data_dir.'/exceptionTemplate.web.php';

		// errors will be logged 
		if ( self::iniToBool(ini_get('log_errors'))) self::$logTemplate = $textTemplate;

		// errors wiil be displayed
		if (self::iniToBool(ini_get('display_errors')))
			// in console(CLI) and Ajax we preffered to display errors in plain text
			self::$displayTemplate = (php_sapi_name() == 'cli'|| Controller::getInstance()->isAjax())?
				$textTemplate : $webTemplate;
    }// }}}

	// {{{ setup
	/** 
	 * Setup error and exception handlers instead standard one.
	 * And only if error_reporting is on.
	 * Otherwise nothing happends.
	 *
	 * @see Boot
	 */
    static function setup(){
        if (error_reporting() == 0 ) return; 
        set_exception_handler(__CLASS__.'::exceptionHandler');
        set_error_handler(__CLASS__.'::errorHandler');
    }// }}}

	// {{{ exceptionHandler
	/**
	 * Handle Exception, process it a little and send in to ErrorsHandler::processError();
	 *
	 * @param Exception
	 * @return bool true; 
	 */
    static function exceptionHandler(Exception $e){
		$data = array();
		if(method_exists($e, 'getExtra')) $data['extra'] = $e->getExtra();
		$data['type'] = get_class($e);
		$data['message'] = $e->getMessage();
		$data['code'] = $e->getCode();
		$data['file'] = $e->getFile();
		$data['line'] = $e->getLine();
		$data['trace'] = $e->getTraceAsString();
		return self::processError($data);
    }// }}}

    // {{{ errorHandler
	/**
	 * Handle Error, process it a little and send in to ErrorsHandler::processError();
	 *
	 * @return bool true; 
	 */
    static function errorHandler($code, $message, $file, $line, $context){
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
			'file' => $file,
			'line' => $line,
			'trace' => str_replace(' called at [',' [',self::getTrace())
			);
		return self::processError($data);
    }// }}}

    // {{{ processError
	/**
	 * Put formated error/Exception informatio in log/web(STDOUT)
	 * depends on configuration
	 *
	 * @param array $data
	 * @return bool true
	 */
	private static function processError($data){
		self::init();
		// hide absolute path in file and trace
		$data['file'] = self::trimRootDir($data['file']);
		$data['trace'] = self::trimRootDir($data['trace']);
        if (!is_null(self::$displayTemplate)) echo self::fillTemplate(self::$displayTemplate, $data);
		if (!is_null(self::$logTemplate)) error_log(self::fillTemplate(self::$logTemplate, $data));
		return true;
    }// }}}

	// {{{ getTrace
	/**
	 * Get backTrace
	 *
	 * @return string
	 */
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
	/**
	 * Fill given template
	 *
	 * Function check  $teplateFile is file and readable.
	 * If not post error to STDERR ans STDOUT
	 *
	 * @param string $teplateFile full path to teplate file
	 * @param array  $data
	 * @return string
	 */ 
    private static function fillTemplate($templateFile, $data){
        if (!is_file($templateFile) || !is_readable($templateFile)){
            $str = 'Unable Find or Read template File: '.$templateFile.PHP_EOL.PHP_EOL.'Original Error is:'.PHP_EOL.print_r($data, true);
            $err = fopen('php://stderr', 'w');
            fwrite($err, print_r($data, true));
            fclose($err);
            return $str;
		}
		ob_start();
		include($templateFile);
		$out = ob_get_contents();
		ob_end_clean();
        return $out;
    }// }}}

	// {{{ iniToBool
	/**
	 * Convert various values of bool variable of ini file to bool
	 * 
	 * @param mixed $val
	 * @return bool
	 */ 
    private static function iniToBool($val){
        return  ($val == 'on' || $val == 'yes' || $val == 1 || $val=== true );
    }// }}}

	// {{{ trimRootDir
	/**
	 * Hide(trim) root_dir from output
	 *
	 * @param string $str
	 * @return string trimmed string
	 */
    private static function trimRootDir($str){
        return preg_replace('#'.self::$rootDir.'/#', '', $str);
    }// }}}
}
