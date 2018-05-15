<?php

require_once __DIR__.'/config/config.inc.php';
require_once __DIR__.'/datautils/util.php';
require_once __DIR__.'/databaseutils/SrsAggregateDB.php';
require_once __DIR__.'/logutils/DebugUtils.php';

// ******* meta ***************** //
printTimedInfoln("Starting...");

// ******* algorithm part ******* //

$debugObj = new DebugCounter();
$debugObj->Start();

$srsAggregateDB = new SrsAggregateDB();

try {
    //Local
    printDebugln("Opening the connection to local aggregations DB...");
    $srsAggregateDB -> open();
    printDebugln("Connection open.");

    printInfoln("Storing one week-old data in history...");
    try {
        $srsAggregateDB->SRS_History_Step();
    }
    catch(Exception $ex) {
        printErrln("Error while storing one week-old data in local aggregations db.");
        printErrln($ex);
    }

    printInfoln("");

} catch(Exception $ex) {
	printErrln("Error opening the connection to the DB: " . $ex);
}

println("Closing the connection to the DB");

$srsAggregateDB -> close();

$debugObj ->Stop();
$debugObj ->PrintExecutionTime(false);

printTimedInfoln("Ended.");
