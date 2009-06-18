<?php
/**
 * class represents the reply received from the remote server
 *
 * @package gCurl
 * @author Grigori Kochanov http://www.grik.net/
 * @version 2
 * published under BSD lisense
 */
class gCurlResponse{
    /**
     * Response body
     *
     * @var string
     */
    public $body = '';

    /**
     * HTTP response headers parsed
     *
     * @var array
     */
    public $headers= array('len'=>0);
    
    /**
     * HTTP response status code
     *
     * @var int
     */
    public $status_code;
    
    /**
     * HTTP response class
     *
     * @var int
     */
    public $status_class;
    
    /**
     * Content type of the response body
     *
     * @var string
     */
    public $content_type;
    
    /**
     * Cookies from the response conveniently parsed
     *
     * @var array
     */
    public $cookies=array();

    /**
     * Instance of the implementation of the gCurlHandlers abstract class
     *
     * @var gCurlHandlers
     */
    public $gCurlHandlers;
    
    /**
     * Curl connection handler
     *
     * @var resource
     */
    public $ch;
    
    /**
     * Instance of an gURI class with URI details
     *
     * @var gURI
     */
    public $URI;

    /**
     * Ignore the content-type response header, report the predefined one
     *
     * @var string
     */
    protected $force_content_type='';
        
    /**
     * Response headers handler
     *
     * @var array
     */
    private $headers_handler;

    /**
     * Response body handler function
     *
     * @var array
     */
    private $body_handler;
    
    /**
     * Cookies handler
     *
     * @var array
     */
    private $cookies_handler;
    
    /**
     * Binary flags to define parameters
     *
     * @var int
     */
    private $flags;

    /**
     * Class constructor - needs a Curl handler and a gURI instance as parameters
     *
     * @param resource $ch
     * @param gURI $URI
     */
    function __construct($ch,gURI $URI){
        $this->ch = $ch;
        $this->URI = $URI;
    }
    /**
     * Check the stream for the eof status
     *
     * @return bool
     */
    function isEof(){
        return $this->flags& gCurl::FLAG_EOF;
    }

    /**
     * Set EOF flag for the response
     *
     */
    function setEof(){
        $this->flags|=RESPONSE_HTTP_EOF;
    }

    /**
     * Get header values by header name (case insensitive)
     * 
     * @param string header_name
     * @return array
     */
    public function getHeaderValues($header_name){
        //case insensitive search ...
        $header_name = strtolower($header_name);

        if (isset($this->headers[$header_name])){
            return $this->headers[$header_name];
        }else{
            return array();
        }
    }

    /**
     * Get single header value by header name (case insensitive)
     * Returns NULL if the requested header was not received
     * 
     * @param string header_name
     * @return string
     */
    public function getHeaderByName($header_name){
        //case insensitive search ...
        $header_name = strtolower($header_name);

        if (isset($this->headers[$header_name])){
            if (is_array($this->headers[$header_name])){
                return $this->headers[$header_name][0];
            }elseif ($this->headers[$header_name]){
                return $this->headers[$header_name];
            }
        }else{
            return null;
        }
    }

