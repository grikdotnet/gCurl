<?php
/**
 * @author Grigori Kochanov http://www.grik.net/
 * @package GCurl
 * Date: 23.03.14
 * Time: 20:47
 */

namespace GCurl;


class PostUrlencodedRequest extends GetRequest{
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
     * Initialize curl
     * @param Options $Options
     */
    public function prepare(Options $Options)
    {
        $Options->commonRequestInit($this);
        $Options->initPostRequest($this);
    }

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
	 * @param string $var_value
	 * @throws \GCurl\Exception
	 */
	public function addPostVar($var, $var_value)
	{
		if (!$var || !is_scalar($var_value)){
			throw new \GCurl\Exception(51);
		}
		$this->post_data[$var] = $var_value;
	}

	/**
	 * Prepare the data for the POST request according to the format
	 *
	 * @return mixed
	 */
	public function getPostFields()
	{
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
