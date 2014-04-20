<?php

namespace GCurl;

/**
 * The class extending this interface will contain methods that will be used as handlers
 * for processing HTTP response
 *
 * @package gCurl
 * @author Grigori Kochanov
 * @version 2.1
 * @abstract
 */
abstract class Handler implements Handlers
{
	/**
	 * Instance of the gCurl class utilizing this handler
	 *
	 * @var single
	 */
	protected $gCurl;

	/**
	 * The handler method triggered after the response headers are received and processed
	 * but before receiving the body
	 *
	 * @param array $headers
	 */
	public function headersHandler(array $headers){}

	/**
	 * The method is triggered after the response headers are received,
	 * it receives an array of cookies set by the server as parameter
	 *
	 * @param array $cookies
	 */
	public function cookiesHandler(array $cookies){}

	/**
	 * Don't use a body handler by default
	 * @return bool
	 */
	public function getUseBodyHandler()
	{
		return false;
	}

	/**
	 * Default body handler
	 *
	 * @param string $chunk
	 */
	public function bodyHandler($chunk){}

	/**
	 * Set the reference to the gCurl object that uses this class methods as handlers
	 *
	 * @param Single $gCurl
	 */
	final public function setGCurlReference(Single $gCurl)
	{
		$this->gCurl = $gCurl;
	}

	/**
	 * Destructor - to avoid a circular reference
	 *
	 */
	final public function cleanGCurlReference()
	{
		$this->gCurl = null;
	}
}
