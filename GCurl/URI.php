<?php

namespace GCurl;

/**
 * Parses a URI, provides details and a full correctly composed URI, processes a relative URI redirect
 *
 * @package GCurl
 * @author Grigori Kochanov
 * @version 2
 */
class URI{
    /**
     * @var string
     */
    public $scheme = 'http';
	/**
	 * @var string
	 */
	public $scheme_delim = '://';
	/**
	 * User (for basic HTTP authentication)
	 *
	 * @var string
	 */
	public $user;
	/**
	 * @var string
	 */
	public $user_delim;
    /**
     * @var string
     */
    public $host;
    /**
     * Password (for basic HTTP authentication)
     *
     * @var string
     */
    public $pass;
    public $pass_delim;
	/**
	 * Port to connect to (if not 80)
	 *
	 * @var string
	 */
	public $port;
	/**
	 * @var string
	 */
	public $port_delim;
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
	 * @var string
	 */
	public $query_delim;
    /**
     * The anchor (after #)
     *
     * @var string
     */
    public $fragment;
	/**
	 * @var string
	 */
	public $fragment_delim;

	/**
	 * Parse the URI, return false on error
	 *
	 * @param string $new_uri
	 * @return bool
	 */
	function __construct($new_uri){
		if (!is_string($new_uri)){
			throw new \GCurl\Exception(125);
		}
	    if (strpos($new_uri, '://') === false && substr(0,2)!== '//'){
	        $new_uri = 'http://'.$new_uri;
	    }

	    //parse current url - get parts
	    $uri_array = parse_url($new_uri);
	    if (!$uri_array){
	        throw new \GCurl\Exception(125);
	    }

	    //set varables with parts of URI
		if (!empty($uri_array['scheme'])){
			$uri_array['scheme'] = strtolower($uri_array['scheme']);
		}

	    //user:password@
	    if (!empty($uri_array['user'])){
		    $this->user_delim = '@';
	        if (!empty($uri_array['pass']))
	        {
		        $this->pass_delim = ':';
	        }
	    }

	    if (!empty($uri_array['port'])){
	        $this->port_delim = ':';
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
	}

	/**
	 * Processes a new URI using details of a previous one
	 *
	 * @param string $new_url
	 */
	function redirect ($new_url){
	    if (!$new_url || !is_string($new_url)){
	        throw new \GCurl\Exception(125);
	    }

	    //check if URL is absolute
	    if (strpos($new_url, '://') || substr($new_url,0,2) == '//'){
            // absolute URL
	        $this->scheme = $this->user = $this->pass_delim = $this->pass = $this->user_delim =
	        $this->host = $this->port = $this->port_delim =
	        $this->path = $this->query_delim = $this->query =
	        $this->fragment_delim = $this->fragment
		        = '';
	        $this->__construct($new_url);
		    return;
	    }
	    // URL is relative

	    //do we have GET data in the URL?
	    $param_pos = strpos($new_url, $this->query_delim);
	    if ($param_pos !== false) {
	        $new_query = substr($new_url, $param_pos);
	        $new_path = $param_pos ? substr($new_url, 0, $param_pos) : '' ;
	    } else {
	        $new_path = $new_url;
	        $new_query = '';
	    }

	    //is URL relative to the previous URL path?
	    if ($new_url[0] != '/') {
	        //attach relative link to the current URI's directory
	        $new_path = self::dirname($this->path).'/'. $new_path;
	    }

	    $this->path = $new_path;
	    $this->query = $new_query;
	}

	/**
	 * compile the full uri and return the string
	 *
	 * @return string
	 */
	function get_full_uri(){
		return $this->scheme.$this->scheme_delim.
			$this->user.$this->pass_delim.$this->pass.$this->user_delim.
	        $this->host.
			$this->port.$this->port_delim.
			$this->path.
			$this->query_delim.$this->query.
			$this->fragment_delim.$this->fragment
			;
	}

	function __toString(){
	    return $this->get_full_uri();
	}

	/**
	 * Returns the directory part of the path (path parameter may include query string)
	 *
	 * @param string $path
	 * @return string
	 */
	public static function dirname($path){
		if(!$path){
			return false;
		}
		$i = strpos($path,'?');
		$dir = $i?substr($path,0,$i):$path;

		$i = strrpos($dir,'/');
		$dir = $i?substr($dir,0,$i):'/';
		$dir[0] == '/' || $dir = '/'.$dir;
		return $dir;
	}
}