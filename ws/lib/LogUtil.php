<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LogUtil {
    private static $defLogger;

    // return the istance of the logger
    public static function get() {
        return LogUtil::$defLogger;
    }

    // init the logger
    // @param string $logFile: output file (default 'php://stderr')
    // @param int $logLevel: log level (default Logger::WARNING)
    public static function init($logFile = 'php://stderr', $logLevel = Logger::WARNING) {
        LogUtil::$defLogger = new Logger('applicationLogger');
        LogUtil::$defLogger->pushHandler(new StreamHandler($logFile, $logLevel));
    }
}
