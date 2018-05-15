<?php

require_once __DIR__.'/../config/config.inc.php';
require_once __DIR__.'/../datautils/util.php';
require_once __DIR__.'/../databaseutils/SrsRawDB.php';
require_once __DIR__.'/../databaseutils/SrsAggregateDB.php';
require_once __DIR__.'/../logutils/DebugUtils.php';
require_once __DIR__.'/../logutils/ProfilingUtils.php';


define("PRINT_PID", true);

ob_implicit_flush(true);
ob_end_flush();

// ******* arguments part ******* //
$projectionSize = SRS_UPDATE_DATA_PROJECTIONS_SIZE;
$roadRoughnessMeters = ROAD_ROUGHNESS_METERS;
$roadRoughnessRange = ROAD_ROUGHNESS_RANGE;
$tryFixProjections = TRY_TO_FIX_PROJECTIONS;
$uploadToAggregateDB = UPLOAD_TO_CARTODB;
$historifyOldData = HISTORIFY_OLD_RAW_DATA;

// ******* meta ***************** //
printTimedInfoln("Starting...", true);

// ******* algorithm part ******* //
$srsDB = new SrsRawDB();
$srsAggregateDB = new SrsAggregateDB();
$debugObj = new DebugCounter();
$profiler = new TimeCounter();
$overallProfiler = new TimeCounter();
$overallProfiler->Start();

$values = unserialize(getenv('args'));
printDebug("Data received: ".str_replace(array("\r", "\n"), ' ', print_r($values, true)), PRINT_PID);
printDebugln("", PRINT_PID);

try {
	printDebugln("Opening the connection to the DB...", PRINT_PID);
	$srsDB -> open();
	printDebugln("Connection open.", PRINT_PID);

    //Local
    printDebugln("Opening the connection to local aggregations DB...", PRINT_PID);
    $srsAggregateDB -> open();
    printDebugln("Connection open.", PRINT_PID);

    $profiler->Start();

	printDebugln("Found new values on OsmLines with the following OsmIds: ", PRINT_PID);
    printDebug(print_r($values, true), PRINT_PID);
    printDebugln("", PRINT_PID);

    // for each GeomId returned, calculate the updated roughness value
    var_dump($values);
    foreach ($values as $geomId) { //Track by track
        if ($geomId != null && $geomId != "") {

            printDebugln("Get OsmRoad highways type with OsmId" . $geomId, PRINT_PID);
            $roadType = $srsDB -> SRS_Get_OsmRoad_Data($geomId);
            printDebugln("RoadType:" . $roadType, PRINT_PID);

            printInfoln("Calculate roughness on point along OsmLine with OsmId " . $geomId. " (road type:$roadType)", PRINT_PID);
            $updatedRoughness = $srsDB -> SRS_Road_Roughness_Values($geomId, $roadRoughnessMeters, $roadRoughnessRange);
            print_r($updatedRoughness);
            printInfoln("Uploading into local db...", PRINT_PID);
            try {

                $aggregateList = fromSrsList2AggregateDBList($updatedRoughness, $geomId, $roadType);

                if (count($aggregateList) > 0) {
                    $srsAggregateDB->SRS_UploadAggregateData($aggregateList);
                    printDebugln("Roughness calculated along OsmLine (with OsmId " . $geomId . ") UPLOADED to local aggregations db. Removing from tmp table...", PRINT_PID);
                }else{
                    printDebugln("Aggregate list is null!", PRINT_PID);
                }

            }
            catch(Exception $ex) {
                printErrln("Error uploading data into local aggregations db.", PRINT_PID);
                printErrln($ex, PRINT_PID);
            }


            printInfoln("", PRINT_PID);
        }
    }

    $profiler->PrintTime(TimeCounter::$STEP_LOCAL_AGGREGATION, PRINT_PID);
    $profiler->PrintTime(TimeCounter::$STEP_REMOTE_UPLOAD, PRINT_PID);

} catch(Exception $ex) {
	printErrln("Error opening the connection to the DB: " . $ex, PRINT_PID);
}

println("Closing the connection to the DB", PRINT_PID);

$srsDB -> close();

$overallProfiler->Stop();

if($debugObj){
    $debugObj ->PrintHitsResume("Hits Resume", PRINT_PID);
    $debugObj ->PrintExecutionTime(false, PRINT_PID);
}

$overallProfiler->PrintExecutionTime("OVERALL TIME", PRINT_PID);
printTimedInfoln("Ended.", PRINT_PID);
flush();
