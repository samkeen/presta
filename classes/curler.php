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
class Curler {


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
		$put_data_file?fclose($put_data_file):null;
        if ($http_response === false) { // some sort od Network level failure
            throw new Exception("HTTP Communication Failed: Curl Error Number [".curl_errno($handle)."] : ".curl_error($handle));
        }
        $response_info = curl_getinfo($handle);
        curl_close($handle);
        $response_has_headers = $http_method=='HEAD'
                                 ||
                                (Arr::get(CURLOPT_HEADER, $curl_opts)==1);
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
     * @param boolean $with_headers
     * @return array
     *   ex: array(
     *     'status_code'  => '',
     *     'status_label' => '',
     *     'http_version' => '',
     *     'headers'      => array(), // all keys are lowercase'd
     *     'entity_body'  => '...'
     *   )
     */
    private static function parse_response($http_response, $with_headers)
    {
        $response = array('headers'=>null, 'entity_body' => null);
        if ($with_headers) {
            $processed_headers = array();
            $response_parts = preg_split('/\r?\n\r?\n/', $http_response);
            // find the dividing index between header and content
            /**
             * @todo See if the header and body size from
             * curl_getinfo($handle) can be reliably used to determine this
             */
            $i = 0;
            while (preg_match('/^HTTP[\.\/\d\s]+\d+/', $response_parts[$i], $match)) {
                $i++;
            }
            $headers = implode("\n", array_slice($response_parts, 0, $i));
            $headers = preg_split('/[\r?\n]+/', $headers);
            // extract first line containing HTTP version, status code/label
            $status_code = array();
            $status_code = array();
            while(preg_match('/^(.*)\s+(\d\d\d)\s+(.*)$/', array_shift($headers), $status_code))
            {
                $response['processed_status_codes'][] = array(
                    'http_version'    => Arr::get(1, $status_code),
                    'status_code'     => Arr::get(2, $status_code),
                    'status_label'    => Arr::get(3, $status_code),
                );
            }
            $last_status = end($response['processed_status_codes']);
            $response['http_version'] = Arr::get('http_version', $last_status);
            $response['status_code']  = Arr::get('status_code', $last_status);
            $response['status_label'] = Arr::get('status_label', $last_status);
            // parse the rest of the headers into an array
            foreach ($headers as $header) {
                $header = explode(':', $header, 2);
                if (count($header)==2) {
                    $header = array_map('trim', $header);
                    if(array_key_exists($header[0], $processed_headers)) {
                        $processed_headers[$header[0]][] = $header[1];
                    } else {
                        $processed_headers[$header[0]] = $header[1];
                    }
                }
            }
            $response['headers'] = is_array($processed_headers)
                ? array_change_key_case($processed_headers, CASE_LOWER)
                : $processed_headers;
            $response['entity_body'] = end($response_parts);
        } else {
            $response['entity_body'] = $http_response;
        }
        return $response;
    }
}