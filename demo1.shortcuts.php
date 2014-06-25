<?php
/**
 * Basic demo : receive and output contents of a remote site
 */
//Include library
require('loader.php');

try {
    //searching for GCurl
    echo \GCurl\Single::GET('https://github.com/search',['q'=>'GCurl','type'=>'Repositories']);

    //log in
    $Response = \GCurl\Single::POST('https://github.com/session',['login'=>'login','password'=>'password']);
    echo "<pre>\n";
    print_r($Response->headers);
    echo "</pre>\n";

} catch (\GCurl\Exception $E) {
    $message = $E->getLogMessage();
    echo $E->getHtmlMessage();
}
