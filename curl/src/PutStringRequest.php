<?php
/**
 * Class prepares a PUT request with data taken from a variable
 */

namespace grikdotnet\curl;

class PutStringRequest extends PostUrlencodedRequest
{
    /**
     * @var string
     */

    private $data;

    /**
     * @param $filename
     */
    public function __construct($uri,$data)
    {
        $this->data = $data;
    }

    /**
     * Initialize curl
     * @param Options $Options
     */
    public function prepare(Options $Options)
    {
        $Options->commonRequestInit($this);
        $Options->initPutStringRequest($this);
    }

    public function getData()
    {
        return $this->data;
    }
}