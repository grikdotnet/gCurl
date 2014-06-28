<pre>
<?php
/**
 * Basic browser emulation: sending custom headers, processing cookies and redirects, sending referrer
 */

//set local timezone
date_default_timezone_set('UTC');

//Include library
require('gcurl.class.php');

//init variables
$url = 'google.com';
$max_redirects = 20;
$i=0;
//some request headers
$request_headers = array(
    'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.11) Gecko/20071127 Firefox/2.0.0.11',
    'Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
    'Accept-Language: en-us,en;q=0.5',
    'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
);
$cookies = array();

try{
    //initialize the class
    $curl = new gCurl($url);
    
    //emulate Firefox
    $curl->Request->registerCustomHeadersArray($request_headers);
    
    do {
        $response = $curl->exec();
        //check for the redirect
        if ($new_url = $response->getHeaderByName('location')){
            echo 'redirect to ',$new_url,"<br>\r\n";
            
            //process cookies (skip domain, path and secure parameters for simplicity)
            foreach ($response->cookies as $c){
                if (isset($c['expires_ts']) && $c['expires_ts']>time()){
                    $cookies[$c['name']] = $c['value'];
                }else{
                    unset($cookies[$c['name']]);
                }
            }
            $curl->redirect($new_url);
            $curl->Request->registerCustomHeadersArray($request_headers);
            foreach ($cookies as $cookie_name=>$cookie_value){
                $curl->Request->addCookieVar($cookie_name,$cookie_value);
            }

            //set referrer with a direct curl_setopt call
            curl_setopt($curl->ch,CURLOPT_REFERER,$url);
            
            $url = $new_url;
            ++$i;
        }else{
            break;
        }
    }while ($i<$max_redirects);
}catch (gksException $E){
    echo $E->getHtmlMessage();
    exit;
}

//show the content received
echo htmlspecialchars($response);

