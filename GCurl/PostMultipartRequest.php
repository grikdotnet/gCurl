<?php
/**
 * @author Grigori Kochanov http://www.grik.net/
 * @package GCurl
 */

namespace GCurl;


class PostMultipartRequest extends GetRequest{
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
	const FORMAT = Options::POST_MULTIPART;

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
	public function setPostData($data)
	{
		$this->post_data = $data;
		$this->post_format = Options::POST_MULTIPART;
	}

	/**
	 * Prepare the data for the POST request according to the format
	 *
	 * @return mixed
	 */
	public function getPostFields()
	{
        return $this->post_data;
	}
}
