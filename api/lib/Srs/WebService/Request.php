<?php

namespace Srs\WebService;

use AppToken;
use lib\Srs\ApiKeyException;
use lib\Srs\Configuration;
use Slim\Slim;

class Request {
    const API_KEY_HEADER_KEY = "SRS-APIKey";
    const YOU_SENSE_TOKEN = "Yousense-Token";
    const CONTENT_TYPE_JSON = "application/json";
    const DEFAULT_API_VERSION = 1.0;
    /** @var AppToken */
    public $appToken;
    /** @var String */
    public $apiKey;
    /** @var Slim  */
    protected $app;

    /**
     * @param $app Slim
     */
    public function __construct(Slim $app) {
        $this->app = $app;
    }

    /**
     * @throws ApiKeyException
     */
    public function checkApiToken(){
        $this->apiKey = trim($this->app->request->headers(Request::API_KEY_HEADER_KEY, ""));

        if($this->apiKey == "" || $this->apiKey != Configuration::_get()->apikey){
            throw new ApiKeyException($this->apiKey);
        }
    }

    /**
     * WARNING.
     * Authentication for old protocol. Not working anymore!
     * @param bool $requiredAuthentication true if missing authentication should raise exception, false if missing
     *                                     authentication should just not create a valid LoggedUserUtil
     *
     * @return AppToken
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public function authenticate($requiredAuthentication = false) {
        // previous authentication mechanism
        $youSenseToken = $this->app->request->headers(Request::YOU_SENSE_TOKEN);
        // search appToken object for this token on DB
        $this->appToken = (new \AppTokenQuery())
            ->filterByToken($youSenseToken)
            ->findOne();

        if($requiredAuthentication && $this->appToken == null)
            throw new AuthenticationException;

        return $this->appToken;
    }

    private function decryptServerEncrypted($data){
        // TODO: decrypt body with private server key
        return $data;
    }

    /**
     * @param bool $serverEncrypted
     *
     * @return array
     * @throws MalformedRequestException
     */
    public function getBodyJsonArray($serverEncrypted = false){
        $body = ($this->app->request->headers->get('Content-Encoding') === "gzip") ?
            gzdecode($this->app->request->getBody()):
            $this->app->request->getBody();
        if($serverEncrypted){
            // decrypt body with private server key
            $body = $this->decryptServerEncrypted($body);
        }
        $arr = json_decode($body, true);
        if($arr == null)
            throw new MalformedRequestException;
        return $arr;
    }

    /**
     * @param bool $serverEncrypted
     *
     * @return mixed
     * @throws MalformedRequestException
     */
    public function getBodyObject($serverEncrypted = false){
        $body = $this->app->request->getBody();
        if($serverEncrypted){
            // decrypt body with private server key
            $body = $this->decryptServerEncrypted($body);
        }
        $object = json_decode($body);
        if($object == null)
            throw new MalformedRequestException;
        return $object;
    }

    /**
     * @param int          $httpCode
     * @param BaseResponse $response
     */
    public function printResponse($httpCode, $response) {
        // force API Version for the response
        $response->version = Request::DEFAULT_API_VERSION;
        // set the response content-type
        $this->app->response->headers->set('Content-Type', Request::CONTENT_TYPE_JSON);
        // set HTTP status code properly
        $this->app->response->setStatus($httpCode);
        echo json_encode($response);
    }

}