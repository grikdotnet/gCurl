<?php
/**
 * This class encapsulates actions on setting options for cURL
 * User: gri
 * Date: 09.03.12
 */
class gCurlOptions{
    private $ch;

    /**
     * Path of the cookie jar file assigned to CURL
     * @var string
     */
    private $cookie_jar_file;

    function __construct($ch){
        $this->ch = $ch;
    }

    function __destruct(){
        if ($this->cookie_jar_file){
            @unlink($this->cookie_jar_file);
        }
    }

    function setFollowLocation($value){
        curl_setopt ($this->ch, CURLOPT_FOLLOWLOCATION, $value);
    }

    function setHeadersHandler($callback){
        curl_setopt ($this->ch, CURLOPT_HEADERFUNCTION, $callback);
    }

    /**
     * Assign the callback for the curl
     * @param $callback callable
     */
    function setBodyHandler($callback){
        curl_setopt($this->ch,CURLOPT_WRITEFUNCTION,$callback);
    }

    function setBasicParams(){
        curl_setopt ($this->ch, CURLOPT_HEADER, 0);
        curl_setopt ($this->ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt ($this->ch, CURLOPT_ENCODING, '');
        curl_setopt ($this->ch, CURLOPT_RETURNTRANSFER, 1);
    }

    function requestInit(gCurlRequest $Request){

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
            curl_setopt ($this->ch,CURLOPT_CUSTOMREQUEST,$this->Request->method);
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
     * Sets the parameters used to
     * @param $file
     */
    function setCookieJar($file){
        curl_setopt($this->ch,CURLOPT_COOKIEFILE,$file);
        curl_setopt($this->ch,CURLOPT_COOKIEJAR,$file);

    }
}
