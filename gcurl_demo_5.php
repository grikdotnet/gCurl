<?php
/**
 * Make a login POST request and receive a session cookie
 *  
 */
//Include library
require('gcurl.class.php');

//init variables
$login = 'mylogin';
$password = 'mypass';
$url = 'http://www.phpclasses.org/login.html?page=';

try{
    //initialize the class
    $curl = new gCurl($url,'POST');

    //prepare POST data
    $curl->Request->addPostVar('alias',$login);
    $curl->Request->addPostVar('password',$password);
    $curl->Request->addPostVar('dologin','1');
    
    //execute the HTTP request
    $response = $curl->exec();
}catch (gksException $E){
    $message = $E->getLogMessage();
    file_put_contents('gcurl.error_log.txt',$message);
    echo $E->getHtmlMessage();
    exit;
}

// if login is correct - the server sets a cookie with a session ID
if ($response->cookies && $response->cookies[0]['name']=='PHPClassesSession'){
    echo 'Login successfull! Session ID: ',$response->cookies[0]['value'];
}else{
    echo 'Invalid login/password or the login form changed';
}

