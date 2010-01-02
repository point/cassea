<?php
/**
 * This file contains class gCurl, abstract class gCurlHandlers and exception class gCurlException.
 * It requires gCurlRequest and gCurlResponse classes as well
 * 
 * @package gCurl
 * @author Grigori Kochanov http://www.grik.net/
 * published under BSD lisense
 */

//Load package classes
    require_once('gCurlRequest.class.php');
    require_once('gCurlResponse.class.php');
    require_once('URI.class.php');

//load the exception base class
    require_once('gksException.class.php');

/**
 * The class extending this interface will contain methods that will be used as handlers
 * for processing HTTP response
 * 
 * @package GCurl
 * @author Grigori Kochanov
 * @version 2
 * @abstract 
 */
abstract class gCurlHandlers{
    /**
     * Name of the method to handle the response body.
     * You can redefine it while processing response headers or cookies.
     * Use NULL to avoid setting a handler for the body.
     *
     * @var string
     */
    public $bodyHandlerName = 'bodyHandler';
    
    /**
     * Instance of the gCurl class utilizing this handler
     *
     * @var gCurl
     */
    protected $gCurl;
    
    /**
     * The handler method triggered after the response headers are received and processed
     * but before receiving the body
     * 
     * @param array $headers
     */
    function headersHandler(array $headers){}
    
    /**
     * The method is triggered after the response headers are received,
     * it receives an array of cookies set by the server as parameter
     *
     * @param array $cookies
     */
    function cookiesHandler(array $cookies){}
    
    /**
     * Default body handler
     *
     * @param string $chunk
     */
    function bodyHandler($ch, $chunk){}
        
    /**
     * Set the reference to the gCurl object that uses this class methods as handlers
     *
     * @param gCurl $gCurl
     */
    final function setGCurlReference(gCurl $gCurl){
        $this->gCurl = $gCurl;
    }
    
    /**
     * Destructor - to avoid a circular reference
     *
     */
    final function cleanGCurlReference(){
        $this->gCurl = null;
    }
}

/**
 * A class to simplify complex tasks for performing and processing HTTP requests with CURL
 *
 * @package GCurl
 * @author Grigori Kochanov
 * @version 2
 */
class gCurl {
    
    /**
     * Error number returned by cURL
     *
     * @var int
     */
    public $curl_errno=0;
    
    /**
     * Error text returned by cURL
     *
     * @var string
     */
    public $curl_error='';
    
    /**
     * instance of the URI class
     *
     * @var gURI
     */
    public $URI;
    
    /**
     * CURL resource handler
     *
     * @var resource
     */
    public $ch;
    
    /**
     * Full URL requested
     *
     * @var string
     */
    protected $location_href= '';
    
    /**
     * Instance of the gCurlRequest object
     *
     * @var gCurlRequest
     * @see gCurlRequest.class.php
     */
    public $Request;
    /**
     * Response object reference
     *
     * @var gCurlResponse
     * @see gCurlResponse.class.php
     */
    public $Response;
    
    /**
     * Flag that defines if cURL should automatically follow the "Location" header or not
     *
     * @var bool
     */
    private $followlocation=0;
    
    /**
     * Flag that defines if cURL should return the body of the response
     *
     * @var bool
     */
    private $return_transfer=1;
    
    /**
     * System network interface (IP)
     * 
     * @var string
     */
    private $interface=null;
    
    
    /**
     * Constants - flags
     */
    const 
    HTTP_BODY = 1,
    HTTP_HEADERS=2,
    HTTP_FULL=3;
    /**
     * sets the status of the data to show the end
     */
    const FLAG_EOF=1;
    /**
     * the HTTP response is received
     */
    const FLAG_HTTP_OK=2;
    /**
     * headers are received and processed
     */
    const FLAG_HEADERS_RECEIVED=4;
    
