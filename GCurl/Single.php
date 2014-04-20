<?php
/**
 * This file contains main GCurl class executing a single HTTP request.
 * It uses Request and Response classes.
 * 
 * @package GCurl
 * @author Grigori Kochanov http://www.grik.net/
 * @version 2.7
 */

namespace GCurl;

/**
 * A class to simplify complex tasks for performing and processing HTTP requests with CURL
 *
 * @package GCurl
 * @author Grigori Kochanov
 * @version 3
 */
class Single
{
    /**
     * Instance of the gCurlRequest object
     * see gCurlRequest.class.php
     *
     * @var Request
     * 
     */
	protected $Request;
    /**
     * Response object reference
     * see gCurlResponse.class.php
     *
     * @var Response
     */
	protected $Response;

    /**
     * @var Options
     */
	protected $Options;

	/**
	 * instance of the URI class
	 *
	 * @var gURI
	 */
	protected $URI;

	/**
	 * CURL resource handler
	 *
	 * @var resource
	 */
	protected $ch;

	/**
	 * Counter of the requests
	 *
	 * @var int
	 */
	protected $request_counter;

	/**
	 * Full URL requested
	 *
	 * @var string
	 */
	protected $location_href= '';

	/**
     * Flag that defines if cURL should return the body of the response
     *
     * @var bool
     */
    private $return_transfer=1;
    
    /**
     * The flag showin that the request is ready to be sent
     * Set by prepare() method after setting all headers
     *
     * @var bool
     */
    private $is_prepared = false;

	/**
	 * A shortcut to make a GET request
	 * Usage:
	 * $Response = \GCurl\Single::GET($url);
	 * echo $Response;
	 *
	 * @param $uri
	 * @param array $params
	 * @return \GCurl\Response
	 */
	public static function GET($uri,$params = [])
	{
		$GCurl = new Single($uri);
		if ($params) {
			foreach ($params as $k => $v) {
				$GCurl->Request->addGetVar($k, $v);
			}
		}
		$Response = $GCurl->exec();
		unset($GCurl);
		return $Response;
	}

	/**
	 * A shortcut to make a POST request
	 * Usage: $Response = \GCurl\Single::POST($url,['a'=>1,'b'=>2]);
	 * echo $Response;
	 *
	 * @param $uri
	 * @param array $params
	 * @return \GCurl\Response
	 */
	public static function POST($uri,$params)
	{
		$GCurl = new Single($uri);
		$GCurl->Request->s
		if ($params) {
			foreach ($params as $k => $v) {
				$GCurl->Request->addGetVar($k, $v);
			}
		}
		$Response = $GCurl->exec();
		unset($GCurl);
		return $Response;
	}

	/**
	 * Constructor of the class
	 *
	 * @param $url
	 * @param string $method
	 * @throws Exception
	 * @return \GKS\GCurl\Single
	 */
	public function __construct($uri = '')
    {
        if (!defined('CURLE_OK')) {
            throw new Exception(10);
        }

        $this->ch = curl_init();
        if (!$this->ch || Exception::catchError($this->ch)) {
            throw new Exception(15);
        }

	    $this->URI = new gURI();
	    //prepare the URL to browse to
	    $this->URI->process($uri);
	    $this->location_href = $this->URI->full;

	    //create request and response objects
	    $this->Request = new GetRequest();
	    $this->Request->setURI($this->URI);

        $this->Response = new Response($this->ch);
        $this->Response->setURI($this->URI);
        
        $this->Options = new Options($this->ch);
        $this->Options->setBasicParams();
        //set the response headers handler
        $this->Options->setHeadersHandler(array($this->Response,'headersHandler'));
    }

	/**
	 * signal a redirect URL
	 *
	 * @param string $new_uri
	 * @param string $method
	 */
	public function redirect($new_uri,$method='GET')
	{
        $this->URI->parse_http_redirect($new_uri);
        $this->location_href = $this->URI->full;
        
        //create request and response objects
        $this->Request = new Request();
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
	 * @throws Exception
	 */
	public function returnTransfer($value)
    {
        if ($this->is_prepared) {
            throw new Exception(25);
        }
        $this->return_transfer=(bool)$value;
    }

    /**
     * Assign request headers, request parameters and data for POST, set proxy and 
     * clear settings of a previous request
     */
	public function prepare()
    {
        $this->Options->requestInit($this->Request);
        $this->is_prepared = true;
    }

	/**
	 * Run the CURL engine
	 *
	 * @throws Exception
	 * @return Response
	 */
	public function exec()
    {
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
        if ($this->Response->Handlers){
            $this->Response->Handlers->cleanGCurlReference();
        }
        
        if ($this->return_transfer && !$result && !$this->Response->headers['len']){
            throw new Exception(115);
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
	public function disconnect()
    {
        if (is_resource($this->ch)){
            curl_close($this->ch);
        }
        $this->ch = NULL;
    }

	/**
	 * Pass the object implementing the handlers
	 *
	 * @param Handler $Handler
	 * @internal param Handler $Handler
	 */
	public function setHandlers(Handler $Handler)
    {
        $Handler->setGCurlReference($this);
        $this->Response->setHandlers($Handler);
    }

    /**
     * Closing the curl handler is required for repetitive requests
     * to release memory used by cURL
     */
	public function __destruct()
    {
        unset($this->Request,$this->Response,$this->URI);
        $this->disconnect();
    }

	/**
	 * Provides a read-only access
	 * @param $key
	 */
	public function __get($key)
	{
		$read_only_properties = ['Request','Response','Options','URI','ch','request_counter'];
		if (in_array($key,$read_only_properties)){
			return $this->$key;
		}
		$trace = debug_backtrace();
		trigger_error(
			'Undefined property via __get(): ' . $key .
			' in ' . $trace[0]['file'] .
			' on line ' . $trace[0]['line'],
			E_USER_NOTICE);
	}
}
