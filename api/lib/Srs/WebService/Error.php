<?php

namespace Srs\WebService;

class Error {
    public $code;
    public $message;

    function __construct($code, $message) {
        $this->code = $code;
        $this->message = $message;
    }

}