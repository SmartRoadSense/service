<?php

require_once 'datautils/util.php';
require_once 'config/config.inc.php';
require_once 'databaseutils/SrsRawDB.php';


$srsDB = new SrsRawDB();

try {
	printDebugln("Opening the connection to the DB...");
    $srsDB -> open();
	printDebugln("Connection open.");

    printDebugln("Resetting projections...");
	$srsDB ->SRS_Reset_Projections();
    printDebugln("Projections cleaned.");

} catch(Exception $ex) {
	printDebugln("Error opening the connection to the DB: " . $ex);
}

exit(0);


?>