    /**
     * The function is called for each header line
     *
     * @param resource ch - Curl handler resource
     * @param string header_line - line of a header
     */
    public function headersHandler($ch, $header_line){
        //the length of the header should be returned
        $header_len = strlen($header_line);

        //first call, check if it's an HTTP response status line
        if ($this->flags ^ gCurl::FLAG_HTTP_OK){

            $regexp = "~^HTTP/1\..\s(\d{3})~";
            if (!preg_match ($regexp, $header_line, $found)){
                //Non-HTTP Response Heade
                throw new gCurlException(110);
            }else{
                //ok, it's HTTP
                $this->flags |= gCurl::FLAG_HTTP_OK;
                //get response codes
                $this->headers['status-line'] = rtrim($header_line);
                $this->headers['status-code'] = $this->status_code = $found[1];
                $this->headers['status-class'] = $this->status_class = $this->status_code[0];
                $this->headers['len']=0;
            }
        }


        //end of headers?
        elseif($header_len<3 && ( $header_line[0] == "\r" || $header_line[0] == "\n")){
            //Call headers handler and assign the body handler
            
            //The content type response header can be ignored
            if (!empty($this->force_content_type)){
                $this->headers['content-type'] = $this->content_type = $this->force_content_type;
            }
            //handlers are processed and defined here
            if ($this->gCurlHandlers){
                //call headers handler
                $this->gCurlHandlers->headersHandler($this->headers);
                if ($this->cookies){
                     //call cookies handler
                    $this->gCurlHandlers->cookiesHandler($this->cookies);
                }
                //if the body handler is defined - assign it to CURL
                if ($this->gCurlHandlers->bodyHandlerName ){
                    $body_handler = array($this->gCurlHandlers,$this->gCurlHandlers->bodyHandlerName);
                    if (!is_callable($body_handler)){
                        throw new gCurlException(20);
                    }
                    curl_setopt($this->ch,CURLOPT_WRITEFUNCTION,$body_handler);
                }
            }
        }


        //maybe it's a continue of the previous header?
        elseif($this->headers['len'] >1 && ($header_line[0]==' ' || $header_line[0]=="\t")){
            $last_num = $this->headers['len']--;
            //create the new full header line
            $full_header = $this->headers[$last_num]['name'] .': '.$this->headers[$last_num]['value'] .' '.trim($header_line);
            // reprocess it
            $last_header_name = $this->headers[$last_num]['name'];
            $this->headers[$last_num]['value']=null;
            $this->headers[$last_header_name]['len']--;
            //recursive call - reproduce the header
            $this->headersHandler($ch,$full_header);
        }
        
        //just a generic header line
        else{
            //increase header counter
            $header_num = ++$this->headers['len'];

            //new header - parse it
            $header = explode(':',$header_line,2);
            if (isset($header[1])){
                //header is correct, has a value after the colon
                $name = strtolower(rtrim($header[0]));
                $value = trim($header[1]);
                //create the enumerated list of headers
                $this->headers[$header_num] = array('name'=>$name,'value'=>$value );
                //create the associative array of headers
            }else {
                //it is an invalid header
                $name = 'invalid';
                $value = rtrim($header_line);
            }

            //create the associative array for faster search
            //link the array to the headers value
            if ( empty ($this->headers[$name]['len']) ){
                $this->headers[$name] = array('len' => 1,0=>$this->headers[$header_num]['value']);
            }else{
                $len = ++$this->headers[$name]['len'];
                $this->headers[$name][$len-1] = $this->headers[$header_num]['value'];
            }

            //create shortcuts for the frequently used headers and parse cookies
            switch ($name){
                case 'content-type':
                    $this->headers['content-type'] = $value;
                    $semicolon = strpos($value,';');
                    $charset = '';
                    if($semicolon){
                        $content_type = substr($value,0,$semicolon);
                        $rest = ltrim(substr($value,$semicolon+1));
                        if (substr($rest,0,7) == 'charset'){
                            $charset = substr($rest,8);
                        }
                    }else{
                        $content_type = $value;
                    }
                    $this->headers['ctype'] = $this->content_type = $content_type;
                    $this->headers['charset'] = $charset;
                    break;
                case 'last-modified':
                    $this->headers['last-modified'] = $value;
                    break;
                case 'content-length':
                    $this->headers['content-length'] =$value;
                    break;
                case 'content-disposition':
                    $this->headers['content-disposition']=$value;
                    break;
                case 'set-cookie':
                    $this->parseCookie($value);
                    break;
                //redirect detected
                case 'location':
                    $this->headers['location']=$value;
                    //body can be skipped
                    curl_setopt($this->ch,CURLOPT_NOBODY,true);
                    break;
            }

        }
        return $header_len;
    }

