<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of http
 *
 * @author sam
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
        $curl_opts = self::sanitize_curlopts($curl_opts);
        /*
         * take the union of the input curl_opts and our defauls, allowing the
         * input to override the defaults
         */
        $curl_opts =  $curl_opts + self::$default_curl_opts;
        // but don't allow override of CURLOPT_RETURNTRANSFER => 1, // return result to a variable
        $curl_opts[CURLOPT_RETURNTRANSFER] = 1;
        curl_setopt_array($handle, $curl_opts);
        // set entity headers if they were supplied
        if ($entity_headers) {
            curl_setopt($handle, CURLOPT_HTTPHEADER, $entity_headers);
        }
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
                    curl_setopt($handle, CURLOPT_POSTFIELDS, $entity_body);
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

        $http_response = curl_exec($handle); // run the whole process
        if ($http_response === false) {
            throw new Exception("HTTP Communication Failed: Curl Error Number [".curl_errno($handle)."] : ".curl_error($handle));
        }
        $response_info = curl_getinfo($handle);
        curl_close($handle);
        $response_has_headers = $http_method=='HEAD'
                                 ||
                                (isset($curl_opts[CURLOPT_HEADER]) && $curl_opts[CURLOPT_HEADER] == 1);
        $response = self::parse_response($http_response, $response_has_headers);
        $response['info'] = $response_info;
        return $response;
    }

    /**
     *
     * @param array $curl_ops
     */
    private static function sanitize_curlopts(array $curl_ops)
    {
        return $curl_ops;
        // @todo consider implementing this
        // allow string names for the curl opts
//        $c = get_defined_constants(true);
//        print_r($c['curl']);
//        $curl_ops = array_change_key_case($curl_ops, CASE_UPPER);
//        $sanitized_ops = array();
//        foreach ($curl_ops as $op_name => $op_value) {
//            $sanitized_op_name = substr($op_name, 0, 8)=='CURLOPT_' ? $op_name : "CURLOPT_{$op_name}";
//            $sanitized_ops[$sanitized_op_name] = $op_value;
//        }
//        return $sanitized_ops;
    }


    /**
     * Pick the response apart.
     * Tokenize headers into an array, separate the Headers from the Enitiy Body
     *   if both were returned.
     * @param string $http_response
     * @param boolean $with_headers
     * @return array
     */
    private static function parse_response($http_response, $with_headers)
    {
        $response = array('headers'=>null, 'entity_body' => null);
        if ($with_headers) {
            $processed_headers = null;
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

            $status_code = array();
            preg_match('/^(.*)\s+(\d\d\d)\s+(.*)$/', array_shift($headers), $status_code);
            $processed_headers['http_version'] = isset($status_code[1]) ? $status_code[1] : null;
            $processed_headers['status_code'] = isset($status_code[2]) ? $status_code[2] : null;
            $processed_headers['status_label'] = isset($status_code[3]) ? $status_code[3] : null;

            foreach ($headers as $header) {
                $header = explode(':', $header, 2);
                if (isset($header[1])) {
                    $processed_headers[trim($header[0])] = trim($header[1]);
                }
            }
            $response['headers'] = is_array($processed_headers) ? array_change_key_case($processed_headers, CASE_LOWER) : $processed_headers;
            $response['entity_body'] = isset($response_parts[1])?$response_parts[1]:null;
        } else {
            $response['entity_body'] = $http_response;
        }
        return $response;
    }
}