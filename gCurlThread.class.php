<?php
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
class gCurlThread implements gCurlHandlers{

    /**
     * gCURL request object
     *
     * @var gCurlRequest
     */
    public $Request;

    /**
     * gCURL response object
     *
     * @var gCurlResponse
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
     * @var gCurlMulti
     */
    public $gCurlMultiObject;

    /**
     * @var gCurlOptions
     */
    public $options;

    /**
     * List of redefined handlers, do not modify this variable
     *
     * @var array
     */
    private $_handlers = array();

    /**
     * @param string $url
     */
    function __construct($url=null){
        $this->url = trim($url);

        // Create a cURL EASY handler
        $this->ch = curl_init();
        if (!$this->ch || gCurlException::catchError($this->ch)){
            throw new gCurlException(15);
        }
        //a class that will handle requests for setting curl options
        $this->options = new gCurlOptions($this->ch);
        //define basic parameters
        $this->options->setBasicParams();

        //get redefined methods of the thread class and fetch the list of the user-defined handlers
        $Reflection = new overrideReflectionClass($this);
        $handlers = array_flip($Reflection->getOverrideMethods());
        unset($handlers['__construct'],$handlers['__destruct'],$handlers['eof']);
        $this->_handlers = $handlers;

        //set handlers
        $this->Response = new gCurlResponse($this->ch);
        $this->Response->setHandlers($this);
        $this->options->setHeadersHandler(array($this->Response,'headersHandler'));
    }

    /**
     * @return gURI
     */
    public function getUri(){
        return new gURI($this->url);
    }

    /**
     * This method should initialize $this->Request as gCurlRequest instance
     * and $this->Response as gCurlResponse.
     * It can be redefined to provide additional parameters such as POST data
     * $this->Request->URI should contain URI (string or object castable to string)
     *
     */
    final public function init(){
        $this->Request = new gCurlRequest();
        $this->Request->setURI($this->getUri());
        $this->Response->setURI($this->getUri());
        $this->customizeRequest($this->Request);
        $this->options->requestInit($this->Request);
    }

    /**
     * Redefine this function to customize the request
     * @param gCurlRequest $Request
     */
    function customizeRequest(gCurlRequest $Request){}

    /**
     * If the child class has the bodyHandler() method redefined
     * it will be used as the handler for the response body
     *
     * @return bool
     */
    public function getUseBodyHandler(){
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
    function eof(){
        return true;
    }

    /**
     *
     */
    function close(){
        $this->gCurlMultiObject->removeThread($this);
        unset($this->gCurlMultiObject);
    }

    /**
     * This callback is called after the thread is registered in CurlMulti engine
     * when the curl handler resource is available.
     * Can be overloaded to define custom Curl options.
     */
    function onAdd(){}

    /**
     * Called before removing the thread from the multi-handler and closing curl handler resource
     */
    function onRemove(){}

    /**
     * Called when the request was successful and a response is being received
     * regardless of the HTTP response code
     */
    function onSuccess(){}

    /**
     * Called if there is a connection error
     */
    function onFailure(){}

    /**
     * Called after onSuccess()/onFailure()
     * @param $status - value of curl_multi_info_read()['result']
     */
    function onComplete($status){}

    function headersHandler(array $headers){}

    function cookiesHandler(array $cookies){}

    function bodyHandler($chunk){}

    /**
     * This method allows to check if a given handler is defined in the inherited class
     * @param $handler_name
     * @return bool
     */
    final public function handlerDefined($handler_name){
        return isset($this->_handlers[$handler_name]);
    }

//class end
}
