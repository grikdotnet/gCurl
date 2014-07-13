<?php

namespace grikdotnet\curl;

/**
 * This class contains curl_setopt() calls and connection parameters
 * User: gri
 * @package GCurl
 */
class Options
{
    /**
     * address of the proxy to use or NULL to use a direct connection
     *
     * @var string
     */
    protected $proxy_host;
    /**
     * port of the proxy
     *
     * @var int
     */
    protected $proxy_port = 3128;
    /**
     * login for the proxy authorisation
     *
     * @var string
     */
    protected $proxyuser = '';
    /**
     * password for the proxy authorisation
     *
     * @var string
     */
    protected $proxypwd = '';
    /**
     * Constants - flags
     */
    const
        HTTP_BODY = 1,
        HTTP_HEADERS = 2,
        HTTP_FULL = 3;
    /**
     * sets the status of the data to show the end
     */
    const FLAG_EOF = 1;
    /**
     * the HTTP response is received
     */
    const FLAG_HTTP_OK = 2;
    /**
     * headers are received and processed
     */
    const FLAG_HEADERS_RECEIVED = 4;
    /**
     * HTTP/1.1 100 Continue
     * expect real headers further
     */
    const FLAG_CONTINUE = 8;

    /**
     * POST request format, can be application/x-www-form-urlencoded or multipart/form-data
     */
    const
        POST_URLENCODED = 1,
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
    private $interface = null;

    /**
     * Flag that defines if cURL should return the body of the response
     *
     * @var bool
     */
    private $return_transfer = 1;

    public function __construct($ch)
    {
        $this->ch = $ch;
    }

    public function __destruct()
    {
        if ($this->cookie_jar_file && is_file($this->cookie_jar_file)){
            unlink($this->cookie_jar_file);
        }
    }

    public function setFollowLocation($value)
    {
        curl_setopt ($this->ch, CURLOPT_FOLLOWLOCATION, $value);
    }

    /**
     * Define whether to return the transfer or not
     *
     * @param bool $value
     * @return bool
     * @throws Exception
     */
    public function returnTransfer($value = null)
    {
        if ($value === NULL){
            return $this->return_transfer;
        }
        $this->return_transfer = (bool)$value;
    }

    /**
     * @param callable $callback
     */
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
     * Common initialization for each request
     *
     * @param IRequest $Request
     */
    public function commonRequestInit(IRequest $Request)
    {
        curl_setopt ($this->ch, CURLOPT_URL, (string)$Request->getURI());

        if ($Request->getURI()->scheme === 'https://') {
            curl_setopt ($this->ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt ($this->ch, CURLOPT_SSL_VERIFYHOST, 2);
        }

        //cleanup after previous request
        curl_setopt ($this->ch, CURLOPT_HTTPHEADER, array());

        //process user-defined request headers
        if ($Request->getHeaders()) {
            curl_setopt ($this->ch, CURLOPT_HTTPHEADER, $Request->getHeaders());
        }

        //use proxy if defined
        if ($this->proxy_host && $this->proxy_port) {
            curl_setopt ($this->ch, CURLOPT_PROXY, $this->proxy_host);
            curl_setopt ($this->ch, CURLOPT_PROXYPORT, $this->proxy_port);
            if ($this->proxyuser) {
                curl_setopt (
                    $this->ch,
                    CURLOPT_PROXYUSERPWD,
                    $this->proxyuser.':'.$this->proxypwd
                );
            }
        } else {
            curl_setopt ($this->ch, CURLOPT_PROXY, '');
            curl_setopt ($this->ch, CURLOPT_PROXYPORT, 0);
        }
    }

    /**
     * @param GetRequest $Request
     */
    public function initGetRequest(GetRequest $Request)
    {
        curl_setopt ($this->ch,CURLOPT_HTTPGET,true);
    }

    /**
     * @param PostUrlencodedRequest $Request
     */
    public function initPostRequest(PostUrlencodedRequest $Request)
    {
        curl_setopt ($this->ch, CURLOPT_POST, true);
        if ($Request->post_data) {
            curl_setopt ($this->ch,CURLOPT_POSTFIELDS, $Request->post_data);
        }
    }

    /**
     * @param PutFileRequest $Request
     */
    public function initPutFileRequest(PutFileRequest $Request)
    {
        curl_setopt ($this->ch,CURLOPT_PUT,true);
        curl_setopt ($this->ch,CURLOPT_INFILE,$Request->getFileHandler());
        curl_setopt ($this->ch,CURLOPT_INFILESIZE,$Request->getFileSize());
    }

    /**
     * @param PutStringRequest $Request
     */
    public function initPutStringRequest(PutStringRequest $Request)
    {
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($this->ch, CURLOPT_POSTFIELDS,$Request->getData());
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
