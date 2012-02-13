<?php
/**
 * Basic demo : receive and output the content of a remote site
 */
//Include library
require_once('gcurl.class.php');

try{
    //initialize the class
    $curl = new gCurl('http://www.google.com/');
    
    //show the content received
    echo $curl->exec();
}catch (gksException $E){
    $message = $E->getLogMessage();
    file_put_contents('gcurl.error_log.txt',$message);
    echo $E->getHtmlMessage();
}
