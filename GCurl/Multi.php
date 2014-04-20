<?php
namespace GKS\GCurl;

/**
 * Main cURL Multi class
 * 
 * @package gCurl
 * @author Grigori Kochanov http://www.grik.net/
 * @version 1.2
 *
 */
class Multi
{
    /**
     * Flags for parameters
     *
     */
    const
    URL = 1,
    HEADERS_HANDLER = 2,
    BODY_HANDLER = 3,
    RETURN_TRANSFER = 4,
    NETWORK_INTERFACE = 5,
    OPTIONS = 6;

    /**
     * Timeout for activity in connections
     *
     */
    public $TIMEOUT=30;

    /**
     * cURL MULTI handler
     *
     * @var resource
     */
    public $mh;

    /**
     * Array of objects that handle Curl connections
     *
     * @var Thread[]
     */
    public $threads = array();

	public function __construct()
    {
        $this->mh = curl_multi_init();
    }

    /**
     * Create a CURL EASY handler and request/response classes for it
     *
     * @param Thread $Thread
     * @throws Exception
     */
	public function addThread(Thread $Thread)
	{
        if (null === $this->mh) {
            $this->mh = curl_multi_init();
        }

        $Thread->gCurlMultiObject = $this;

        if (Exception::catchError($Thread->ch) || !$Thread->ch) {
            throw new Exception(15);
        }

        //set parameters for request
        if (!$Thread->init()) {
            return;
        }

        curl_multi_add_handle ($this->mh,$Thread->ch);
        $this->threads[] = $Thread;
        if ($Thread->handlerDefined('onAdd')) {
            $Thread->onAdd();
        }
    }

    /**
     * Un-register the thread before deleting it
     *
     * @param Thread $Thread
     * @return bool
     */
	public function removeThread(Thread $Thread)
    {
        if (is_resource($Thread->ch)) {
            @curl_multi_remove_handle($this->mh, $Thread->ch);
            curl_close($Thread->ch);
        }
        $key = array_search($Thread, $this->threads);
        if ($key === false){
            return false;
        }
        $this->threads[$key]->onRemove();
        unset($this->threads[$key]);
        return true;
    }

    /**
     * Spin the wheel
     *
     * @throws Exception
     */

	public function exec()
    {
        if (!count($this->threads)){
            throw new Exception(303);
        }

        for ($i=0,$j=count($this->threads);$i<$j;++$i) {
            $Thread = $this->threads[$i]; /* @var $Thread Thread */
            if ($Thread->handlerDefined('onStart')){
                $Thread->onStart();
            }
        }

        do {

            do {
                $mrc = curl_multi_exec($this->mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);

            $thread_renew = false;
            //check for info from cURL
            while ($details = curl_multi_info_read($this->mh)) {
                foreach ($this->threads as $Thread) {
                    if ($details['handle'] === $Thread->ch) {
                        break;
                    }
                }
                curl_multi_remove_handle($this->mh,$Thread->ch);
                if ($details['result'] == CURLE_OK) {
                    //response received successfully
                    $Thread->Response->body = curl_multi_getcontent($Thread->ch);
                    if (isset($Thread->Response->headers['status-class']) &&
                        $Thread->Response->headers['status-class'] == 3
                        || isset($Thread->Response->headers['location']))
                    {
                        $Thread->onRedirect();
                    } else {
                        $Thread->onSuccess();
                    }

                } else {
                    //connection error
                    $Thread->onFailure();
                }
                if (!$Thread->eof()) {
                    //if the thread should be reused - prepare the new request and reassign the handler
                    $Thread->Response->cleanup();
                    if ($Thread->init()) {
                        curl_multi_add_handle ($this->mh,$Thread->ch);
                    }
                    $thread_renew = true;
                } elseif ( is_resource($Thread->ch)) {
                    //thread finished, close resource
                    curl_close($Thread->ch);
                    $Thread->ch = null;
                }

                $Thread->onComplete($details['result']);
            }

            if ($active) {
                // wait for network
                $socket_select = curl_multi_select($this->mh,$this->TIMEOUT);
                if ($socket_select == -1){
                    throw new Exception(330);
                }
                //continue the operations
            } elseif (!$thread_renew) {
                //no active threads left
                break;
            }
            if ( $mrc != CURLM_OK ) {
                //some problem in cURL Multi
                break;
            }

        } while (true);

        if ($mrc != CURLM_OK) {
            if ($mrc == CURLM_OUT_OF_MEMORY) {
                $code = 60;
            }else{
                $code = 300;
            }
            throw new Exception($code);
        }

        curl_multi_close($this->mh);
    }

    /**
     * Return the thread object by its ID
     *
     * @param $thread_id
     * @return Thread
     * @throws Exception
     */
	public function getThread($thread_id)
    {
        if (!isset($this->threads[$thread_id])) {
            throw new Exception(320);
        }
        return $this->threads[$thread_id];
    }
}
