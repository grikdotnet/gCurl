<?php

interface gCurlHandlers{
    /**
     * The handler method triggered after the response headers are received and processed
     * but before receiving the body
     * 
     * @param array $headers
     */
    function headersHandler(array $headers);
    
    /**
     * The method is triggered after the response headers are received,
     * it receives an array of cookies set by the server as parameter
     *
     * @param array $cookies
     */
    function cookiesHandler(array $cookies);
    
    /**
     * Default body handler
     *
     * @param string $chunk
     */
    function bodyHandler($chunk);

    /**
     * Called to check if the body handler should be set
     * @return bool
     */
    function getUseBodyHandler();
}

/**
 * The class extending this interface will contain methods that will be used as handlers
 * for processing HTTP response
 * 
 * @package gCurl
 * @author Grigori Kochanov
 * @version 2.1
 * @abstract 
 */
abstract class gCurlHandler implements gCurlHandlers{
    /**
     * Instance of the gCurl class utilizing this handler
     *
     * @var gCurl
     */
    protected $gCurl;
        
    /**
     * The handler method triggered after the response headers are received and processed
     * but before receiving the body
     * 
     * @param array $headers
     */
    function headersHandler(array $headers){}

    /**
     * The method is triggered after the response headers are received,
     * it receives an array of cookies set by the server as parameter
     *
     * @param array $cookies
     */
    function cookiesHandler(array $cookies){}

    /**
     * Don't use a body handler by default
     * @return bool
     */
    function getUseBodyHandler(){
        return false;
    }
    
    /**
     * Default body handler
     *
     * @param string $chunk
     */
    function bodyHandler($chunk){}
        
    /**
     * Set the reference to the gCurl object that uses this class methods as handlers
     *
     * @param gCurl $gCurl
     */
    final function setGCurlReference(gCurl $gCurl){
        $this->gCurl = $gCurl;
    }
    
    /**
     * Destructor - to avoid a circular reference
     *
     */
    final function cleanGCurlReference(){
        $this->gCurl = null;
    }
}
