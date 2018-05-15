<?php

define('DEBUG', true);

function println($text, $printPID = false){
	echo date('Y-m-d H:i:s') . " " . $text .($printPID?" [".getmypid ()."]":""). PHP_EOL;
}

function printDebugln($text = '', $printPID = false){
    if(DEBUG || defined('DEBUG_OVERRIDE')){
	    echo $text .($printPID?" [".getmypid ()."]":""). PHP_EOL;
    }
}

function printDebug($text, $printPID = false){
    if(DEBUG ||defined('DEBUG_OVERRIDE')){
	    echo $text.($printPID?" [".getmypid ()."]":"");
    }
}

function printInfoln($text, $printPID = false){
	echo $text.($printPID?" [".getmypid ()."]":""). PHP_EOL;
}

function printTimedInfoln($text, $printPID = false){
    echo "[".date(DATE_RFC2822)."] ".$text.($printPID?" [".getmypid ()."]":""). PHP_EOL;
}

function printInfo($text, $printPID = false){
	echo $text. ($printPID?" [".getmypid ()."]":"");
}

function printErrln($text, $printPID = false){
    file_put_contents('php://stderr', $text.($printPID?" [".getmypid ()."]":"").PHP_EOL,FILE_APPEND);
}

function printErr($text, $printPID = false){
    file_put_contents('php://stderr', $text.($printPID?" [".getmypid ()."]":""),FILE_APPEND);
}



function fromSrsList2AggregateDBList($roughnessList, $geomId, $roadType){
	$aggDBList = array();
	// preprocessing phase, where elements with null PPE are filtered out
	$filteredList = array_filter($roughnessList, function($el) {
		return $el->avgRoughness != null && $el->avgRoughness != "";
	});

	// now convert only the filtered elements (those with a valid PPE)
	foreach($filteredList as $r){
		$aggData = new stdClass();
		$aggData->ppe = $r->avgRoughness;
		$aggData->osmid = $geomId;
		$aggData->highway = $roadType;

		// decode from GeoJSON
		$point = json_decode($r->point);
		// then retrieve latitude
		$aggData->latitude = $point->coordinates[1];
		// and longitude
		$aggData->longitude = $point->coordinates[0];

		// finally append CartoDBData to the list
		array_push($aggDBList, $aggData);
	}
	return $aggDBList;
}
