<?php
/**
 * cURL Thread is a class used to set hooks and emulate the multi-thread execution
 * when using the gCurlMulti class
 * 
 * @package gCurl
 * @author Grigori Kochanov http://www.grik.net/
 * @version 1
 *
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
     * List of redefined handlers, do not modify this variable
     *
     * @var array
     */
    public $_handlers = array();

    /**
     * Just a constructor
     *
     */
    function __construct($url){
        $this->url = trim($url);
    }

    /**
     * This method should initialize $this->Request as gCurlRequest instance
     * $this->Request->URI should contain URI (string or object castable to string)
     *
     */
    function prepareRequest(){
        $this->Request = new gCurlRequest();
        $this->Request->URI = new gURI($this->url);
    }

    function close(){
        $this->gCurlMultiObject->removeThread($this);
        unset($this->gCurlMultiObject);
    }
    /**
     * Handler called just before start, you can redefine any cURL parameters here
     *
     */
    function onStart(){}
    
    function headersHandler(array $headers){}
    
    function cookiesHandler(array $cookies){}
    
    function bodyHandler($chunk){}
    
    function onSuccess(){}
    
    function onFailure(){}
    
    function onComplete($status){}
    
    function eof(){return true;}
    
//class end
}
