<?php
/*
 *
 * This Class is completly static
 * Handle Network Level Errors here (HTTP errors handled in Presta class)
 *
 */

/**
 * @tdo Look through these and provide shortcut names for the most common ones.
 *        This will keep casual users from having to look up CURL OPT codes, but
 *        we will still accept Actual codes for the more experienced users
 *
 *
 *
 * @author @samkeen
 */
class Presta_Curler {

    

    private static $default_curl_opts = array(
        CURLOPT_HEADER => 1,
        CURLOPT_FOLLOWLOCATION => 1,
    );

    /**
     *
     * @param string $http_method
     * @param string $url
     * @param array $curl_opts @see http://php.net/manual/en/function.curl-setopt.php
     * @param array $entity_headers
     * @param array $entity_body
     * @return array
     *
     */
    public static function xmit($http_method, $url, array $curl_opts=array(), array $entity_headers=null, $entity_body=null)
    {
        $http_method = strtoupper($http_method);
        $handle = curl_init($url); // initialize curl handle
//        $curl_opts = self::sanitize_curlopts($curl_opts);
        /*
         * take the union of the input curl_opts and our defauls, allowing the
         * input to override the defaults
         */
        $curl_opts = $curl_opts + self::$default_curl_opts;
        // but don't allow override of CURLOPT_RETURNTRANSFER => 1, // return result to a variable
        $curl_opts[CURLOPT_RETURNTRANSFER] = 1;
        curl_setopt_array($handle, $curl_opts);
        // set entity headers if they were supplied
        if ($entity_headers) {
            curl_setopt($handle, CURLOPT_HTTPHEADER, $entity_headers);
        }
        $put_data_file = null;
        switch ($http_method) {
            case 'GET':
                break;
            case 'POST':
                curl_setopt($handle, CURLOPT_POST, 1);
                if ($entity_body) {
                    curl_setopt($handle, CURLOPT_POSTFIELDS, $entity_body);
                }
                break;
            case 'PUT':
                curl_setopt($handle, CURLOPT_PUT, 1);
                if ($entity_body) {
                    /* Prepare the data for HTTP PUT. */
                    $put_data_file = tmpfile();
                    fwrite($put_data_file, $entity_body);
                    fseek($put_data_file, 0);
                    curl_setopt($handle, CURLOPT_INFILE, $put_data_file);
                    curl_setopt($handle, CURLOPT_INFILESIZE, strlen($entity_body));
                }
                break;
            case 'DELETE':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case 'HEAD':
                curl_setopt($handle, CURLOPT_NOBODY, 1);
                break;
            case 'OPTIONS':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'OPTIONS');
                break;
            default:
                throw new Exception("Unknown HTTP Method: [{$http_method}]");
                break;
        }

        $http_response = curl_exec($handle);
	$put_data_file ? fclose($put_data_file) : null;
        if ($http_response === false) { // some sort od Network level failure
            throw new Exception("HTTP Communication Failed: Curl Error Number [".curl_errno($handle)."] : ".curl_error($handle));
        }
        $response_info = curl_getinfo($handle);
        curl_close($handle);
        $response_has_headers = $http_method=='HEAD'
                                 ||
                                (Util_Arr::get(CURLOPT_HEADER, $curl_opts)==1);
        $response = self::parse_response($http_response, $response_has_headers);
        $response['info'] = $response_info;
        return $response;
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
    public static function parse_response($http_response, $resp_includes_headers) {
        $response = array(
            'headers'       => null,
            'header_blocks' => null,
            'entity_body'   => null,
            'http_version'  => null,
            'status_code'   => null,
            'status_label'  => null
        );
       
        if ($resp_includes_headers) {
            $processed_headers = array();
            $response_parts = preg_split('/\r?\n\r?\n/', $http_response);
            /*
             * take apart the header block(s)
             */
            while ($response_part = array_shift($response_parts)) {
                if(preg_match('/^HTTP[\.\/\d\s]+\d+/', $response_part)){
                    $response['header_blocks'][] = self::parse_header_block($response_part);
                } else {
                    $response['entity_body'] = $response_part."\n\n".implode("\n\n", $response_parts);
                    break;
                }
            }
            $final_header_sent = end($response['header_blocks']);
            $response['headers'] = $final_header_sent['headers'];
            $response['http_version'] = $final_header_sent['http_version'];
            $response['status_code'] = $final_header_sent['status_code'];
            $response['status_label'] = $final_header_sent['status_label'];
        } else {
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
    public static function parse_header_block($header_block) {
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
        $parsed_header_block['http_version'] = Util_Arr::get(1, $http_declaration);
        $parsed_header_block['status_code'] = Util_Arr::get(2, $http_declaration);
        $parsed_header_block['status_label'] = Util_Arr::get(3, $http_declaration);

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