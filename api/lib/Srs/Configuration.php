<?php
/**
 * Created by PhpStorm.
 * User: gioele
 * Date: 27/03/15
 * Time: 16:31
 */

namespace lib\Srs;

class Configuration {
    const DEFAULT_API_KEY = "71c56630-abae-423f-93f2-1464d373bc15";
    /**
     * @var Configuration
     */
    protected static $_instance;
    public $apikey;

    public function __construct() {
        $this->apikey = Configuration::DEFAULT_API_KEY;
    }

    /**
     * @return Configuration
     */
    public static function _get() {
        if (Configuration::$_instance == null) {
            Configuration::$_instance = new Configuration();
        }
        return Configuration::$_instance;
    }
}