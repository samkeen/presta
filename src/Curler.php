<?php
/*
 *
 * This Class is completly static
 * Handle Network Level Errors here (HTTP errors handled in Presta class)
 *
 */
namespace Presta;
use Presta\Util\Arr;
/**
 * @todo Look through these and provide shortcut names for the most common ones.
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
    
    private static $response_has_headers = false;

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
    public static function make_request($http_method, $url, array $curl_opts=array(), array $entity_headers=null, $entity_body=null)
    {
        if( ! $url)
        {
            throw new \Exception("URI cannot be empty");
        }
        
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
                throw new \Exception("Unknown HTTP Method: [{$http_method}]");
                break;
        }

        $http_response = curl_exec($handle);
        $response_info = curl_getinfo($handle);
        $put_data_file ? fclose($put_data_file) : null;
        if ($http_response === false) { // some sort od Network level failure
            throw new \Exception("HTTP Communication Failed: Curl Error Number [".curl_errno($handle)."] : ".curl_error($handle));
        }
        
        curl_close($handle);
        self::$response_has_headers = 
            $http_method=='HEAD' || (Arr::get(CURLOPT_HEADER, $curl_opts)==1);
        return array('response' => $http_response, 'info' => $response_info);
    }
    /**
     * @static
     * @return bool
     */
    static function response_has_headers()
    {
        return self::$response_has_headers;
    }
}