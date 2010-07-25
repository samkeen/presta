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
class Presta {

    private $STRICT_REST_METHODS = true;

    public function  __construct(array $curl_configs) {
        $this->config = $this->init($curl_configs);
        
        
    }
    /**
     * Return a static instance of Bugzilla.
     *
     * @return  object
     */
    public static function instance(array $curl_configs = array()) {
        static $instance;
        empty($instance) and $instance = new self($curl_configs);
        return $instance;
    }
    /**
     *
     * @param string $uri_string
     */
    public function uri($uri_string) {

    }

    public function get(){}
    public function post($entity_body){}
    public function put($entity_body){}
    public function delete(){}
    public function head(){}

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