<?php
/**
 * Class prepares a PUT request that sends a file
 */

namespace grikdotnet\curl;

class PutFileRequest extends GetRequest
{
    /**
     * @var string
     */
    private $file_path;
    /**
     * @var resource
     */
    private $file_handler;

    /**
     * @param $filename
     */
    public function __construct($uri,$filename)
    {
        parent::__construct($uri);
        if (!is_readable($filename) || !($this->file_handler = fopen($filename,'r'))) {
            throw new \GCURL\Exception(401);
        }
    }

    /**
     * Initialize curl
     * @param Options $Options
     */
    public function prepare(Options $Options)
    {
        $Options->commonRequestInit($this);
        $Options->initPutFileRequest($this);
    }

    /**
     * Return the handler for the file to be sent with PUT request
     * @return resource
     */
    public function getFileHandler()
    {
        return $this->file_handler;
    }

    /**
     * @return int
     */
    public function getFileSize()
    {
        return filesize($this->file_path);
    }

    /**
     * called after curl_exec() returns
     */
    function onRequestEnd()
    {
        parent::onRequestEnd();
        fclose($this->file_handler);
        $this->file_handler = null;
    }
}
