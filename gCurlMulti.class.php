<?php

/**
 * This file contains class gCurl, interface gCurlHandlers and exception class gCurlException.
 * It requires gCurlRequest and gCurlResponse classes as well
 * 
 * @package gCurl
 * @author Grigori Kochanov http://www.grik.net/
 * @version 2.6.1
 */

//Load package classes
if (!class_exists('gCurl',false)){
    require(__DIR__.'/gCurl.class.php');
}
if (!class_exists('overrideReflectionClass',false)){
    require(__DIR__.'/overrideReflectionClass.class.php');
}
if (!class_exists('gCurlThread',false)){
    require(__DIR__.'/gCurlThread.class.php');
}

/**
 * Main cURL Multi class
 * 
 * @package gCurl
 * @author Grigori Kochanov http://www.grik.net/
 * @version 1.2
 *
 */
class gCurlMulti{

/**
 * Flags for parameters
 *
 */
const 
URL = 1, 
HEADERS_HANDLER = 2,
BODY_HANDLER = 3,
RETURN_TRANSFER = 4,
NETWORK_INTERFACE = 5,
OPTIONS = 6;

/**
 * Timeout for activity in connections
 *
 */
public $TIMEOUT=30;

/**
 * cURL MULTI handler
 *
 * @var resource
 */
public $mh;

/**
 * Array of objects that handle Curl connections
 *
 * @var array
 */
public $threads = array();

function __construct(){
    $this->mh = curl_multi_init();
}

/**
 * Create a CURL EASY handler and request/response classes for it
 *
 */
function addThread(gCurlThread $Thread){
    if (null === $this->mh){
        $this->mh = curl_multi_init();
    }
    
    $Thread->gCurlMultiObject = $this;
    
    // Create a cURL EASY handler
    $Thread->ch = curl_init();
    
    if ($this->catchCurlError($Thread->ch) || !$Thread->ch){
        throw new gCurlException(15);
    }
    
    //create request and response objects
    $Thread->prepareRequest();
    if (!($Thread->Request instanceof gCurlRequest )){
        throw new gCurlException(310);
    }
    $Thread->Response = new gCurlResponse($Thread->ch,$Thread->Request->URI);
    

    //define basic parameters
    curl_setopt ($Thread->ch, CURLOPT_HEADER, 0);
    curl_setopt ($Thread->ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt ($Thread->ch, CURLOPT_ENCODING, '');
    curl_setopt ($Thread->ch, CURLOPT_RETURNTRANSFER, 1);
    if ($Thread->Request->URI->scheme == 'https://'){
        curl_setopt ($Thread->ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt ($Thread->ch, CURLOPT_SSL_VERIFYHOST, 2);
    }
    
    //set parameters for request
    $this->initThread($Thread->ch,$Thread->Request);
    
    //get redefined methods of the thread class and fetch the list of the user-defined handlers
    $Reflection = new overrideReflectionClass($Thread);
    $handlers = array_flip($Reflection->getOverrideMethods());
    unset($handlers['__construct'],$handlers['__destruct'],$handlers['eof']);
    $Thread->_handlers = $handlers;
    
    //set the handlers
    $Thread->Response->setHandlers($Thread);
    curl_setopt ($Thread->ch, CURLOPT_HEADERFUNCTION, array($Thread->Response,'headersHandler'));
    if (isset($handlers['bodyHandler'])){
        curl_setopt($Thread->ch,CURLOPT_WRITEFUNCTION,array($Thread,'bodyHandler'));
    }
    
    curl_multi_add_handle ($this->mh,$Thread->ch);
    $this->threads[] = $Thread;
}

/**
 * Unregister the thread before deleting it
 *
 * @param gCurlThread $Thread
 * @return bool
 */
function removeThread(gCurlThread $Thread){
    if (is_resource($Thread->ch)){
        @curl_multi_remove_handle($this->mh, $Thread->ch);
        curl_close($Thread->ch);
    }
    $key = array_search($Thread, $this->threads);
    if ($key === false){
        return false;
    }
    unset($this->threads[$key]);
    return true;
}

private function initThread($ch, gCurlRequest $Request){
    curl_setopt ($ch, CURLOPT_URL, (string)$Request->URI);

    //prepare the POST data
    if (strcasecmp($Request->method, 'POST')==0){
        curl_setopt ($ch, CURLOPT_POST, 1);
    }    
    if ($Request->post_data){
        curl_setopt ($ch,CURLOPT_POSTFIELDS, $Request->post_data);
    }

    //add cookies to headers
    if ($Request->cookie_string){
        $Request->registerCustomHeader('Cookie: '.$Request->cookie_string);
    }
    //process user-defined request headers
    if ($Request->custom_headers){
        curl_setopt ($ch, CURLOPT_HTTPHEADER, $Request->custom_headers);
    }
    //use proxy if defined
    if ($Request->proxy && $Request->proxy_port){
        curl_setopt ($ch, CURLOPT_PROXY, $Request->proxy);
        curl_setopt ($ch, CURLOPT_PROXYPORT, $Request->proxy_port);
        if ($Request->proxyuser){
            curl_setopt (
                $ch, 
                CURLOPT_PROXYUSERPWD, 
                $Request->proxyuser.':'.$Request->proxypwd
            );
        }
    }
    
}

/**
 * Spin the wheel
 *
 */
function exec(){
    if (!count($this->threads)){
        throw new gCurlException(303);
    }

    for ($i=0,$j=count($this->threads);$i<$j;++$i){
        $Thread = $this->threads[$i];
        if (isset($Thread->_handlers['onStart'])){
            $Thread->onStart();
        }
    }

    foreach ($this->threads as $Thread){
        if (isset($Thread->_handlers['onStart'])){
            $Thread->onStart();
        }
    }
    
    do{
        
        do {
            $mrc = curl_multi_exec($this->mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        
        //check for info from cURL
        while ($details = curl_multi_info_read($this->mh)){
            foreach ($this->threads as $Thread){
                if ($details['handle'] === $Thread->ch){
                    break;
                }
            }
            curl_multi_remove_handle($this->mh,$Thread->ch);
            if ($details['result'] == CURLE_OK){
                //response received successfully
                $Thread->Response->body = curl_multi_getcontent($Thread->ch);
                if (isset($Thread->_handlers['onSuccess'])){
                    $Thread->onSuccess();
                }
            }else{
                //connection error
                if (isset($Thread->_handlers['onFailure'])){
                    $Thread->onFailure();
                }
            }
            if (!$Thread->eof()){
                //if the thread should be reused - prepare the new request and reassign the handler
                $Thread->prepareRequest();
                $Thread->Response->cleanup();
                curl_multi_add_handle ($this->mh,$Thread->ch);
                $this->initThread($Thread->ch,$Thread->Request);
                if (isset($Thread->_handlers['onStart'])){
                    $Thread->onStart();
                }
                $thread_renew = true;
            } elseif ( is_resource($Thread->ch)){
                //thread finished, close resource
                curl_close($Thread->ch);
                $Thread->ch = null;
            }


            if (isset($Thread->_handlers['onComplete'])){
                $Thread->onComplete($details['result']);
            }

        }

        if ($active){
            // wait for network
            $socket_select = curl_multi_select($this->mh,$this->TIMEOUT);
            if ($socket_select == -1){
                throw new gCurlException(330);
            }
            //continue the operations
        }elseif (!$thread_renew){
            //no active threads left
            break;
        }
        if ( $mrc != CURLM_OK ){
            //some problem in cURL Multi
            break;
        }

    }while (true);

    if ($mrc != CURLM_OK){
        if ($mrc == CURLM_OUT_OF_MEMORY){
            $code = 60;
        }else{
            $code = 300;
        }
        throw new gCurlException($code);
    }

    curl_multi_close($this->mh);
}

/**
 * Return the thread ogbject by it's ID
 *
 * @param int $thread_id
 * @return gCurlThread
 */
function getThread($thread_id){
    if (!isset($this->threads[$thread_id])){
        throw new gCurlException(320);
    }
    return $this->threads[$thread_id];
}

/**
 * Check for an error
 *
 * @param resource $ch
 * @return bool
 */
function catchCurlError($ch=null){
    if ($ch === null){
        //check all
        
    }else{
        if (!is_resource($ch) || !($curl_errno=curl_errno($ch))){
            return false;
        }
        $this->curl_errno = $curl_errno;
        $this->curl_error = curl_error($ch);
        throw new gCurlException(80,$curl_errno,$this->curl_error);
        return true;
    }
    
}
//class end
}