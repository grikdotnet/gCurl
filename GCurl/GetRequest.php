<?php

namespace GCurl;

/**
 * Request-related data and methods to prepare the request
 * 
 * @package GCurl
 * @author Grigori Kochanov
 * @version 3
 */
class GetRequest implements IRequest
{
    /**
     * History of requests and data sent
     *
     * @var array
     */
    public $history = array(
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
    private $custom_headers = [];

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

    /**
     * Cookies added via addCookie() method
     *
     * @var array
     */
    private $cookies = [];

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
     * Initialize curl
     * @param Options $Options
     */
    public function prepare(Options $Options)
    {
        $Options->commonRequestInit($this);
        $Options->initGetRequest($this);
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
    public function addCookie($name,$value)
    {
        if (!$name || !is_string($name) || !is_scalar($value)) {
            throw new Exception(51);
        }
        $this->cookies[] = [$name,$value];
    }

    /**
     * Get cookies in a format to send in request
     *
     * @return string
     */
    public function getCookieString()
    {
        $cookie_string = '';
        foreach ($this->cookies as $c) {
            $cookie_string.=urlencode($c[0]).'='.urlencode($c[1]);
        }
        if ($cookie_string !== '') {
            $cookie_string.=';';
        }
        return $cookie_string;
    }

    /**
     * Add a custom request header
     * if the value argument is provided, the header is considered a header name
     *
     * @param string $header
     * @param string $value optional
     */
    public function addHeader($header,$value='')
    {
        $this->custom_headers[] = $value? ($header .': '.$value) : $header ;
    }

    /**
     * Returns request headers to register via curl_setopt()
     *
     * @return array
     */
    public function getHeaders()
    {
        if ($this->cookies){
            return array_merge($this->custom_headers,['Cookie: '.$this->getCookieString()]);
        }
        return $this->custom_headers;
    }

    /**
     * @return URI
     */
    function getURI()
    {
        return $this->URI;
    }

    function onRequestEnd()
    {
    }
}
