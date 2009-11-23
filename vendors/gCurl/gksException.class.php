<?php
/**
 * The interface defines the methods that prepare the log message, HTML-formatted message
 *
 * @author Grigori Kochanov http://www.grik.net/
 * published under BSD lisense
 */
interface gksException{
    /**
     * Prepare the message to write to the log
     *
     * @return  string
     */
    public function getLogMessage();
    
    /**
     * returns the exception dump prepared for a browser
     * 
     * @return string
     */
    public function getHtmlMessage();
}

/*    EXCEPTIONS   */
class pException extends Exception implements gksException {

static private $error_codes;
public $httpResponceCode=500;
public $defaultErrorMessage;

/**
 * @param $code int - error code from [lang].errors.php
 * @desc returns the value of the configuration parameter (from the database)
 */
function __construct($code){
    if (!self::$error_codes){
        $errors_file=_Config::$CONFIG_DIR.'/errors.php';
        self::$error_codes = include($errors_file);
    }
    $this->defaultErrorMessage=self::$error_codes[1];

    key_exists($code, self::$error_codes) || $code=1;
    $message=self::$error_codes[$code];
    parent::__construct($message, $code);
}

/**
 * Create the message to write to the log
 *
 * @return  string
 */
function getLogMessage(){
    $log_string='Exception: '.$this->getMessage().' ('.$this->getCode().')'."\n".
        'line '.$this->getLine().' file '.$this->getFile()."\n".$this->getTraceAsString()."\n";
    return $log_string;
}

public function getHtmlMessage(){
    $message = 'Exception: '. nl2br ($this->getMessage());
    $trace = 'TRACE: <br />'. nl2br ($this->getTraceAsString());
    return '<p style="font-weignt: bold; padding: 10px;">'."\n".$message."<br />\n".$trace."</p>\n";
}
//class end
}