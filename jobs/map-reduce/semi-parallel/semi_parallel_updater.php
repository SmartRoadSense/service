<?php

require_once __DIR__.'/../config/config.inc.php';
require_once __DIR__.'/../datautils/util.php';
require_once __DIR__.'/../databaseutils/SrsRawDB.php';
require_once __DIR__.'/../databaseutils/SrsAggregateDB.php';
require_once __DIR__.'/../logutils/DebugUtils.php';
require_once __DIR__.'/../logutils/ProfilingUtils.php';
require_once __DIR__.'/../postprocessing/postprocessing_lib.php';

require_once __DIR__.'/semi_parallel_lib.php';

// ******* arguments part ******* //

$projectionSize = SRS_UPDATE_DATA_PROJECTIONS_SIZE;
$roadRoughnessMeters = ROAD_ROUGHNESS_METERS;
$roadRoughnessRange = ROAD_ROUGHNESS_RANGE;
$tryFixProjections = TRY_TO_FIX_PROJECTIONS;
$uploadToAggregateDB = UPLOAD_TO_CARTODB;
$historifyOldData = HISTORIFY_OLD_RAW_DATA;
$outputFilePrefix = "";

// parse command-line arguments for any options
// $argv[0] = <name-of-the-script>
for ($i = 1; $i < count($argv); $i++) {
	if ($argv[$i] === "-s" || $argv[$i] === "-projections-size")
		$projectionSize = $argv[++$i];
    else if ($argv[$i] === "-o" || $argv[$i] === "-output-file-prefix")
        $outputFilePrefix = $argv[++$i];
	else if ($argv[$i] === "-road-roughness-meters")
		$roadRoughnessMeters = $argv[++$i];
	else if ($argv[$i] === "-road-roughness-range")
		$roadRoughnessRange = $argv[++$i];
	else if ($argv[$i] === "-r" || $argv[$i] === "-raw-projections")
		$tryFixProjections = false;
	else if ($argv[$i] === "-d" || $argv[$i] === "-deactivate-upload")
        $uploadToAggregateDB = false;
    else if ($argv[$i] === "-nh" || $argv[$i] === "-do-not-move-historify")
        $historifyOldData = false;
    else if ($argv[$i] === "-debug")
        define('DEBUG_OVERRIDE', true);
    else if ($argv[$i] === "-h") {
		println("SrsSynchronizer Help.");
		println("Options:\n");
		println("-s\t-projections-size INT");
		println("\tSpecify the maximum number of row for which update the projection (contiguous row won't be split).");
		println("-r\t-raw-projections");
		println("\tDeactivate the projections correction algorithm.");
		println("-d\t-deactivate-upload");
		println("\tDon't upload data to CartoDB.");
		println("-road-roughness-meters INT");
		println("\tSpecify the meters between two points on a road where calculate Roughness.");
		println("-road-roughness-range INT");
		println("\tSpecify the range within it include points to calculate the Roughness (for a point on the road).");
        println("-o\t-output-file-prefix STRING");
        println("\tSpecify the out file prefix. It will be prefixed in sub tasks log file names.");
        println("-debug");
        println("\tActivate debug output.");

		exit ;
	}
}

// ******* meta ***************** //
printTimedInfoln("Starting...");
ob_implicit_flush(true);
ob_end_flush();

// ******* algorithm part ******* //

$srsDB = new SrsRawDB();
$srsAggregateDB = new SrsAggregateDB();
$debugObj = new DebugCounter();
$profiler = new TimeCounter();
$overallProfiler = new TimeCounter();
$overallProfiler->Start();

try {
	printDebugln("Opening the connection to the DB...");

	$srsDB -> open();

	printDebugln("Connection open.");

    //Local
    printDebugln("Opening the connection to local aggregations DB...");
    $srsAggregateDB -> open();
    printDebugln("Connection open.");

    $countRecords = $srsDB -> SRS_CountRawRecord();
    printInfoln("Elaborating ".($countRecords > $projectionSize ? $projectionSize : $countRecords)." on $countRecords total records.");

    $profiler->PrintHowMany(($countRecords > $projectionSize ? $projectionSize : $countRecords),$countRecords);
    $profiler->Start();

    $profiler->PrintTime(TimeCounter::$STEP_CAST);

	printInfoln("Updating projections for new SRS points...");
	// call SRS_Update_Data_Projection() to update the projections
	$updatedKeys = $srsDB -> SRS_Update_Data_Projection($projectionSize);
	printInfoln("Projections UPDATED for new SRS points.");

    $profiler->PrintTime(TimeCounter::$STEP_PROJECTION);

    printInfoln("Cleaning points too distant from an OSM line...");
    $srsDB -> SRS_Clean_Not_Projectable_Points();
    printInfoln("Points too distant from an OSM line cleaned.");

    $profiler->PrintTime(TimeCounter::$STEP_CLEAN);

	if($tryFixProjections){
		printInfoln("Trying to fix projections of each SRS point...");
		//OLD ONE: $debugObj ->AddDebugCounter(parallel_fix_projections($srsDB)); // try to fix projection for each point, track by track.

		$tracks = $srsDB -> SRS_Tracks();
		//print_r($tracks);
        try {
		$processes = start_parallel_execution(SUB_MAPPING_ROUTINE_CMD, $tracks, WORKERS, 0);
            if ($processes !== FALSE && is_array($processes) && count($processes) > 0) {
		merge_parallel_execution($processes);
            } else {
                printDebugln("No map-matching sub process has been started.");
            }
        } catch(\RuntimeException $ex) {
            printErrln("Error during the map-matching process.");
            printErrln($ex);
            exit(1);
        }

		printInfoln("Projections fixed of each SRS point...");
	}

    $profiler->PrintTime(TimeCounter::$STEP_MAP_MATCHING);
	//exit(0);
	if($uploadToAggregateDB){
		printDebugln("Found new values on OsmLines with the following OsmIds: ");
		printDebug(print_r($updatedKeys, true));
		printDebugln("");

		try {
		$processes = start_parallel_execution(SUB_AGGREGATION_ROUTINE_CMD, $updatedKeys, WORKERS, 0);
		print_r($processes);

            if ($processes !== FALSE && is_array($processes) && count($processes) > 0) {
            merge_parallel_execution($processes);
        } else {
            printDebugln("No updated OSM ID line to aggregate.");
        }
        } catch(\RuntimeException $ex) {
            printErrln("Error aggregating raw data.");
            printErrln($ex);
            exit(1);
        }


        $profiler->PrintTime(TimeCounter::$STEP_LOCAL_AGGREGATION);
        $profiler->PrintTime(TimeCounter::$STEP_REMOTE_UPLOAD);
	}


    if($historifyOldData){
        try {
            printInfoln("Historifying old raw data...");
            $srsDB->SRS_HistoryFy_Old_Raw_Values();
            printInfoln("Old raw data moved.");
        }catch(Exception $ex) {
            printErrln("Error historifying old raw data.");
            printErrln($ex);
        }
    }

    $profiler->PrintTime(TimeCounter::$STEP_MOVING_OLD_DATA);

} catch(Exception $ex) {
	printErrln("Error opening the connection to the DB: " . $ex);
	exit(1);
}

println("Closing the connection to the DB");

$srsDB -> close();

$overallProfiler->Stop();

if($debugObj){
    $debugObj ->PrintHitsResume("Hits Resume");
    $debugObj ->PrintExecutionTime(false);
}

$overallProfiler->PrintExecutionTime("OVERALL TIME");
printTimedInfoln("Ended.");
