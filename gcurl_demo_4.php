<?php
/**
 * CURL Handlers: Swiss-knife of CURL-based crawlers
 * You can define the handler function for the response body after processing response headers.
 * For example, the handler can be set according to the value of the Content-type header.
 */
//Include library
require('gcurl.class.php');


/**
 * Define a handler class
 *
 */
class Handlers implements gCurlHandlers {
    public $charset;
    /**
     * Process headers (check content type) before assigning the body handler
     *
     * @param array $headers
     */
    function headersHandler(array $headers){
        //use 'ctype' as the content type value
        // because $headers['content-type'] usually contains charset as well
        if (stripos($headers['ctype'],'text') === false){
            //it's not a text, don't process it
            $this->bodyHandlerName='';
        }
        $this->charset = $headers['charset'];
    }
    
    /**
     * The cookies handler method is called after headers handler and before assigning the response body handler.
     * 
     * The method is called only if the server response contains Set-cookie header(s).
     * It  receives an array of the parsed cookies.
     *
     * @param array $cookies
     */
    function cookiesHandler(array $cookies){
        var_export($cookies);
    }
    
    /**
     * The function MUST return the exact number of bytes it received in the $data_chunk parameter.
     * The chunks have unpredictable length between 1 and 8192 bytes (inclusive),
     * as received from the remote server.
     * The handler is called sequentially for each chunk of a response body.
     *
     * @param resource $ch
     * @param string $data_chunk
     */
    function bodyHandler($data_chunk){
        $length = strlen($data_chunk);
        
        echo strtoupper( $data_chunk);
        
        //The body handler should return the nuber of bytes it received
        return $length;
    }
}

//init variables
$url = 'google.com.ua';

try{
    
    //initialize the class
    $curl = new gCurl($url);
    //initialize the handlers
    $handlers = new Handlers();
    //assign handlers
    $curl->setHandlers($handlers);
    
    // Response object returned does not have body data if a body handler is defined
    $response = $curl->exec();
    //$response->body == '';
    
}catch (gksException $E){
    echo $E->getHtmlMessage();
    exit;
}

