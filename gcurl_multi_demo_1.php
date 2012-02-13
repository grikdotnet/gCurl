<?php

include('gCurlMulti.class.php');
include('gCurlThread.class.php');


class thread1 extends gCurlThread {
    function __construct(){
        parent::__construct('yahoo.com');
    }
    function onSuccess(){
        echo "Success\n";
    }
}

class thread2 extends gCurlThread {
    var $location = '';
    function __construct(){
        parent::__construct('google.com');
    }
    function onSuccess(){
        if (isset($this->Response->headers['location'])){
            
        }
        echo "Success\n";
    }
}

try {
    $t1 = new thread1();
    $t2 = new thread2();
    
    $CM = new gCurlMulti();
    $CM->addThread($t1);
    $CM->addThread($t2);
    $CM->exec();
}catch (gCurlException $E){
    echo $E->getHtmlMessage();
}

var_dump($t1->Response->headers);
echo strlen($t2->Response->body);
