<?php
/**
 * This file contains class gCurl, interface gCurlHandlers and exception class gCurlException.
 * It requires gCurlRequest and gCurlResponse classes as well
 * 
 * @package gCurl
 * @author Grigori Kochanov http://www.grik.net/
 * @version 2.7
 */
//Load package classes
if (!class_exists('gCurlRequest',false)){
    require(dirname(__FILE__).'/gCurlRequest.class.php');
}
if (!class_exists('gCurlResponse',false)){
    require(dirname(__FILE__).'/gCurlResponse.class.php');
}
if (!class_exists('gURI',false)){
    require(dirname(__FILE__).'/gURI.class.php');
}
if (!class_exists('gCurlOptions',false)){
    require(dirname(__FILE__).'/gCurlOptions.class.php');
}
if (!interface_exists('gCurlHandlers',false)){
    require(dirname(__FILE__).'/gCurlHandlers.php');
}
if (!interface_exists('gksException',false)){
    require(dirname(__FILE__).'/gksException.class.php');
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
     * Counter of the requests
     *
     * @var int
     */
    public $request_counter;
    
    /**
     * Full URL requested
     *
     * @var string
     */
    protected $location_href= '';
    
    /**
     * Instance of the gCurlRequest object
     * see gCurlRequest.class.php
     *
     * @var gCurlRequest
     * 
     */
    public $Request;
    /**
     * Response object reference
     * see gCurlResponse.class.php
     *
     * @var gCurlResponse
     */
    public $Response;

    /**
     * @var gCurlOptions
     */
    public $options;

    /**
     * Flag that defines if cURL should automatically follow the "Location" header or not
     *
     * @var bool
     */
    public $followlocation=0;
    
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
     * The flag showin that the request is ready to be sent
     * Set by prepare() method after setting all headers
     *
     * @var bool
     */
    private $is_prepared = false;

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
     * HTTP/1.1 100 Continue
     * expect real headers further
     */
    const FLAG_CONTINUE=8;
    
    /**
     * POST request format, can be application/x-www-form-urlencoded or multipart/form-data
     */
    const 
        POST_URLENCODED =1,
        POST_MULTIPART = 2;
    
    /**
     * Constructor of the class
     *
     * @return void
     */
    function __construct($url,$method='GET'){
        if (!defined('CURLE_OK')){
            throw new gCurlException(10);
        }
        $this->URI = new gURI();
        //prepare the URL to browse to
        $this->URI->process($url);
        $this->location_href = $this->URI->full;

        $this->ch = curl_init();
        if (!$this->ch || gCurlException::catchError($this->ch)){
            throw new gCurlException(15);
        }

        //create request and response objects
        $this->Request= new gCurlRequest();
        $this->Request->setURI($this->URI);
        $this->Request->setRequestMethod($method);

        $this->Response = new gCurlResponse($this->ch);
        $this->Response->setURI($this->URI);
        
        $this->options = new gCurlOptions($this->ch);
        $this->options->setBasicParams();
        //set the response headers handler
        $this->options->setHeadersHandler(array($this->Response,'headersHandler'));
    }

    /**
     * signal a redirect URL
     *
     * @param string $new_uri
     */
    function redirect($new_uri,$method='GET'){
        $this->URI->parse_http_redirect($new_uri);
        $this->location_href = $this->URI->full;
        
        //create request and response objects
        $this->Request = new gCurlRequest();
        $this->Request->setURI($this->URI);
        $this->Request->setRequestMethod($method);
        $this->Response->cleanup();
        $this->Response->setURI($this->URI);
        $this->is_prepared = false;
    }

    /**
     * Define whether to return the transfer or not
     *
     * @param bool $value
     */
    function returnTransfer($value){
        if ($this->is_prepared){
            throw new gCurlException(25);
        }
        $this->return_transfer=(bool)$value;
    }

    /**
     * Assign request headers, request parameters and data for POST, set proxy and 
     * clear settings of a previous request
     */
    function prepare(){
        $this->options->requestInit($this->Request);
        $this->is_prepared = true;
    }
    /**
     * Run the CURL engine
     *
     * @return gCurlResponse
     */
    function exec(){
        if (!$this->is_prepared){
            $this->prepare();
        }
        //run the request
        ++$this->request_counter;
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
        $this->is_prepared = false;
        return $this->Response;
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
     * Pass the object implementing the handlers
     * 
     * @param gCurlHandlers $Handlers
     */
    function setHandlers(gCurlHandlers $Handlers){
        $Handlers->setGCurlReference($this);
        $this->Response->setHandlers($Handlers);
    }

    /**
     * Closing the curl handler is required for repetitive requests
     * to release memory used by cURL
     */
    function __destruct(){
        unset($this->Request,$this->Response,$this->URI);
        $this->disconnect();
    }
//end of the class
}



/**
 * Exceptions for gCurl
 *
 */
class gCurlException extends Exception implements gksException {

    static $curl_errno;

    static $curl_error;

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
        25=>'The request is "prepared", can not set new options',
        26=>'The request is not prepared, call the gCurl::prepare() to assign headers and data',
        50=>'Invalid request method',
        51=>'Invalid request parameters',
        51=>'Invalid POST format - the parameter should be a constant',
        60=>'Out of memory',
        70=>'Headers already sent to the user agent',
        80=>'CURL reported error',
        90=>'Invalid delay value',
        110=>'Non-HTTP response headers',
        115=>'Curl returned empty result after execution',
        120=>'Invalid host of the requested URI',
        125=>'Invalid URI',
        130=>'Redirects limit reached',
        
        300=>'cURL MULTI error',
        302=>'Error running cURL multi requests',
        303=>'No threads registered',
        310=>'The Thread is missing Request',
        320=>'Invalid thread ID',
        330=>'Socket select error',
        335=>'cURL Multi timeout',
        340=>'Handler did not provide a URL, remove thread',

        200=>'Interrupt connection from the handler',
        1000=>'Interrupt connection from the handler',
    );

    /**
     * Initialize the exception
     *
     * @param int $code
     * @param int $curl_errno
     * @param string $curl_error
     */
    function __construct($code, $curl_errno=0, $curl_error=''){
        //get the error description
        array_key_exists($code, $this->exception_codes) || $code=1;
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

    /**
     * Check for an error
     *
     * @param resource $ch
     * @return bool
     */
    static function catchError($ch=null){
        if ($ch === null){
            //check all

        }else{
            if (!is_resource($ch) || !($curl_errno=curl_errno($ch))){
                return false;
            }
            self::$curl_errno = $curl_errno;
            self::$curl_error = curl_error($ch);
            throw new gCurlException(80,$curl_errno,self::$curl_error);
            return true;
        }

    }

//class end
}
