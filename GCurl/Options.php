<?php

namespace GCurl;

/**
 * This class encapsulates actions on setting options for cURL
 * User: gri
 * @package GCurl
 */
class Options
{
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
	 * Memory for the temporary file to use before creating a temporary file on a disk
	 * @var int
	 */
	public $put_max_tmp_memory = 4194304;

	/**
	 * Curl handler
	 * @var resource
	 */
	private $ch;

	/**
     * Path of the cookie jar file assigned to CURL
     * @var string
     */
    private $cookie_jar_file;

	/**
	 * System network interface (IP)
	 *
	 * @var string
	 */
	private $interface=null;

	public function __construct($ch)
    {
        $this->ch = $ch;
    }

	public function __destruct()
	{
        if ($this->cookie_jar_file){
            @unlink($this->cookie_jar_file);
        }
    }

	public function setFollowLocation($value)
	{
        curl_setopt ($this->ch, CURLOPT_FOLLOWLOCATION, $value);
    }

	public function setHeadersHandler(callable $callback)
	{
        curl_setopt ($this->ch, CURLOPT_HEADERFUNCTION, $callback);
    }

    /**
     * Assign the callback for the curl
     * @param $callback callable
     */
	public function setBodyHandler($callback)
    {
        curl_setopt($this->ch,CURLOPT_WRITEFUNCTION,$callback);
    }

	/**
	 *
	 */
	public function setBasicParams()
	{
        curl_setopt ($this->ch, CURLOPT_HEADER, 0);
        curl_setopt ($this->ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt ($this->ch, CURLOPT_ENCODING, '');
        curl_setopt ($this->ch, CURLOPT_RETURNTRANSFER, 1);
    }

	/**
	 * @param Request $Request
	 */
	public function requestInit(Request $Request)
	{

        curl_setopt ($this->ch, CURLOPT_URL, (string)$Request->getURI());

        if ($Request->getURI()->scheme == 'https://'){
            curl_setopt ($this->ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt ($this->ch, CURLOPT_SSL_VERIFYHOST, 2);
        }

        //cleanup after the previous request
        curl_setopt ($this->ch,CURLOPT_HTTPGET,1);
        curl_setopt ($this->ch, CURLOPT_HTTPHEADER, array());

        //prepare the POST data
        if (strcasecmp($Request->method, 'POST')==0){
            curl_setopt ($this->ch, CURLOPT_POST, 1);
            if ($Request->post_data){
                curl_setopt ($this->ch,CURLOPT_POSTFIELDS, $Request->post_data);
            }
        }elseif ($Request->method !== 'GET'){
            curl_setopt ($this->ch,CURLOPT_CUSTOMREQUEST,$Request->method);
        }

        //add cookies to headers
        if ($Request->cookie_string){
            $Request->registerCustomHeader('Cookie: '.$Request->cookie_string);
        }
        //process user-defined request headers
        if ($Request->custom_headers){
            curl_setopt ($this->ch, CURLOPT_HTTPHEADER, $Request->custom_headers);
        }
        //use proxy if defined
        if ($Request->proxy && $Request->proxy_port){
            curl_setopt ($this->ch, CURLOPT_PROXY, $Request->proxy);
            curl_setopt ($this->ch, CURLOPT_PROXYPORT, $Request->proxy_port);
            if ($Request->proxyuser){
                curl_setopt (
                    $this->ch,
                    CURLOPT_PROXYUSERPWD,
                    $Request->proxyuser.':'.$Request->proxypwd
                );
            }
        }
    }

    /**
     * Sets the name of a file used to store cookies
     * @param $file
     */
	public function setCookieJar($file)
	{
        curl_setopt($this->ch,CURLOPT_COOKIEFILE,$file);
        curl_setopt($this->ch,CURLOPT_COOKIEJAR,$file);

    }

    /**
     * Sets the time limit for the connection.
     * It makes sense to set a small value before making a request as a connection timeout,
     * and increase the value after the response started to be received
     * @param $seconds
     * @throws Exception
     */
	public function setConnectionTimeLimit($seconds)
	{
        curl_setopt($this->ch,CURLOPT_TIMEOUT,$seconds);
        if (Exception::catchError($this->ch)){
            throw new Exception(22);
        }
    }

    /**
     * Set the network interface for the outgoing connection.
     * The list of available interfaces can be found with a system commands
     * "ifconfig" in Unix or "ipconfig" in Windows
     *
     * @param string $interface
     */
	public function setNetworkInterface($interface)
	{
        $this->interface = $interface;
        curl_setopt($this->ch,CURLOPT_INTERFACE,$this->interface);
    }

    /**
     * Use a private key for SSL connection
     *
     * @param string $key_path - filename
     * @param string $password
     */
	public function setPrivateKey($key_path,$password = '')
	{
        curl_setopt($this->ch,CURLOPT_SSLKEY,$key_path);
        if ($password !==''){
            curl_setopt($this->ch,CURLOPT_SSLKEYPASSWD,$password);
        }
    }

    /**
     * Use an SSL certificate key for an SSL connection with the key authentication
     *
     * @param string $crt_path - filename
     * @param string $password
     */
	public function setCertificate($crt_path,$password='')
	{
        curl_setopt($this->ch,CURLOPT_SSLCERT,$crt_path);
        if ($password !==''){
            curl_setopt($this->ch,CURLOPT_SSLCERTPASSWD,$password);
        }
    }

	/**
	 * Use a file on a disk content for the PUT data
	 * @param $filename string
	 */
	public function setPutFile($filename)
	{
		$fh = fopen($filename,'r');
		if (!$fh){
			throw new Exception(401);
		}
		curl_setopt($this->ch, CURLOPT_PUT, 1);
		curl_setopt($this->ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($this->ch, CURLOPT_INFILE, $fh);
		curl_setopt($this->ch, CURLOPT_INFILESIZE, filesize($filename));
	}

	/**
	 *
	 */
	public function setPutContent($content)
	{
		$fp = fopen('php://temp/maxmemory:', 'w');

	}


}
