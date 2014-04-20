<?php

namespace GCurl;

interface Handlers
{
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
