<?php
/*
 * Resonse Class
 *
 */

/**
 * Very simple class to wrap the HTTP response.
 *
 * @author sam
 */
class Presta_Response {

    private $attributes = array();

    public function __construct($curler_response)
    {
        $this->attributes = $curler_response;
    }

    /**
     * get access to a single entity header value.
     * (get all header key/value pairs w/ $response->headers)
     * 
     * @param string $header_key
     */
    public function header($header_key)
    {
        $result = null;
        if(array_key_exists($header_key, $this->attributes['headers'])) {
            $result = $this->attributes['headers'][$header_key];
        }
        return $result;
    }
    /**
     *
     * @return array The attributes available in this class
     */
    public function attributes()
    {
        return array_keys($this->attributes);
    }
    public function  __get($name)
    {
        return array_key_exists($name, $this->attributes)
            ? $this->attributes[$name]
            : trigger_error("Attempt to access unknown attribute: [{$name}]", E_USER_NOTICE);
    }

}