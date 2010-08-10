<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of response
 *
 * @author sam
 */
class Response {

    private $attributes = array(
        'entity_body' => null, // the entity body of the http response
        'headers' => array(),  // the entity headers of the http response
        'info' => array()      // the response info array returned by curl
    );
    private $getable_attributes = array('entity_body', 'headers', 'info');

    public function __construct($curler_response)
    {
        $this->attributes['entity_body'] = isset($curler_response['entity_body'])
            ? $curler_response['entity_body'] : null;
        $this->attributes['headers'] = isset($curler_response['headers'])
            ? $curler_response['headers'] : null;
        $this->attributes['info'] = isset($curler_response['info'])
            ? $curler_response['info'] : null;
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
        if(key_exists($header_key, $this->attributes['headers'])) {
            $result = $this->attributes['headers'][$header_key];
        }
        return $result;
    }

    public function  __get($name)
    {
        $result = null;
        if(in_array($name, $this->getable_attributes)) {
            $result = $this->attributes[$name];
        }
        return $result;
    }

}
?>
