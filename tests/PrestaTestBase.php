<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Presta;
/**
 * Description of PrestaTestBase
 *
 * @author sam
 */
class PrestaTestBase extends \PHPUnit_Framework_TestCase {

    public function mock_http_resp($name) {
        $mock_http_resp_dir = dirname(__FILE__)."/mocks/http-responses";
        $name = preg_replace('/\.txt$/i', '', $name).".txt";
        if( ! file_exists("{$mock_http_resp_dir}/{$name}")) {
            throw new \Exception("Http mock response [{$name}] not found in [{$mock_http_resp_dir}]");
        }
        return file_get_contents("{$mock_http_resp_dir}/{$name}");
    }
}