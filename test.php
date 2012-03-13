<?php
require_once('gCurl.class.php');

$Curl = new gCurl('http://devel/test.php');

curl_setopt($Curl->ch, CURLOPT_COOKIEJAR, "cookie.txt");
curl_setopt($Curl->ch, CURLOPT_COOKIEFILE, "cookie.txt");
 
echo $Curl->exec();
echo $Curl->exec();