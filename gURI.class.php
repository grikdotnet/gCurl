<?php

/**
 * Parses a URI, provides details and a full correctly composed URI, processes a relative URI redirect
 *
 * @package gCurl
 * @author Grigori Kochanov
 * @version 1.0.1
 */
class gURI{
    /**
     * URI schema part
     *
     * @var string
     */
    public $scheme;
    /**
     * Host name
     *
     * @var string
     */
    public $host;
    /**
     * User (for basic HTTP authentication)
     *
     * @var string
     */
    public $user;
    /**
     * Password (for basic HTTP authentication)
     *
     * @var string
     */
    public $pass;
    /**
     * Path part
     *
     * @var string
     */
    public $path;
    /**
     * Directory part of the path (not part of RFC)
     *
     * @var string
     */
    public $dir;
    /**
     * Query string
     *
     * @var string
     */
    public $query;
    /**
     * Port to connect to (if not 80)
     *
     * @var string
     */
    public $port;
    /**
     * Port part including preceeding semicolon
     *
     * @var string
     */
    public $port_string;
    /**
     * The anchor (after #)
     *
     * @var string
     */
    public $fragment;
    /**
     * Full correct URI in the RFC-compliant format
     *
     * @var string
     */
    public $full;
    
    /**
     * Flag defines whether to process "/../", "/./", "//" and convert backslash to slashes
     *
     * @var bool
     */
    public $normalize_path = false;
    
    /**
     * Symbol used to mark a start of a query
     *
     */
    public $QUERY_DELIMITER = '?';

/**
 * Class constructor
 *
 * @param string $new_uri
 */
function __construct($new_uri=null){
    $new_uri and $this->process($new_uri);
}

/**
 * Parse the URI, return false on error
 *
 * @param string $new_uri
 * @return bool
 */
function process($new_uri){
    //init variables, results of parse_url() may redefine them
    $this->scheme =$this->host=$this->user =$this->pass =$this->path =$this->dir=$this->query=$this->fragment=$this->full='';
    $this->port = 80;
    $this->port_string = ':80';
    if (strpos($new_uri, '://') === false){
        $new_uri = 'http://'.$new_uri;
    }

    //parse current url - get parts
    $uri_array = @parse_url($new_uri);
    if (!$uri_array){
        return false;
    }

    //set varables with parts of URI
    $uri_array['scheme'] = empty($uri_array['scheme'])?'http://': strtolower($uri_array['scheme']).'://';
    //user:password@
    if (!empty($uri_array['user'])){
        if (!empty($uri_array['pass']))
            $uri_array['pass'] = ':'.$uri_array['pass'].'@';
        else {
            $uri_array['user'] .= '@';
            $uri_array['pass'] = '';
        }
    }else {
        $uri_array['user'] = $uri_array['pass'] = '';
    }

    if (!empty($uri_array['port'])){
        $uri_array['port_string'] = ':'.$uri_array['port'];
    }else {
        $uri_array['port'] = 80;
        $uri_array['port_string'] = '';
    }

    if (empty($uri_array['path']) || !trim($uri_array['path'])){
        $uri_array['path'] = '/';
    }

    $uri_array['dir']=$this->dirname($uri_array['path']);
    $uri_array['query'] =empty($uri_array['query'])? '':'?'.$uri_array['query'];
    $uri_array['fragment'] = empty($uri_array['fragment'])?'': '#'.$uri_array['fragment'];

    //assign class fields
    foreach($uri_array as $key=>$value){
        $this->$key = $value;
    }
    $this->get_full_uri();
    return true;
}

/**
 * Processes a new URI using details of a previous one
 *
 * @param string $new_url
 * @return bool
 */
function parse_http_redirect ($new_url){
    if (!$new_url || !is_string($new_url)){
        return false;
    }
    
    //detect if URL is absolute
    if ($method_pos = strpos($new_url, '://')){
        $method = substr($new_url, 0, $method_pos);
        if (!strcasecmp($method, 'http') || !strcasecmp($method,'https')){
            // absolute URL passed
            return $this->process($new_url);
        }else{//invalid protocol
            return false;
        }
    }

    // URL is relative
    //do we have GET data in the URL?
    $param_pos = strpos($new_url, $this->QUERY_DELIMITER);
    if($param_pos !== false){
        $new_query = substr($new_url, $param_pos);
        $new_path = $param_pos ? substr($new_url, 0, $param_pos) : '' ;
    }else{
        $new_path = $new_url;
        $new_query = '';
    }

    //is URL relative to the previous URL path?
    if ($new_url[0] != '/'){
        //attach relative link to the current URI's directory
        $new_path = $this->dirname($this->path).'/'. $new_path;
    }

    if ($this->normalize_path){
        //replace back and repetitive slashes with a single forward one
        $new_path = preg_replace ('~((\\\\+)|/){2,}~', '/', $new_path);
        //parse links to the upper directories
        if (strpos($new_path, '/../') !== false){
            $path_array = explode ('/', $new_path);
            foreach ($path_array as $key=>$value){
                if ($value == '..'){
                    if ($key > 2){
                        unset ($path_array[$key-1]);
                    }
                    unset ($path_array[$key]);
                }
            }
            $new_path = implode ('/', $path_array);
        }
        //parse links to the 'current' directories
        $new_path = str_replace('/./', '/', $new_path);
    }

    $this->path = $new_path;
    $this->query = $new_query;
    $this->get_full_uri();

    return true;
}

/**
 * Returns the directory part of the path (path parameter may include query string)
 * 
 * @param string $path
 * @return string
*/
function dirname($path){
    if(!$path){
        return false;
    }
    $i=strpos($path,'?');
    $dir=$i?substr($path,0,$i):$path;

    $i=strrpos($dir,'/');
    $dir=$i?substr($dir,0,$i):'/';
    $dir[0]=='/' || $dir='/'.$dir;
    return $dir;
}

/**
 * (re)compile the full uri and return the string
 *
 * @return string
 */
function get_full_uri(){
    $this->full = $this->scheme.$this->user.$this->pass.
        $this->host.$this->port_string.$this->path.$this->query;
    return $this->full;
}

/**
 *Checks if the requested host exists
 * 
 *@return bool
 */
function checkHost(){
    
    if (!$this->host){
        throw new URLException(120);
    }
    //host name may be given as IP address or domain name
    $regexp='/^\d{2,3}(\.\d{1,3}){3}$/';
    if(!checkdnsrr($this->host,'A') && !preg_match($regexp,$this->host)){
        throw new URLException(120);
    }
    
}

function __toString(){
    return ($this->full) ? $this->full : $this->get_full_uri();
}

//end of the class
}
