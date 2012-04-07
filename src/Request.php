<?php
/**
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
namespace Presta;
/**
 * 
 */
class Request {

    private $attributes = array('uri' => array(), 'entity_headers' => array());
    private $getable_attributes = array('uri');
    private $curl_opts = array();

    public function __construct(array $curl_opts = array())
    {
        // @TODO set defaults, look for Presta globals
        $this->curl_opts = $curl_opts;
    }

    /**
     * Return a static instance of Presta.
     * 
     * @static
     * @param array $curl_opts
     * @return Request
     */
    public static function instance(array $curl_opts = array())
    {
        static $instance;
        empty($instance) and $instance = new self($curl_opts);
        return $instance;
    }

    /**
     * @param $uri_string
     * @return Request
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
	 * @return Request
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
     * @return $this
     */
    public function headers(array $entity_headers=array())
    {
        $this->attributes['entity_headers'] = $entity_headers;
        return $this;
    }

    /**
     * Invoke the GET HTTP method
     * 
     * @return Response
     */
    public function get()
    {
        return $this->make_http_request('GET');
    }
    /**
     * Invoke the POST HTTP method
     * 
     * @param $entity_body
     * @return Response
     */
    public function post($entity_body)
    {
        return $this->make_http_request('POST', $entity_body);
    }
    /**
     * Invoke the PUT HTTP method
     * 
     * @param $entity_body
     * @return Response
     */
    public function put($entity_body)
    {
        return $this->make_http_request('PUT', $entity_body);
    }
    /**
     * Invoke the DELETE HTTP method
     * 
     * @return Response
     */
    public function delete()
    {
        return $this->make_http_request('DELETE');
    }
    /**
     * Invoke the HEAD HTTP method
     * 
     * @return Response
     */
    public function head()
    {
        return $this->make_http_request('HEAD');
    }
    /**
     * Invoke the OPTIONS HTTP method
     * 
     * @return Response
     */
    public function options()
    {
        return $this->make_http_request('OPTIONS');
    }
    
    
    /**
     * Generalized Getter for any of $this->getable_attributes
     * 
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return in_array($name, $this->getable_attributes) ? $this->attributes[$name] : null;
    }

    /**
     * Make the actual http request using Curl
     * 
     * @TODO ensure proper form for params going to curler_instance()->make_request
     * 
     * @param string $http_method (one of GET|POST|PUT|DELETE|HEAD|OPTIONS)
     * @param string|null $entity_body
     * @return Response
     */
    private function make_http_request($http_method, $entity_body = null)
    {
        $http_response = Curler::make_request(
            $http_method,
            $this->attributes['uri'], // set by $this->uri('...')
            $this->curl_opts, // set by $this->instance(array)
            $this->attributes['entity_headers'], // set by $this->headers(array)
            $entity_body
        );
        return new Response($http_response['response'], Curler::response_has_headers(), $http_response['info']);
    }

}