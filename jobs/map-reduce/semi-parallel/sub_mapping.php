<?php

require_once __DIR__.'/../config/config.inc.php';
require_once __DIR__.'/../datautils/util.php';
require_once __DIR__.'/../databaseutils/SrsRawDB.php';
require_once __DIR__.'/../logutils/DebugUtils.php';
require_once __DIR__.'/../logutils/ProfilingUtils.php';
require_once __DIR__.'/../postprocessing/postprocessing_lib.php';


define("PRINT_PID", true);

ob_implicit_flush(true);
ob_end_flush();

// ******* arguments part ******* //
$roadRoughnessMeters = ROAD_ROUGHNESS_METERS;
$roadRoughnessRange = ROAD_ROUGHNESS_RANGE;
$tryFixProjections = TRY_TO_FIX_PROJECTIONS;

// ******* meta ***************** //
printTimedInfoln("Starting...", PRINT_PID);

// ******* algorithm part ******* //
$srsDB = new SrsRawDB();
$debugObj = new DebugCounter();
$profiler = new TimeCounter();
$overallProfiler = new TimeCounter();
$overallProfiler->Start();

$values = unserialize(getenv('args'));
printDebug("Data received: ".str_replace(array("\r", "\n"), ' ', print_r($values, true)), PRINT_PID);

try {
	printDebugln("Opening the connection to the DB...", PRINT_PID);

	$srsDB -> open();

	printDebugln("Connection open.", PRINT_PID);

    $profiler->Start();

    if($tryFixProjections){
        printInfoln("Trying to fix projections of each SRS point...", PRINT_PID);
        $debugObj ->AddDebugCounter(fix_projections($srsDB, $values, PRINT_PID)); // try to fix projection for each point, track by track.
        printInfoln("Projections fixed of each SRS point...", PRINT_PID);
    }

    $profiler->PrintTime(TimeCounter::$STEP_MAP_MATCHING, PRINT_PID);

} catch(Exception $ex) {
	printErrln("Error opening the connection to the DB: " . $ex, PRINT_PID);
}

println("Closing the connection to the DB", PRINT_PID);

$srsDB -> close();

$overallProfiler->Stop();

if($debugObj){
    $debugObj ->PrintHitsResume("Hits Resume", false, PRINT_PID);
    $debugObj ->PrintExecutionTime(false, PRINT_PID);
}

$overallProfiler->PrintExecutionTime("OVERALL TIME", PRINT_PID);
printTimedInfoln("Ended.", PRINT_PID);
flush();