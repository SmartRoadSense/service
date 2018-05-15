<?php

// return the env variable or the default value
function _getenv($env, $default) {
  $value = getenv($env);
  return ($value? $value : $default);
}

DEFINE("ENVIRONMENT", _getenv("ENVIRONMENT", "dev"));
DEFINE("DB_HOST", _getenv("RAW_DB_HOST", "raw-db"));
DEFINE("DB_NAME", _getenv("RAW_DB_NAME", "srs_raw_db"));
DEFINE("DB_USER", _getenv("RAW_DB_USER", "crowd4roads_sw"));
DEFINE("DB_PASS", _getenv("RAW_DB_PASS", "password"));

date_default_timezone_set("Europe/Rome");

// setup the Vendor libraries and custom namespaces autoloading
require_once '/code/vendor/autoload.php';

foreach (glob("/code/api/lib/Srs/*.php") as $filename) {
    require_once $filename;
}

foreach (glob("/code/api/lib/Srs/WebService/*.php") as $filename) {
    require_once $filename;
}

// show warnings and errors?
if (ENVIRONMENT == "dev") {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ERROR);
}

// Init Propel
$serviceContainer = \Propel\Runtime\Propel::getServiceContainer();
$serviceContainer->checkVersion('2.0.0-dev');
$serviceContainer->setAdapterClass('srs_raw', 'pgsql');
$manager = new \Propel\Runtime\Connection\ConnectionManagerSingle();
$manager->setConfiguration(array (
  'classname' => 'Propel\\Runtime\\Connection\\DebugPDO',
  'dsn' => 'pgsql:host='.DB_HOST.';dbname='.DB_NAME,
  'user' => DB_USER,
  'password' => DB_PASS,
  'settings' =>
  array (
    'charset' => 'utf8',
    'queries' =>
    array (
      'utf8' => 'SET NAMES \'UTF8\'',
    ),
  ),
));
$manager->setName('srs_raw');
$serviceContainer->setConnectionManager('srs_raw', $manager);
$serviceContainer->setDefaultDatasource('srs_raw');
$serviceContainer->setLoggerConfiguration('defaultLogger', array (
  'type' => 'stream',
  'path' => 'php://stderr',
  'level' => 100,
));

// init LogUtil for logging purpose
\Srs\LogUtil::init('php://stderr', \Monolog\Logger::DEBUG);

// Slim (routing)
$app = new \Slim\Slim(array(
        'debug' => true, // true show exception details, false run custom error handler
    )
);

$app->notFound(function () use ($app) {
    $request = new \Srs\WebService\Request($app);
    $request->printResponse(404, \Srs\WebService\ErrorResponse::createNotFound());
});

$app->error(function (\Exception $ex) use ($app) {
    \Srs\WebService\ErrorResponse::manageError($ex, $app);
}
);

$app->post("/", function () use ($app) {
    $request = new \Srs\WebService\Request($app);

    try {
        $start_log_time = new DateTime();
        file_put_contents("/tmp/last-request.dmp", "\nDECODE::\t\t".$start_log_time->format('Y-m-d H:i:s')."\n\n\n", FILE_APPEND);

        file_put_contents("/tmp/last-request.dmp", print_r($app->request->headers, true)."\n\n\n");
        //file_put_contents("/tmp/last-request.dmp", print_r($app->request->params(), true)."\n\n\n", FILE_APPEND);
        file_put_contents("/tmp/last-request.dmp",
            (($app->request->headers->get('Content-Encoding') === "gzip") ?
                gzdecode($app->request->getBody()) :
                $app->request->getBody()),
            FILE_APPEND);

        file_put_contents("/tmp/last-request.dmp", "\nDECODE::\t\t".(new DateTime())->format('Y-m-d H:i:s')."\t\t[".((new DateTime())->getTimestamp() - $start_log_time->getTimestamp())."]"."\n\n\n", FILE_APPEND);
        // Create DataBurst from Request body
        $dataBurst = \Srs\DataBurstV2::fromJSONArray($request->getBodyJsonArray());
        file_put_contents("/tmp/last-request.dmp", "PARSE::\t\t".(new DateTime())->format('Y-m-d H:i:s')."\t\t[".((new DateTime())->getTimestamp() - $start_log_time->getTimestamp())."]"."\n\n\n", FILE_APPEND);

        $track = \Srs\DataManager::insertV2($dataBurst);
        file_put_contents("/tmp/last-request.dmp", "INSER::\t\t".(new DateTime())->format('Y-m-d H:i:s')."\t\t[".((new DateTime())->getTimestamp() - $start_log_time->getTimestamp())."]"."\n\n\n", FILE_APPEND);
        // print response
        $response = new \Srs\WebService\BaseResponse();
        $response->track = $track->getTrackId();
        $request->printResponse(200, $response);
        file_put_contents("/tmp/last-request.dmp", "END::\t\t".(new DateTime())->format('Y-m-d H:i:s')."\t\t[".((new DateTime())->getTimestamp() - $start_log_time->getTimestamp())."]"."\n\n\n", FILE_APPEND);
    } catch (\Srs\HashMismatchException $ex) {
        $response = ErrorResponse::createHashMismatch($ex);
        $request->printResponse($response->code, $response);
    }
});

$app->run();
