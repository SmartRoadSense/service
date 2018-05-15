<?php
/**
 * Created by PhpStorm.
 * User: gioele
 * Date: 18/03/15
 * Time: 17:26
 */

namespace Srs\WebService;


class RequestV2 extends Request {
    public function authenticate($requiredAuthentication = false, $requiredRole = null) {
        // TODO: Aldini's authentication mechanism
        throw new \Exception("Not implemented yet");
    }
}