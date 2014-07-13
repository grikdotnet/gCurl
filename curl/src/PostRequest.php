<?php
/**
 * @author Grigori Kochanov http://www.grik.net/
 * @package GCurl
 * Date: 23.03.14
 * Time: 20:47
 */

namespace grikdotnet\curl;


use GCurl\scalar;
use grikdotnet\curl\GetRequest;
use grikdotnet\curl\Options;

class PostRequest extends GetRequest{
	/**
	 * data to send with POST request
	 *
	 * @var array
	 */
	public $post_data  = array();

	/**
	 * flag to define the format of the POST data
	 *
	 * @var int
	 */
	public $post_format = Options::POST_URLENCODED;

	const METHOD = 'POST';

	/**
	 * Assign the data prepared for the POST request
	 *
	 * @param string $data
	 */
	public function setRawPostData($data)
	{
		$this->post_data = $data;
		$this->post_format = Options::POST_MULTIPART;
	}

	/**
	 * Add a variable to the  POST request
	 *
	 * @param string $var
	 * @param scalar $var_value
	 * @throws \grikdotnet\curl\Exception
	 */
	public function addPostVar($var, $var_value)
	{
		if (!$var || !is_string($var) || !is_scalar($var_value)){
			throw new \grikdotnet\curl\Exception(51);
		}
		$this->post_data[$var] = $var_value;
	}

	/**
	 * Define the format of the POST request
	 * gCurl::POST_MULTIPART or gCurl::POST_URLENCODED
	 *
	 * @param string $format
	 * @throws \grikdotnet\curl\Exception
	 */
	public function setPostFormat($format)
	{
		if ($format !== Options::POST_MULTIPART  && $format != Options::POST_URLENCODED ){
			throw new \grikdotnet\curl\Exception(52);
		}
		$this->post_format = $format;
	}
	/**
	 * Prepare the data for the POST request according to the format
	 *
	 * @return mixed
	 */
	public function getPostFields()
	{
		if ($this->post_format == Options::POST_MULTIPART ){
			return $this->post_data;
		}
		//POST_URLENCODED
		if (!$this->post_data){
			return '';
		}
		$data = '';
		foreach ($this->post_data as $var=>$var_value){
			$data .= rawurlencode($var). '=' .rawurlencode($var_value).'&';
		}
		return substr($data,0,-1);
	}
}
