<?php

require_once 'postprocessing/postprocessing_lib.php';
require_once 'datautils/util.php';
require_once 'config/config.inc.php';
require_once 'databaseutils/SrsRawDB.php';

$srsDB = new SrsRawDB();

try {
	printDebugln("Opening the connection to the DB...");
	$srsDB -> open();
	printDebugln("Connection open.");

    $els = $srsDB -> SRS_Track_Vals('2dbf4a67-7f93-40d2-bcfd-e2dea801ea08');

	//print_labels($els);

} catch(Exception $ex) {
	printDebugln("Error opening the connection to the DB: " . $ex);
}

exit(0);



?>
