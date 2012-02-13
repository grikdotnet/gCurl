<?php
include('gCurlMulti.class.php');
include('gCurlThread.class.php');

$THREADS_COUNT = 1;

class ThreadManager{
    /**
     * How many times to retry connection to the URL
     *
     */
    const CONNECTION_ATTEMPTS = 3;
    /**
     * URL list to process
     *
     * @var array (int url_id => string url )
     */
    public $urls = array();
    /**
     * Cursor of the array shows what URLs are processed
     *
     * @var int
     */
    public $cursor = 0;
    /**
     * Results of the processing
     *
     * @var array
     */
    public $results = array();
    
    /**
     * List of URLs that need another attempt
     *
     * @var array
     */
    public $broken_links = array();
    
    /**
     * Count attempts or connections to the URLs
     *
     * @var array (int url_id => int count)
     */
    public $attempts_count = array();
    
    function __construct(array $url_list){
        $this->urls = $url_list;
    }
    
    /**
     * Get the URL to work with and it's ID
     *
     * @return array (ID, URL)
     */
    function getUrl(){
        if ($this->cursor < sizeof($this->urls)){
            return array($this->cursor,$this->urls[$this->cursor++]);
        }
        if ($url = $this->getBrokenLink()){
            return $url;
        }
        return null;
    }
    
    /**
     * Save the result of the URL processing
     *
     * @param int $url_id
     * @param mixed $result
     */
    function reportResult($url_id,$result){
        $this->results[$url_id]=$result;
    }
    
    /**
     * Register the broken connection for the attempts
     *
     * @param int $url_id
     */
    function reportBrokenLink($url_id){
        if (!array_key_exists($url_id,$this->attempts_count)){
            $this->attempts_count[$url_id]=1;
        }elseif ($this->attempts_count[$url_id] <= self::CONNECTION_ATTEMPTS ){
            $this->attempts_count[$url_id]++;
        }else{
            return;
        }
        array_push($this->broken_links,$url_id);
    }

    /**
     * Get the URL for re-connecting
     *
     * @return array (ID, URL)
     */
    function getBrokenLink(){
        if (!$this->broken_links || NULL === ($url_id = array_shift($this->broken_links))){
            return null;
        }
        return array($url_id,$this->urls[$url_id]);
    }
    
//class end
}

class thread extends gCurlThread {
    /**
     * Thread manager reference
     *
     * @var ThreadManager
     */
    public $TM;
    
    public $url_id;

    function __construct(ThreadManager $TM){
        $this->TM = $TM;
        $details = $TM->getUrl();
        $this->url_id = $details[0];
        $this->url = $details[1];
    }
    
    function onSuccess(){
        /**
         * Process response here
         */
        $empty = strlen($this->Response->body) ? 'not empty' : 'empty';
        $this->TM->reportResult($this->url_id,$empty);
    }
    
    function onFailure(){
        $this->TM->reportBrokenLink($this->url_id);
    }
    
    function eof(){
        $new_url = $this->TM->getUrl();
        if ($new_url){
            $this->url_id = $new_url[0];
            $this->url = $new_url[1];
            return false;
        }
        return true;
    }
}

$list = array(
    'http://phpclub.ru/talk/forumdisplay.php?forumid=12',
    'http://phpclub.ru/talk/forumdisplay.php?forumid=13',
    'http://phpclub.ru/talk/forumdisplay.php?forumid=14',
);

$TM = new ThreadManager($list);

try {
    $CM = new gCurlMulti();
    
    for ($i=0;$i<$THREADS_COUNT;++$i){
        $CM->addThread(new thread($TM));
    }
    $CM->exec();
}catch (gCurlException $E){
    echo $E->getHtmlMessage();
}



foreach ($list as $id=>$url){
    if (isset($TM->results[$id]) ){
        echo $url,' is ', $TM->results[$id],"\r\n";
    }else{
        echo 'I could not check ',$url,"\r\n";
    }
}