    /**
     * Constructor of the class
     *
     * @return void
     */
    function __construct($url,$method='GET'){
        if (!defined('CURLE_OK')){
            throw new gCurlException(10);
        }
        //init service objects
        $this->URI = new gURI();
    
        $this->ch = curl_init();
        if ($this->catchCurlError() || !$this->ch){
            throw new gCurlException(15);
        }
    
    
        //define basic parameters
        curl_setopt ($this->ch, CURLOPT_HEADER, 0);
        curl_setopt ($this->ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt ($this->ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt ($this->ch, CURLOPT_ENCODING, '');
        curl_setopt ($this->ch, CURLOPT_RETURNTRANSFER, 1);
        
        //create request and response objects
        $this->Request= new gCurlRequest();
        $this->Response = new gCurlResponse($this->ch,$this->URI);
        
        //set the headers handler
        curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, array($this->Response,'headersHandler'));

        //prepare the URL to browse to
        $this->URI->process($url);
        $this->location_href = $this->URI->full;
    
        $this->Request->setRequestMethod($method);
        if (strcasecmp($method, 'POST')==0){
            curl_setopt ($this->ch, CURLOPT_POST, 1);
        }
    }

    /**
     * signal a redirect URL
     *
     * @param string $new_uri
     */
    function redirect($new_uri){
        $this->URI->parse_http_redirect($new_uri);
        $this->location_href = $this->URI->full;
        
        //create request and response objects
        $this->Request = new gCurlRequest();
        $this->Response->cleanup();
    }

    /**
     * Define whether to return the transfer or not
     *
     * @param bool $value
     */
    function returnTransfer($value){
        $this->return_transfer=(bool)$value;
    }
    
    /**
     * sets the time limit of time the CURL can execute
     *
     * @param int $seconds
     */
    function setTimeout($seconds){
        curl_setopt($this->ch,CURLOPT_TIMEOUT,$seconds);
        if ($this->catchCurlError()){
            throw new gCurlException(22);
        }
    }
    
    /**
     * Set the network interface for the outgoing connection
     *
     * @param string $interface
     */
    function setInterface($interface){
        $this->interface = $interface;
        curl_setopt($this->ch,CURLOPT_INTERFACE,$this->interface);
    }
    
    /**
     * Set extra options for the connection
     *
     * @param array $options
     */
    function setOptions(array $options){
        curl_setopt_array($this->ch,$options);
    }
    
    /**
     * Run the CURL engine
     *
     * @return gCurlResponse
     */
    function exec(){
        //add cookies to headers
        if ($this->Request->cookie_string){
            $this->Request->registerCustomHeader('Cookie: '.$this->Request->cookie_string);
        }
        //process user-defined request headers
        if ($this->Request->custom_headers){
            curl_setopt ($this->ch, CURLOPT_HTTPHEADER, $this->Request->custom_headers);
        }
        //prepare the POST data
        if ($this->Request->method=='POST' && $this->Request->post_data){
            curl_setopt ($this->ch,CURLOPT_POSTFIELDS, $this->Request->post_data);
        }
        //use proxy if defined
        if ($this->Request->proxy && $this->Request->proxy_port){
            curl_setopt ($this->ch, CURLOPT_PROXY, $this->Request->proxy);
            curl_setopt ($this->ch, CURLOPT_PROXYPORT, $this->Request->proxy_port);
            if($this->Request->proxyuser){
                curl_setopt ($this->ch, CURLOPT_PROXYUSERPWD, $this->Request->proxyuser.':'.$this->Request->proxypwd);
            }
        }
        
        //set the URI to connect to
        curl_setopt ($this->ch, CURLOPT_URL, $this->location_href);
        curl_setopt($this->ch,CURLOPT_NOBODY,!$this->return_transfer);

        //run the request
        if ($this->return_transfer){
            $result = curl_exec($this->ch);
        }else{
            curl_exec($this->ch);
            $result='';
        }
        //clear the reference in the handler to avoid circular references
        if ($this->Response->gCurlHandlers){
            $this->Response->gCurlHandlers->cleanGCurlReference();
        }
        
        if ($this->return_transfer && !$result && !$this->Response->headers['len']){
            throw new gCurlException(115);
        }
        //return the response data if required
        if ($this->return_transfer && is_string($result)){
            $this->Response->body = $result;
        }
        return $this->Response;
    }

    /**
     * Close the connection on object destruction
     *
     */
    function __destruct(){
        $this->disconnect();
    }
    
    /**
     * close connection to the remote host
     *
     */
    function disconnect(){
        if (is_resource($this->ch)){
            curl_close($this->ch);
        }
        $this->ch = NULL;
    }
    
    /**
     * check the memory consumption
     *
     */
    function checkMemoryConsumption(){
        if (memory_get_usage()>MEMORY_USAGE_LIMIT*1024){
            throw new gCurlException(60);
        }
    }
    
    /**
     * Check for an error
     *
     * @return bool
     */
    function catchCurlError(){
        if(!is_resource($this->ch) || !($curl_errno=curl_errno($this->ch))){
            return false;
        }
        $this->curl_errno = $curl_errno;
        $this->curl_error = curl_error($this->ch);
        throw new gCurlException(80,$curl_errno,$this->curl_error);
        return true;
    }
    
    /**
     * Pass the object implementing the handlers
     * 
     * @param gCurlHandlers $Handlers
     */
    function setHandlers(gCurlHandlers $Handlers){
        $Handlers->setGCurlReference($this);
        $this->Response->setHandlers($Handlers);
    }
//end of the class
}



/**
 * Exceptions for gCurl
 *
 */
class gCurlException extends Exception implements gksException {

