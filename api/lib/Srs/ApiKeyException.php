<?php
/**
 * Created by PhpStorm.
 * User: gioele
 * Date: 27/03/15
 * Time: 13:08
 */

namespace lib\Srs;


use Exception;

class ApiKeyException extends Exception {

    public $apiKey;

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
}