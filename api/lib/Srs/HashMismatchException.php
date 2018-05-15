<?php
/**
 * Created by PhpStorm.
 * User: gioele
 * Date: 15/04/15
 * Time: 10:31
 */

namespace Srs;

use Exception;

class HashMismatchException extends Exception {
    public $givenHash;
    public $calculatedHash;

    function __construct($givenHash, $calculatedHash) {
        $this->givenHash = $givenHash;
        $this->calculatedHash = $calculatedHash;
    }

}