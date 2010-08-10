<?php
/* 
 * $response = Restly->instance(array('curl_op_1'=>'...', ...))
 *      ->uri('http://example.com/customers')
 *      ->headers(
 *          array(
 *              'x-auth-myauth' => 'hcyek8rnflay'
 *          )
 *      )
 *      ->method('post')
 *      ->entity_body(
 *          array(
 *              'name' => 'bob'
 *          )
 *      )
 *      ->send('json')
 *
 * $response = Restly->instance(array())
 *      ->uri('http://example.com/customers')
 *      ->headers(
 *          array(
 *              'x-auth-myauth' => 'hcyek8rnflay'
 *          )
 *      )
 *      ->post(
 *          array(
 *              'name' => 'bob'
 *          )
 *      )
 *      ->send('json')
 *
 * - uri
 * - headers (optional)
 *      @param optional array for formatted string of header(s)
 * - response_type (optional)
 *      This is a hint, if excluded, we'll try to sniff the response type
 *      @param optional string [json|html|xml|jsonp]
 * - one of 
 *      - get
 *      - post
 *          @param required array or string of entity body
 *      - put required
 *          @param required array or string of entity body
 *      - delete
 *      - head
 * 
 * // post (showing all method)
 * $response = Restly->instance(array())
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
 * $response = Restly->instance(array())
 *      ->uri('http://example.com/customers')
 *      ->post(
 *          array(
 *              'name' => 'bob'
 *          )
 *      )
 *
 * // short get (could of course be file_get_contents instead, but uniform API is good)
 * $response = Restly->instance()->uri('http://example.com/customers')->get()
 * 
 * // delete
 * $response = Restly->instance()
 *      ->uri('http://example.com/customers/1')
 *      ->delete()
 *
 * // put
 * $response = Restly->instance(array())
 *      ->uri('http://example.com/customers/1')
 *      ->response_type('json')
 *      ->put(
 *          array(
 *              'name' => 'bob'
 *          )
 *      )
 */
require dirname(__FILE__).'/curler.php';
require dirname(__FILE__).'/response.php';
class Presta {

    const MIN_PHP_VERSION = '5.1.3';

    private $STRICT_REST_METHODS = true;

    private $attributes = array('uri' => array(), 'entity_headers' => array());
    private $getable_attributes = array('uri');

    private $curl_opts = array();

    public function  __construct(array $curl_opts) {
        $this->curl_opts = $this->init($curl_opts);
    }
    /**
     * Return a static instance of Presta.
     *
     * @return  object
     */
    public static function instance(array $curl_opts = array()) {
        static $instance;
        empty($instance) and $instance = new self($curl_opts);
        return $instance;
    }

    /**
     *
     * @param string $uri_string
     */
    public function uri($uri_string) {
        $this->attributes['uri'] = $uri_string;
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
    public function headers(array $entity_headers=array()) {
        $this->attributes['entity_headers'] = $entity_headers;
        return $this;
    }

    
    // == the HTTP method mthods == //
    public function get(){
        return $this->http_xmit('GET');
    }
    public function post($entity_body){
        return $this->http_xmit('POST', $entity_body);
    }
    public function put($entity_body){
        return $this->http_xmit('PUT', $entity_body);
    }
    public function delete(){
        return $this->http_xmit('DELETE');
    }
    public function head(){
        return $this->http_xmit('HEAD');
    }
    public function options(){
        return $this->http_xmit('OPTIONS');
    }


    public function  __get($name) {
        return in_array($name, $this->getable_attributes) ? $this->attributes[$name] : null;
    }

    public static function sanity_check()
    {
        if (!in_array('curl', get_loaded_extensions())) {
            throw new Exception("curl ext not loaded");
        }
        if (version_compare(PHP_VERSION, self::MIN_PHP_VERSION) < 0) {
            throw new Exception("min PHP version required: " . self::MIN_PHP_VERSION);
        }
    }
    /**
     *
     * @param string $http_method
     * @param array $entity_body
     *
     * @TODO ensure proper form for params going to curler_instance()->xmit
     */
    private function http_xmit($http_method, $entity_body = null) {
        return new Response(Curler::xmit(
            $http_method,
            $this->attributes['uri'],            // set by $this->uri('...')
            $this->attributes['entity_headers'], // set by $this->headers(array)
            $this->curl_opts,                    // set by $this->instance(array)
            $entity_body
        ));
    }

    private function init(array $config) { 
        // set defaults, look for Presta globals
        $this->STRICT_REST_METHODS = defined('PRESTA_STRICT_REST_METHODS')
            ? RESTLY_STRICT_REST_METHODS
            : $this->STRICT_REST_METHODS;
        // .....
        // .....
        $this->curl_config = $config;
    }
}