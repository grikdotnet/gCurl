<?php
/**
 * Exception class
 * User: gri
 * Date: 22.03.14
 * Time: 16:45
 */

namespace GKS\GCurl;

/**
 * Exceptions for gCurl
 *
 */
class Exception extends \Exception {

	static $curl_errno;

	static $curl_error;

	/**
	 * The list of exception codes
	 *
	 * @var array
	 */
	private $exception_codes= array(
		1=>'Connection error',
		10=>'Curl extension not loaded',
		15=>'Could not initialize CURL',
		20=>'Invalid handler method name',
		21=>'Error assigning the output stream for headers',
		22=>'Error setting CURL timeout',
		23=>'Error setting URL to connect to',
		25=>'The request is "prepared", can not set new options',
		26=>'The request is not prepared, call the gCurl::prepare() to assign headers and data',
		50=>'Invalid request method',
		51=>'Invalid request parameters',
		51=>'Invalid POST format - the parameter should be a constant',
		60=>'Out of memory',
		70=>'Headers already sent to the user agent',
		80=>'CURL reported error',
		90=>'Invalid delay value',
		110=>'Non-HTTP response headers',
		115=>'Curl returned empty result after execution',
		120=>'Invalid host of the requested URI',
		125=>'Invalid URI',
		130=>'Redirects limit reached',

		300=>'cURL MULTI error',
		302=>'Error running cURL multi requests',
		303=>'No threads registered',
		310=>'The Thread is missing Request',
		320=>'Invalid thread ID',
		330=>'Socket select error',
		335=>'cURL Multi timeout',
		340=>'Handler did not provide a URL, remove thread',

		200=>'Interrupt connection from the handler',
		1000=>'Interrupt connection from the handler',

		401=>'Error opening file for reading',
		402=>'Error allocating memory for PUT request',
	);

	/**
	 * Initialize the exception
	 *
	 * @param int $code
	 * @param int $curl_errno
	 * @param string $curl_error
	 */
	function __construct($code, $curl_errno=0, $curl_error=''){
		//get the error description
		array_key_exists($code, $this->exception_codes) || $code=1;
		$message= $this->exception_codes[$code];
		if ($curl_errno){
			$message.="\nCurl Error #: ".$curl_errno;
		}
		if ($curl_error){
			$message.="\nError message: ".$curl_error;
		}
		//set the error string through the Exception class constructor
		parent::__construct($message, $code);

	}

	/**
	 * Get the message prepared to write to the log file
	 *
	 * @return string
	 */
	function getLogMessage(){
		$log_string='Exception '.$this->getCode().':'.$this->message."\n";
		if ($this->getCode() != 80){
			$log_string .= 'line '.$this->getLine().' file '.$this->getFile()."\n".$this->getTraceAsString()."\n";
		}
		return $log_string;
	}

	/**
	 * Get the error message to output to the browser
	 *
	 * @return string
	 */
	function getHtmlMessage(){
		$message='<b>Exception '.$this->getCode().'</b>: '.$this->message."<br>\n";
		if ($this->getCode() != 80){
			$message .= 'file '.$this->getFile()."\n<br> line ".$this->getLine().
				"<br>\nTrace: <br />\n".nl2br($this->getTraceAsString())."<br>\n";
		}
		return $message;
	}

	/**
	 * Check for an error
	 *
	 * @param resource $ch
	 * @return bool
	 */
	static function catchError($ch=null){
		if ($ch === null){
			//check all

		}else{
			if (!is_resource($ch) || !($curl_errno=curl_errno($ch))){
				return false;
			}
			self::$curl_errno = $curl_errno;
			self::$curl_error = curl_error($ch);
			throw new gCurlException(80,$curl_errno,self::$curl_error);
			return true;
		}

	}

//class end
}
