<?php
/**
 * Request-related data and methods to prepare the request
 * 
 * @package gCurl
 * @author Grigori Kochanov
 * @version 2
 */
class gCurlRequest{
    /**
     * gURI object with parsed URL
     *
     * @var gURI
     */
    public $URI;
    /**
     * request type (GET, POST, HEAD, OPTIONS)
     *
     * @var string
     */
    public $method = 'GET';
    /**
     * address of the proxy to use or NULL to use a direct connection
     *
     * @var string
     */
    public $proxy;
    /**
     * port of the proxy
     *
     * @var numeric
     */
    public $proxy_port = 3128;
    /**
     * login for the proxy authorisation
     *
     * @var string
     */
    public $proxyuser = '';
    /**
     * password for the proxy authorisation
     *
     * @var string
     */
    public $proxypwd = '';
    /**
     * cookies joined and ready to be sent
     *
     * @var string
     */
    public $cookie_string = '';
    
    /**
     * History of requests and data sent
     *
     * @var array
     */
    public $history = array(
                    'requests_count'=>0,
                    'request_headers'=>array(),
                    'sent_data'=>''
                );
    /**
     *  2-D array of custom headers provided by the user
     *  array('Referrer: http://example.com/','User-Agent: Mozilla');
     *  Note: it is NOT a key-value pair list
     *
     * @var array
     */
    public $custom_headers = array();

    /**
     * data to send with POST request
     *
     * @var array
     */
    public $post_data  = array();

    /**
     * flag to define the format of the POST data
     *
     * @var int
     */
    public $post_format = gCurl::POST_URLENCODED;

    /**
     * Assign the data prepared for the POST request
     *
     * @param string $data
     */
    function setRawPostData($data){
        $this->post_data = $data;
        $this->post_format = gCurl::POST_MULTIPART;
    }

    /**
     * Add a variable to send in a query
     *
     * @param string $var
     * @param string $var_value
     */
    function addGetVar($var, $var_value){
        //check parameters
        if (!$var || !is_string($var) || !is_scalar($var_value)){
            throw new gCurlException(51);
        }
        $query=$this->URI->query;
        $query.=($query?'&':'?').urlencode($var).'='.urlencode($var_value);

        $this->URI->query = $query;
    }
    
    /**
     * Add a variable to the  POST request
     *
     * @param string $var
     * @param string $var_value
     */
    function addPostVar($var, $var_value = ''){
        if (!$var || !is_string($var) || !is_scalar($var_value)){
            throw new gCurlException(51);
        }
        $this->post_data[$var] = $var_value;
    }
    
    /**
     * Define the format of the POST request
     * gCurl::POST_MULTIPART or gCurl::POST_URLENCODED
     *
     * @param const $format
     */
    function setPostFormat($format){
        if ($format !== gCurl::POST_MULTIPART  && $format != gCurl::POST_URLENCODED ){
            throw new gCurlException(52);
        }
        $this->post_format = $format;
    }
    /**
     * Prepare the data for the POST request according to the format
     *
     * @return mixed
     */
    function getPostFields(){
        if ($this->post_format == gCurl::POST_MULTIPART ){
            return $this->post_data;
        }
        //POST_URLENCODED
        if (!$this->post_data){
            return '';
        }
        $data = '';
        foreach ($this->post_data as $var=>$var_value){
            $data .= rawurlencode($var). '=' .rawurlencode($var_value).'&';
        }
        return substr($data,0,-1);
    }
    
    /**
     * Add a name/value pair to the request coookie
     *
     * @param string $name
     * @param string $value
     */
    function addCookieVar($name,$value){
        if (!$name || !is_string($name) || !is_scalar($value)){
            throw new gCurlException(51);
        }
        if ($this->cookie_string){
            $this->cookie_string.=';';
        }
        $this->cookie_string.=urlencode($name).'='.urlencode($value);
    }
    
    /**
     * Define the request method
     *
     * @param string $method
     */
    function setRequestMethod($method){
        $method = strtoupper($method);
        if (!in_array($method,array('GET','POST','HEAD','OPTIONS'))){
            throw new gCurlException(50);
        }
        $this->method=$method;
    }
    
    /**
     * Add a custom request header
     * if the value argument is provided, the header is considered a header name
     *
     * @param string $header
     * @param string $value optional
     */
    function registerCustomHeader($header,$value=NULL){
        $this->custom_headers[] = $value? ($header .': '.$value) : $header ;
    }
    
    /**
     * Add a bunch of custom request headers
     *
     * @param array $headers
     */
    function registerCustomHeadersArray(array $headers){
        for ($i=0,$len=sizeof($headers);$i<$len;++$i){
            $this->custom_headers[] = $headers[$i];
        }
    }
        
    /**
     * Set parameters to use proxy
     *
     * @param string $proxy IP address
     * @param string $port
     * @param string $user
     * @param string $password
     */
    function useProxy($proxy,$port,$user='',$password=''){
        $this->proxy = $proxy;
        $this->proxy_port = $port;
        $this->proxyuser = $user;
        $this->proxypwd = $password;
    }

//end of the class
}