    /**
     * The list of exception codes
     *
     * @var array
     */
    private $exception_codes= array(
        1=>'Connection error',
        10=>'Curl extension not loaded',
        15=>'Could not initialize CURL',
        20=>'Invalid handler method name',
        21=>'Error assigning the output stream for headers',
        22=>'Error setting CURL timeout',
        23=>'Error setting URL to connect to',
        50=>'Invalid request method',
        51=>'Invalid request parameters',
        60=>'Out of memory',
        70=>'Headers already sent to the user agent',
        80=>'CURL reported error',
        90=>'Invalid delay value',
        110=>'Non-HTTP response headers',
        115=>'Curl returned empty result after execution',
        120=>'Invalid host of the requested URI',
        125=>'Invalid URI',
        130=>'Redirects limit reached',
    );

    /**
     * Error number for CURL operation
     *
     * @var int
     */
    public $curl_errno;
    
    /**
     * Error message returned by CURL
     *
     * @var string
     */
    public $curl_error='';
    
    /**
     * Initialize the exception
     *
     * @param int $code
     * @param int $curl_errno
     * @param string $curl_error
     */
    function __construct($code, $curl_errno=0, $curl_error=''){
        //get the error description
        key_exists($code, $this->exception_codes) || $code=1;
        $message= $this->exception_codes[$code]; 
        if ($curl_errno){
            $message.="\nCurl Error #: ".$curl_errno;
        }
        if ($curl_error){
            $message.="\nError message: ".$curl_error;
        }
        //set the error string through the Exception class constructor
        parent::__construct($message, $code);
        
    }
    
    /**
     * Get the message prepared to write to the log file
     *
     * @return string
     */
    function getLogMessage(){
        $log_string='Exception '.$this->getCode().':'.$this->message."\n";
        if ($this->getCode() != 80){
            $log_string .= 'line '.$this->getLine().' file '.$this->getFile()."\n".$this->getTraceAsString()."\n";
        }
        return $log_string;
    }
    
    /**
     * Get the error message to output to the browser
     *
     * @return string
     */
    function getHtmlMessage(){
        $message='<b>Exception '.$this->getCode().'</b>: '.$this->message."<br>\n";
        if ($this->getCode() != 80){
            $message .= 'file '.$this->getFile()."\n<br> line ".$this->getLine().
            "<br>\nTrace: <br />\n".nl2br($this->getTraceAsString())."<br>\n";
        }
        return $message;
    }

//class end
}
