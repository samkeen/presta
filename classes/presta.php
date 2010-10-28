<?php

/*
 * Available chained methods for the Presta class.  They would typically be used
 *   in the order listed below.
 *
 * - uri
 *
 * - headers (optional)
 *      @param optional array for formatted string of header(s)
 *
 * - response_type (optional)
 *      This is a hint, if excluded, we'll try to sniff the response type
 *      @param optional string [json|html|xml|jsonp]
 *
 * - one of:
 *      - get
 *      - post
 *          @param required array or string of entity body
 *      - put
 *          @param required array or string of entity body
 *      - delete
 *      - head
 *
 * ======= Usage examples =======
 *
 * // post (showing all method)
 * $response = Presta->instance(array(CURLOPT_SSL_VERIFYHOST => 0))
 *      ->uri('http://example.com/customers')
 *      ->headers(
 *          array(
 *              'x-auth-myauth' => 'hcyek8rnflay'
 *          )
 *      )
 *      ->response_type('json')
 *      ->post(
 *          array(
 *              'name' => 'bob'
 *          )
 *      )
 *
 * // post (simple example, only needing required methods)
 * $response = Presta->instance(array())
 *      ->uri('http://example.com/customers')
 *      ->post(
 *          array(
 *              'name' => 'bob'
 *          )
 *      )
 *
 * // short get (could of course be file_get_contents instead, but uniform API is good)
 * $response = Presta->instance()->uri('http://example.com/customers')->get()
 *
 * // delete
 * $response = Presta->instance()
 *      ->uri('http://example.com/customers/1')
 *      ->delete()
 *
 * // put
 * $response = Presta->instance(array())
 *      ->uri('http://example.com/customers/1')
 *      ->response_type('json')
 *      ->put(
 *          array(
 *              'name' => 'bob'
 *          )
 *      )
 */
$base = dirname(__FILE__);
require "{$base}/curler.php";
require "{$base}/presta_util.php";
require "{$base}/response.php";

class Presta {

	// @todo still not sure if I'll enable these.
    private $shortnames = array(
        "fail_on_error" => 45, //CURLOPT_FAILONERROR
        "follow_location" => 52, //CURLOPT_FOLLOWLOCATION
        "max_redirects" => 68, //CURLOPT_MAXREDIRS
        "max_connections" => 71, //CURLOPT_MAXCONNECTS
        "credentials" => 10005, //CURLOPT_USERPWD
        "proxy_credentials" => 10006, //CURLOPT_PROXYUSERPWD
        "timeout" => 13, //CURLOPT_TIMEOUT
        "referer" => 10016, //CURLOPT_REFERER
        "user_agent" => 10018, //CURLOPT_USERAGENT
        "cookie" => 10022, //CURLOPT_COOKIE
        "cookie_session" => 96, //CURLOPT_COOKIESESSION
        "ssl_cert" => 10025, //CURLOPT_SSLCERT
        "ssl_cert_pw" => 10026, //CURLOPT_SSLCERTPASSWD
        "ssl_cert_password" => 10026, //CURLOPT_SSLCERTPASSWD
        "ssl_verify_host" => 81, //CURLOPT_SSL_VERIFYHOST
        "ssl_verify_peer" => 64, //CURLOPT_SSL_VERIFYPEER
        "connection_timeout" => 78, //CURLOPT_CONNECTTIMEOUT
        "cookie_jar" => 10082, //CURLOPT_COOKIEJAR
        "proxy_type" => 101, //CURLOPT_PROXYTYPE
        "buffer_size" => 98, //CURLOPT_BUFFERSIZE
        "http_version" => 84, //CURLOPT_HTTP_VERSION
        "ssl_key" => 10087, //CURLOPT_SSLKEY
        "ssl_key_type" => 10088, //CURLOPT_SSLKEYTYPE
        "ssl_key_pw" => 10026, //CURLOPT_SSLKEYPASSWD
        "ssl_key_password" => 10026, //CURLOPT_SSLKEYPASSWD
        "ssl_engine" => 10089, //CURLOPT_SSLENGINE
        "ssl_engine_default" => 90, //CURLOPT_SSLENGINE_DEFAULT
        "ssl_cert_type" => 10086, //CURLOPT_SSLCERTTYPE
    );

    private $attributes = array('uri' => array(), 'entity_headers' => array());
    private $getable_attributes = array('uri');
    private $curl_opts = array();

    public function __construct(array $curl_opts)
    {
        // @TODO set defaults, look for Presta globals
        $this->curl_opts = $this->normalize_curlopts($curl_opts);
    }

    /**
     * Return a static instance of Presta.
     *
     * @return  object
     */
    public static function instance(array $curl_opts = array())
    {
        static $instance;
        empty($instance) and $instance = new self($curl_opts);
        return $instance;
    }

    /**
     *
     * @param string $uri_string
     */
    public function uri($uri_string)
    {
        $this->attributes['uri'] = $uri_string;
        return $this;
    }
	/**
	 *
	 * @param string $username
	 * @param string $password
	 * @return Presta
	 */
	public function auth($username, $password)
	{
		$this->curl_opts[CURLOPT_USERPWD] = "{$username}:{$password}";
		return $this;
	}

    /**
     * Allow the entity headers to be set for the request
     *
     * @param array $entity_headers
     *   ex: array('Accept-Language' => 'en, en-gb', ...)
     * 
     * @chainable
     * @return $this
     */
    public function headers(array $entity_headers=array())
    {
        $this->attributes['entity_headers'] = $entity_headers;
        return $this;
    }

    // == the HTTP method mthods == //
    public function get()
    {
        return $this->http_xmit('GET');
    }

    public function post($entity_body)
    {
        return $this->http_xmit('POST', $entity_body);
    }

    public function put($entity_body)
    {
        return $this->http_xmit('PUT', $entity_body);
    }

    public function delete()
    {
        return $this->http_xmit('DELETE');
    }

    public function head()
    {
        return $this->http_xmit('HEAD');
    }

    public function options()
    {
        return $this->http_xmit('OPTIONS');
    }

    public function __get($name)
    {
        return in_array($name, $this->getable_attributes) ? $this->attributes[$name] : null;
    }

    /**
     *
     * @param string $http_method
     * @param array $entity_body
     *
     * @TODO ensure proper form for params going to curler_instance()->xmit
     */
    private function http_xmit($http_method, $entity_body = null)
    {
        return new Response(Curler::xmit(
                        $http_method,
                        $this->attributes['uri'], // set by $this->uri('...')
                        $this->curl_opts, // set by $this->instance(array)
                        $this->attributes['entity_headers'], // set by $this->headers(array)
                        $entity_body
        ));
    }

    /**
     *
     * @param array $curl_ops
     */
    private static function normalize_curlopts(array $curl_ops)
    {
        $sanitized_ops = array();
        foreach ($curl_ops as $op_key => $op_value) {
            if( ! is_int($op_key)) {
                if( ! Arr::get($op_key, $this->shortnames)) {
                    throw new Exception("Unrecognized curl option: [{$op_key}]");
                }
                $sanitized_ops[Arr::get($op_key, $this->shortnames)] = $op_value;
            } else {
                $sanitized_ops[$op_key] = $op_value;
            }
            $sanitized_op_name = substr($op_key, 0, 8)=='CURLOPT_' ? $op_key : "CURLOPT_{$op_key}";
            $sanitized_ops[$sanitized_op_name] = $op_value;
        }
        return $sanitized_ops;
    }

}