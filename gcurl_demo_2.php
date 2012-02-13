<pre>
<?php
/**
 * See the headers and cookies of the HTTP response
 *  
 */
//Include library
require('gcurl.class.php');
try{
    //initialize the class
    $curl = new gCurl('http://google.com/');
    //execute the HTTP request
    $response = $curl->exec();
}catch (gksException $E){
    $message = $E->getLogMessage();
    file_put_contents('gcurl.error_log.txt',$message);
    echo $E->getHtmlMessage();
    exit;
}

echo 'Cookies:<br>';
/**
 * Cookies are parsed and represented as an array:
 *     array (
 *         'name' => 'PREF',
 *         'value' => 'ID=b49b10c435c55014:TM=1199581228:LM=1199581228:S=hXaNi8AR8vNlHx52',
 *         'expires' => '2010-01-05 03:10:23',
 *         'path' => '/',
 *         'domain' => 'google.com',
 *         'secure' => NULL,
 *         'expires_ts' => 1262653823,
 *         'expires_gmt' => 'Tue, 05-Jan-2010 01:10:23 GMT',
 *     );
 * 'expires' is a value of an 'Expires' parameter in a local time zone
 * 'expires_ts' is a unix timestamp in local time
 * 'expires_gmt' is an original value of an 'Expires' parameter (should be GMT by spec)
 */
print_r($response->cookies);


//see the headers received
echo '<br>Headers: <br>';

/**
 * Headers are organized both in an associative array and a numeric one.
 *
 * You can access headers by number and by name:
 * $response->headers[2] or 
 * $response->headers['cache-control'][0]
 *
 * Remember, there may be several headers with the same name.
 * When referring the header by name the values are stored as a numeric sub-array.

 */
print_r($response->headers);
