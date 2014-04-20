<?php

namespace GKS\GCurl;

/**
 * cURL Thread is a class used to set hooks and emulate the multi-thread execution
 * when using the gCurlMulti class
 * 
 * @package gCurl
 * @author Grigori Kochanov http://www.grik.net/
 * @version 1.1
 *
 * in 1.1: added onAdd and onRemove handlers
 */
class Thread implements Handlers
{

    /**
     * gCURL request object
     *
     * @var Request
     */
    public $Request;

    /**
     * gCURL response object
     *
     * @var Response
     */
    public $Response;

    /**
     * Curl easy handler
     *
     * @var resource
     */
    public $ch;

    /**
     * Main gCurlMulti object running the thread
     *
     * @var Multi
     */
    public $gCurlMultiObject;

    /**
     * @var Options
     */
    public $options;

    /**
     * The URL of the redirect
     * @var string
     */
    protected $redirect;

    /**
     * List of redefined handlers, do not modify this variable
     *
     * @var array
     */
    private $_handlers = array();

    /**
     * @param string $url
     */
    public function __construct($url=null)
    {
        $this->url = trim($url);

        // Create a cURL EASY handler
        $this->ch = curl_init();
        if (!$this->ch || Exception::catchError($this->ch)){
            throw new Exception(15);
        }
        //a class that will handle requests for setting curl options
        $this->options = new Options($this->ch);
        //define basic parameters
        $this->options->setBasicParams();

        //get redefined methods of the thread class and fetch the list of the user-defined handlers
        $Reflection = new overrideReflectionClass($this);
        $handlers = array_flip($Reflection->getOverrideMethods());
        unset($handlers['__construct'],$handlers['__destruct'],$handlers['eof']);
        $this->_handlers = $handlers;

        //set handlers
        $this->Response = new Response($this->ch);
        $this->Response->setHandlers($this);
        $this->options->setHeadersHandler(array($this->Response,'headersHandler'));
    }

    /**
     * This method returns the URI for the request.
     * It should be redefined to provide URLs from the custom sources
     *
     * @return gURI
     */
    public function getUri()
    {
        if ($this->redirect){
            $this->redirect = null;
            if (!$this->Request->getURI()){
                return new gURI($this->redirect);
            }
            if (!$this->Request->getURI()->parse_http_redirect($this->redirect)){
                return false;
            }
            return $this->Request->getURI();
        }
        return new gURI($this->url);
    }

    /**
     * This method initializes $this->Request as gCurlRequest instance
     * and $this->Response as gCurlResponse.
     * $this->Request->URI should contain URI (string or object castable to string)
     *
     * @return bool
     */
	public final function init()
	{
        $this->Request = new Request();
        $uri = $this->getUri();
        if (!($uri instanceof gURI)){
            return false;
        }
        $this->Request->setURI($uri);
        $this->Response->setURI($uri);
        $this->customizeRequest($this->Request);
        $this->options->requestInit($this->Request);
        return true;
    }

    /**
     * Redefine this function to customize the request
     * Additional parameters such as POST data should be set here
     * @param Request $Request
     */
	public function customizeRequest(Request $Request){}

    /**
     * If the child class has the bodyHandler() method redefined
     * it will be used as the handler for the response body
     *
     * @return bool
     */
    public function getUseBodyHandler()
    {
        return isset($this->_handlers['bodyHandler']);
    }

    /**
     * This callback is called after processing the response to ask
     * if the thread wants to proceed with another request.
     * It it returns false the prepareRequest() and init() methods will be called
     * and the
     *
     * @return bool
     */
	public function eof()
    {
        return !$this->redirect;
    }

    /**
     *
     */
	public function close()
    {
        $this->gCurlMultiObject->removeThread($this);
        unset($this->gCurlMultiObject);
    }

    /**
     * This callback is called after the thread is registered in CurlMulti engine
     * when the curl handler resource is available.
     * Can be overloaded to define custom Curl options.
     */
	public function onAdd(){}

    /**
     * Called before removing the thread from the multi-handler and closing curl handler resource
     */
	public function onRemove(){}

    /**
     * Called when the request was successful and a response is being received
     * regardless of the HTTP response code
     */
	public function onSuccess(){}

    /**
     * Called when the redirect is met
     */
	public function onRedirect(){}

    /**
     * Called if there is a connection error
     */
	public function onFailure(){}

    /**
     * Called after onSuccess()/onFailure()
     * @param $status - value of curl_multi_info_read()['result']
     */
	public function onComplete($status){}

	public function headersHandler(array $headers){}

	public function cookiesHandler(array $cookies){}

	public function bodyHandler($chunk){}

    /**
     * This method allows to check if a given handler is defined in the inherited class
     * @param $handler_name
     * @return bool
     */
	public final function handlerDefined($handler_name){
        return isset($this->_handlers[$handler_name]);
    }
}
