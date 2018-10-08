<?php

// return the env variable or the default value
function _getenv($env, $default) {
  $value = getenv($env);
  return ($value? $value : $default);
}

// aggregate database
DEFINE("DB_HOST", _getenv("AGG_DB_HOST", "agg-db"));
DEFINE("DB_PORT", _getenv("AGG_DB_PORT", "5432"));
DEFINE("DB_RAW_NAME", _getenv("RAW_DB_NAME", "srs_raw_db"));
DEFINE("DB_AGG_NAME", _getenv("AGG_DB_NAME", "srs_agg_db"));
DEFINE("DB_USER", _getenv("AGG_DB_USER", "crowd4roads_sw"));
DEFINE("DB_PASS", _getenv("AGG_DB_PASS", "password"));

DEFINE("DB_META_HOST", _getenv("META_DB_HOST", DB_HOST));
DEFINE("DB_META_PORT", _getenv("META_DB_PORT", DB_PORT));
DEFINE("DB_META_NAME", _getenv("META_DB_NAME", DB_NAME));
DEFINE("DB_META_USER", _getenv("META_DB_USER", DB_USER));
DEFINE("DB_META_PASS", _getenv("META_DB_PASS", DB_PASS));

date_default_timezone_set("Europe/Rome");

// setup the Vendor libraries autoloading
require_once '/code/vendor/autoload.php';

// include library folder
define("LIBRARY_DIR", __DIR__ . "/lib/");
foreach (glob(LIBRARY_DIR. "*.php") as $filename) {
    include $filename;
}

// init LogUtil for logging purpose
LogUtil::init('php://stderr', \Monolog\Logger::DEBUG);
