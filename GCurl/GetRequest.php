<?php

namespace GCurl;

/**
 * Request-related data and methods to prepare the request
 * 
 * @package GCurl
 * @author Grigori Kochanov
 * @version 3
 */
class GetRequest
{
    /**
     * address of the proxy to use or NULL to use a direct connection
     *
     * @var string
     */
    public $proxy;
    /**
     * port of the proxy
     *
     * @var int
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
     * a request method (GET, POST, HEAD, OPTIONS)
     *
     * @var string
     */
    /**
     * gURI object with parsed URL
     *
     * @var URI
     */
    private $URI;

    const METHOD = 'GET';

    /**
     * @param URI $URI
     */
    public function __construct($uri){
        if (!($uri instanceof URI)) {
            $uri = new URI($uri);
        }
        $this->URI = $uri;
    }

    /**
     * Add a variable to send in a query
     *
     * @param string $var
     * @param string $var_value
     * @throws Exception
     */
    public function addGetVar($var, $var_value)
    {
        //check parameters
        if (!$var || !is_string($var) || !is_scalar($var_value)){
            throw new Exception(51);
        }
        $query=$this->URI->query;
        $query.=($query?'&':'?').urlencode($var).'='.urlencode($var_value);

        $this->URI->query = $query;
    }

    /**
     * Add a name/value pair to the request coookie
     *
     * @param string $name
     * @param string $value
     * @throws Exception
     */
    public function addCookieVar($name,$value)
    {
        if (!$name || !is_string($name) || !is_scalar($value)) {
            throw new Exception(51);
        }
        if ($this->cookie_string) {
            $this->cookie_string.=';';
        }
        $this->cookie_string.=urlencode($name).'='.urlencode($value);
    }

    /**
     * Add a custom request header
     * if the value argument is provided, the header is considered a header name
     *
     * @param string $header
     * @param string $value optional
     */
    public function registerCustomHeader($header,$value=NULL)
    {
        $this->custom_headers[] = $value? ($header .': '.$value) : $header ;
    }
    
    /**
     * Add a bunch of custom request headers
     *
     * @param array $headers
     */
    public function registerCustomHeadersArray(array $headers)
    {
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
    public function useProxy($proxy,$port,$user='',$password='')
    {
        $this->proxy = $proxy;
        $this->proxy_port = $port;
        $this->proxyuser = $user;
        $this->proxypwd = $password;
    }

    function getURI()
    {
        return $this->URI;
    }
}
