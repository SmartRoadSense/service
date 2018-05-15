<?php
/**
 * Created by PhpStorm.
 * User: gioele
 * Date: 23/03/15
 * Time: 14:47
 */
namespace Srs\WebService;

use Exception;

class AuthorizationException extends Exception {

    public $requiredRole;

    function __construct($requiredRole = null) {
        $this->requiredRole = $requiredRole;
    }
}