<?php

require_once 'datautils/util.php';
require_once 'config/config.inc.php';
require_once 'databaseutils/SrsRawDB.php';

$points = array();

for ($i = 1; $i < count($argv); $i++) {
	$points[] = $argv[$i];
}

$srsDB = new SrsRawDB();

try {
	printDebugln("Opening the connection to the DB...");
	$srsDB -> open();
	printDebugln("Connection open.");

	printDebugln("deactivating projections...");
	$srsDB ->SRS_Do_Not_Evaluate($points);
	printDebugln("Projections deactivated.");

} catch(Exception $ex) {
	printDebugln("Error opening the connection to the DB: " . $ex);
}

exit(0);


?>
