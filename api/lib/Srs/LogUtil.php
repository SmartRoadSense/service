<?php

namespace Srs;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LogUtil {
    /**
     * @var Logger
     */
    private static $defLogger;

    /**
     * @return Logger
     */
    public static function get() {
        return LogUtil::$defLogger;
    }

    /**
     * @param string $dir the path to the file where to write logs
     * @param int $logLevel the level of logging system (Logger::WARNING is default)
     */
    public static function init($logFile = 'php://stderr', $logLevel = Logger::WARNING) {
        LogUtil::$defLogger = new Logger('applicationLogger');
        LogUtil::$defLogger->pushHandler(new StreamHandler($logFile, $logLevel));
    }
} 
