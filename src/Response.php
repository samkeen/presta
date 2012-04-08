<?php
/*
 * Resonse Class
 *
 */
namespace Presta;
use Presta\Util\Arr;
/**
 * Very simple class to wrap the HTTP response.
 *
 * @author sam keen
 */
class Response {

    private $attributes = array();
    private $curl_info = array();

    function __construct($raw_http_response, $response_has_headers, array $curl_info=array())
    {
        $this->attributes = $this->parse_response($raw_http_response, $response_has_headers);
        $this->curl_info = $curl_info;
    }

    /**
     * get access to a single entity header value.
     * (get all header key/value pairs w/ $response->headers)
     * 
     * @param $header_key
     * @return string|null
     */
    function header($header_key)
    {
        $result = null;
        if(array_key_exists($header_key, $this->attributes['headers'])) {
            $result = $this->attributes['headers'][$header_key];
        }
        return $result;
    }
    /**
     * getter for all headers
     */
    function headers()
    {
        return $this->attributes['headers']['headers'];
    }
    /**
     *
     * @return array The attributes available in this class
     */
    function attributes()
    {
        return array_keys($this->attributes);
    }
    function parsed_headers()
    {
        return $this->attributes;
    }
    function  __get
    ($name)
    {
        return array_key_exists($name, $this->attributes)
            ? $this->attributes[$name]
            : trigger_error("Attempt to access unknown attribute: [{$name}]", E_USER_NOTICE);
    }
    
    /**
     * Pick the response apart.
     * Separate the Headers from the Enitiy Body if both were returned.
     *
     * Tokenize headers into an array,
     * @param string $http_response
     * @param boolean $resp_includes_headers
     * @return array
     *   ex: array(
     *     'status_code'  => '',
     *     'status_label' => '',
     *     'http_version' => '',
     *     'headers'      => array(), // all keys are lowercase'd
     *     'header_blocks'=> array(),
     *     'entity_body'  => '...'
     *   )
     */
    function parse_response($http_response, $resp_includes_headers) 
    {
        $response = array(
            'headers'       => null,
            'header_blocks' => null,
            'entity_body'   => null,
            'http_version'  => null,
            'status_code'   => null,
            'status_label'  => null
        );
        if(preg_match_all('%HTTP/\d\.\d.*?$.*?\r?\n\r?\n%sim', $http_response, $matches))
        {
            $raw_header_blocks = array_pop($matches);
            foreach($raw_header_blocks as $header_block)
            {
                $response['header_blocks'][] = self::parse_header_block($header_block);
            }
            // set response headers to the final header block
            $response['headers'] = end($response['header_blocks']);
            $response['http_version'] = $response['headers']['http_version'];
            $response['status_code']  = $response['headers']['status_code'];
            $response['status_label'] = $response['headers']['status_label'];
            
            $escaped_last_header_string = str_replace('/', '\/', preg_quote(end($raw_header_blocks)));
            $response['entity_body'] = ltrim(preg_replace('/(.*'.$escaped_last_header_string.')/sm', '', $http_response));
        }
        else // no headers, just entity body
        {
            $response['entity_body'] = $http_response;
        }
        return $response;
    }
    /**
     *
     * @param <type> $header_block
     * @return array
     * <code>
     * ex:
     * Array
        (
            [raw] => {the raw unparsed header block}
            [http_version] => HTTP/1.0
            [status_code] => 302
            [status_label] => Found
            [headers] => Array
                (
                    [location] => http://www.iana.org/domains/example/
                    [server] => BigIP
                    [connection] => Keep-Alive
                    [content-length] => 0
                )

        )
     * </code>
     */
    function parse_header_block($header_block) {
        $parsed_header_block = array(
            'raw'           => $header_block,
            'http_version'  => null,
            'status_code'   => null,
            'status_label'  => null,
            'headers'       => array(),
        );
        
        $header_lines = preg_split('/[\r?\n]+/', $header_block);
        
        // take apart the http declaration on the first line
        $http_declaration = array();
        preg_match('/^(.*)\s+(\d\d\d)\s+(.*)$/', array_shift($header_lines), $http_declaration);
        $parsed_header_block['http_version'] = Arr::get(1, $http_declaration);
        $parsed_header_block['status_code']  = Arr::get(2, $http_declaration);
        $parsed_header_block['status_label'] = Arr::get(3, $http_declaration);

        // parse the rest of the headers into an array
        $processed_headers = array();
        foreach ($header_lines as $header_line) {
            $header_line = explode(':', $header_line, 2);
            if (count($header_line)==2) {
                $header_line = array_map('trim', $header_line);
                if(array_key_exists($header_line[0], $processed_headers)) {
                    if( ! is_array($processed_headers[$header_line[0]])){
                        $processed_headers[$header_line[0]] = array($processed_headers[$header_line[0]]);
                    }
                    $processed_headers[$header_line[0]][] = $header_line[1];
                } else {
                    $processed_headers[$header_line[0]] = $header_line[1];
                }
            }
        }
        $parsed_header_block['headers'] = is_array($processed_headers)
            ? array_change_key_case($processed_headers, CASE_LOWER)
            : $processed_headers;
        return $parsed_header_block;
    }

}