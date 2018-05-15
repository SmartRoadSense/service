<?php

namespace Srs\WebService;

use Srs\HashMismatchException;
use Srs\WebService;
use Srs\LogUtil;

class ErrorResponse extends BaseResponse {
    public $code;
    public $message;
    public $errors;

    public function __construct() {
        parent::__construct();
        $this->result = BaseResponse::RESULT_ERROR;
        $this->errors = array();
    }

    public function addError($error) {
        $this->errors[] = $error;
    }

    public static function createMissingApiKey() {
        $response = new ErrorResponse();
        $response->result = BaseResponse::RESULT_ERROR;
        $response->message = "No API key received.";
        $response->code = 40101;

        return $response;
    }

    public static function createWrongApiKey() {
        $response = new ErrorResponse();
        $response->result = BaseResponse::RESULT_ERROR;
        $response->message = "The API key is not valid.";
        $response->code = 40102;

        return $response;
    }

    public static function createNotAuthenticated() {
        $response = new ErrorResponse();
        $response->result = BaseResponse::RESULT_ERROR;
        $response->message = "Invalid credentials provided";
        $response->code = 40101;
        $response->addError(new Error(40102, "TODO"));

        return $response;
    }

    public static function createNotAuthorized() {
        $response = new ErrorResponse();
        $response->result = BaseResponse::RESULT_ERROR;
        $response->message = "Denied operation";
        $response->code = 40111;
        $response->addError(new Error(40112, "TODO"));

        return $response;
    }

    public static function createNotFound() {
        $response = new ErrorResponse();
        $response->result = BaseResponse::RESULT_ERROR;
        $response->message = "Resource not found";
        $response->code = 40411;
        $response->addError(new Error(40412, "TODO"));

        return $response;
    }

    public static function createMalformedRequest() {
        $response = new ErrorResponse();
        $response->result = BaseResponse::RESULT_ERROR;
        $response->message = "Malformed request";
        $response->code = 40011;
        $response->addError(new Error(40012, "TODO"));

        return $response;
    }

    public static function createSingularityRequest() {
        $response = new ErrorResponse();
        $response->result = BaseResponse::RESULT_ERROR;
        $response->message = "Secret already used";
        $response->code = 40901;
        //$response->addError(new Error(40012, "TODO"));

        return $response;
    }

    public static function createServerError() {
        $response = new ErrorResponse();
        $response->result = BaseResponse::RESULT_ERROR;
        $response->message = "Ops, something unexpected happened. Please report this to administrator";
        $response->code = 40011;
        $response->addError(new Error(40012, "TODO"));

        return $response;
    }

    public static function createHashMismatch(HashMismatchException $ex){
        $response = new ErrorResponse();
        $response->result = BaseResponse::RESULT_ERROR;
        $response->message = "Given hash was " . $ex->givenHash . " | Calculated hash is " . $ex->calculatedHash;
        $response->code = 40020;
        $response->addError(new Error(40020, "Hash mismatch"));

        return $response;
    }

    public static function manageError (\Exception $ex, $app) {

        $request = new \Srs\WebService\Request($app);

        switch(true){
            case $ex instanceof \lib\Srs\ApiKeyException:
                if ($ex->apiKey == "") {
                    $response = \Srs\WebService\ErrorResponse::createMissingApiKey();
                } else {
                    $response = \Srs\WebService\ErrorResponse::createWrongApiKey();
                }
                $request->printResponse(403, $response);
                break;
            case $ex instanceof AuthenticationException:
                LogUtil::get()->warning(("Not authenticated request @ " . $app->request->getPath()));
                $response = \Srs\WebService\ErrorResponse::createNotAuthenticated();
                $request->printResponse(401, $response);
                break;
            case $ex instanceof AuthorizationException:
                LogUtil::get()->warning(("Not authorized request @ " . $app->request->getPath()));
                $response = \Srs\WebService\ErrorResponse::createNotAuthorized();
                $request->printResponse(403, $response);
                break;
            case $ex instanceof NotFoundException:
                $response = \Srs\WebService\ErrorResponse::createNotFound();
                $request->printResponse(404, $response);
                break;
            case $ex instanceof MalformedRequestException:
                $response = \Srs\WebService\ErrorResponse::createMalformedRequest();
                $request->printResponse(400, $response);
                break;
            case $ex instanceof SingularityRequestException:
                $response = \Srs\WebService\ErrorResponse::createSingularityRequest();
                $request->printResponse(409, $response);
                break;
            default:
                // Annotate exception on log
                LogUtil::get()->error("Error on path " . $app->request->getPath() . ". " . $ex);
                $response = \Srs\WebService\ErrorResponse::createServerError();
                $request->printResponse(500, $response);
        }

    }


}