    /**
     * Parses the cookie headers
     *
     * @param string $cookie_header_string
     */
    private function parseCookie($cookie_header_string){
        $cookie=array('name'=>'','value'=>'','expires'=>null,'path'=>'/','domain'=>$this->URI->host,'secure'=>null);

        $cookie_parts = explode(';',$cookie_header_string);
        $cookie_parts_len=sizeof($cookie_parts);

        //separate cookie name
        $eq_sign=strpos($cookie_parts[0],'=');
        if(!$eq_sign){
            //invalid cookie
            return;
        }

        // fetch cookie name and value
        $cookie['name']=rtrim(urldecode(substr($cookie_parts[0],0,$eq_sign)));
        $cookie['value']=urldecode(substr($cookie_parts[0],$eq_sign+1));

        //find other parameters of cookie - expires, domain, path, secure
        for ($part=1;$part<$cookie_parts_len;++$part){
            $eq_sign = strpos($cookie_parts[$part],'=');
            if(!$eq_sign){
                //this parameter has no value, just name
                if (trim($cookie_parts[$part])=='secure'){
                    $cookie['secure']=true;
                }
                continue;
            }
            //check if this is the cookie parameter, if yes - parse it
            $param=strtolower(trim(substr($cookie_parts[$part],0,$eq_sign)));
            $value=substr($cookie_parts[$part],$eq_sign+1);
            switch ($param){
                case 'expires':
                    if (($expires = strtotime(trim($value))) != -1){
                        $cookie['expires_ts'] = $expires;
                        $cookie['expires'] = date('Y-m-d H:i:s', $expires);
                        $cookie['expires_gmt'] = $value;
                    }
                    break;
                case 'max-age':
                    if (is_numeric($value)){
                        $cookie['expires'] = date('Y-m-d H:i:s', time()+$value);
                    }elseif ($expires = strtotime($value) != -1){
                        $cookie['expires'] = date('Y-m-d H:i:s', $expires);
                    }
                    break;
                case 'domain':
                    if ($value){
                        $cookie_domain_dots_count = substr_count($value,'.');
                        $domain_dots_count = substr_count($this->URI->host,'.');
                        $cookie_domain_parts = explode('.',$value,$cookie_domain_dots_count--);
                        $domain_parts = explode('.',$this->URI->host,$domain_dots_count--);
                        $accept_domain = true;
                        while($domain_dots_count>=0 && $cookie_domain_dots_count>=0){
                            if ($cookie_domain_parts[$cookie_domain_dots_count] != $domain_parts[$domain_dots_count] && strlen($cookie_domain_parts[$cookie_domain_dots_count])){
                                $accept_domain=false;
                                break;
                            }
                            $cookie_domain_dots_count--;
                            $domain_dots_count--;
                        }
                        $accept_domain && $cookie['domain'] = $value;
                    }
                    break;
                default:
                    $cookie[$param]=substr($cookie_parts[$part],$eq_sign+1);
            }
        }

        $this->cookies[]=$cookie;

    }

    /**
     * Force reporting the data as given content-type
     *
     * @param string $content_type
     */
    function forceContentType($content_type){
        if ($content_type == 'css'){
            $content_type = 'text/css';
        }elseif ($content_type == 'js'){
            $content_type = 'text/javascript';
        }

        $this->force_content_type=strtolower($content_type);
    }

    /**
     * Pass the object implementing the handlers
     * 
     * @param gCurlHandlers $Handlers
     */
    function setHandlers(gCurlHandlers $Handlers=null){
        if (!$Handlers){
            return ;
        }
        $this->gCurlHandlers = $Handlers;
    }

    /**
     * Clean all fields, flags and handlers
     *
     */
    function cleanup(){
        $this->body=$this->content_type=$this->force_content_type='';
        $this->flags=$this->status_class=$this->status_code=0;
        $this->cookies=array();
        $this->headers=array('len'=>0);
        $this->body_handler=$this->cookies_handler=$this->headers_handler=$this->gCurlHandlers=null;
    }
    
    /**
     * return the response body when the object is echo-ed or casted to string
     *
     * @return string
     */
    function __toString(){
        return $this->body;
    }
//end of the class
}