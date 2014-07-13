<?php
/**
 * Basic demo : receive and output contents of a remote site
 */
//Include library
require(__DIR__.'/vendor/autoload.php');

try {
    //searching for GCurl
    echo \grikdotnet\curl\Single::GET('https://github.com/search',['q'=>'GCurl','type'=>'Repositories']);

    //log in
    $Response = \grikdotnet\curl\Single::POST('https://github.com/session',['login'=>'login','password'=>'password']);
    echo "<pre>\n";
    print_r($Response->headers);
    echo "</pre>\n";

} catch (\grikdotnet\curl\Exception $E) {
    $message = $E->getLogMessage();
    echo $E->getHtmlMessage();
}
