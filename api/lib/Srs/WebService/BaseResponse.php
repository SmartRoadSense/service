<?php

namespace Srs\WebService;

class BaseResponse {
    const RESULT_OK = "OK";
    const RESULT_FAILURE = "FAILURE";
    const RESULT_ERROR = "ERROR";

    public $result;
    public $version;

    public function __construct(){
        $this->result = BaseResponse::RESULT_OK;
    }
}

