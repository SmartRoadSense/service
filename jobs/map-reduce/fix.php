<?php

require_once 'postprocessing/postprocessing_lib.php';
//require_once 'postprocessing/test_data.php';
//require_once 'postprocessing/SrsDBTest.php';
require_once 'datautils/util.php';
require_once 'config/config.inc.php';
require_once 'databaseutils/SrsRawDB.php';
require_once 'config/CartoDBUploader.php';

//test_main2(); //TODO: REMOVE this when debug finish.

test_main(); //TODO: REMOVE this when debug finish.

$srsDB = new SrsRawDB();

try {
	printDebugln("Opening the connection to the DB...");
	$srsDB -> open();
	printDebugln("Connection open.");

	$tracks = $srsDB -> SRS_Tracks();
	//print_r($tracks);

	foreach($tracks as $val){

		//$els = $srsDB -> SRS_Track_Vals($val);
		//$els = $srsDB -> SRS_Track_Vals('d2196677-de3d-4a9f-a583-cfdc5a7ec9ae');
        $els = $srsDB -> SRS_Track_Vals('2dbf4a67-7f93-40d2-bcfd-e2dea801ea08');



		printDebugln("******* PRIMA ******** TRACK:$val");
		print_data($els);
		//print_labels($els);
		exit();
		printDebugln("******* DOPO ********");
		$changed = false;
		//print_labels(fixOSMIds($els, $changed));

		printDebugln($changed? "CHANGED":"NOT CHANGED");

		if($changed){
			$srsDB -> SRS_Update_Fixed_Projections($els);
		}

		break; //TODO: REMOVE ME!
	}

} catch(Exception $ex) {
	printDebugln("Error opening the connection to the DB: " . $ex);
}

exit(0);
$handle = fopen ("php://stdin","r");
$line = fgets($handle);

echo "\n";
echo "Thank you, continuing...\n";


function test_main(){

    $srsDB = new SrsDBTest();

	$els = dummy_array();
	printDebugln("******* PRIMA ********");
	print_labels($els);

	printDebugln("******* DOPO ********");
	$changed = false;
	print_labels(fixOSMIds($srsDB, $els, $changed));

	printDebugln($changed? "CHANGED":"NOT CHANGED");

	$handle = fopen ("php://stdin","r");
	$line = fgets($handle);

	exit(0);

}

function test_main2(){

    $srsDB = new SrsRawDB();
    $srsDB -> open();

	$intersection_vals[0] = "0101000020E6100000F711AE36B5522940F805AA3583D94540";
    $intersection_vals[1] = "0101000020E6100000F711AE36B5522940F805AA3583D94540";
    $intersection_vals[2] = "0101000020E6100000F711AE36B5522940F805AA3583D94540";

    $ref = "0101000020E6100000F711AE36B5522940F805AA3583D94540";

    printDebugln("result: " .are_points_in_range($srsDB,$ref, $intersection_vals));
    printDebugln($srsDB -> SRS_Points_In_Range($ref, $intersection_vals, 42)? "OK":"NOT OK");

	$handle = fopen ("php://stdin","r");
	$line = fgets($handle);

	exit(0);

}




?>
