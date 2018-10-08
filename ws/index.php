<?php

require_once 'config.php';

$clientMark = $_GET['mark'];
$include_old_data = (isset($_GET['all']) && !empty($_GET['all']));

$srsDB = new SrsDB(DB_HOST, DB_PORT, $clientMark == "" ? DB_AGG_NAME : DB_RAW_NAME, DB_USER, DB_PASS);


// Handle request parameters
$moduleFilter = 1;
if (is_numeric($_GET['zoom_level'])) {
    LogUtil::get()->info("Zoom level required: " . $_GET['zoom_level']);
    $zoomFilter = new SrsZoomFilter(SrsZoomFilter::DEF_MIN_ZOOM,
                                    SrsZoomFilter::DEF_MAX_ZOOM);
    $moduleFilter = $zoomFilter->getModuleFilter((int)$_GET['zoom_level']);

    LogUtil::get()->info("Module Filter applied: " . $moduleFilter);
}

// bounding box
if ($_GET['bbox'] == "") {
    // invalid request
    $ajxres = array();
    $ajxres['resp'] = 4;
    $ajxres['dberror'] = 0;
    $ajxres['msg'] = 'missing bounding box';
    echo json_encode($ajxres);
}

else {
    // split the bbox into it's parts
    list($left, $bottom, $right, $top) = explode(",", $_GET['bbox']);

	
	
    $srsDB->open();
	if ($clientMark == ""){
		$data = $srsDB->SRS_Get_All_Current_Data($left, $bottom, $right, $top, $moduleFilter, $clientMark);
		//LogUtil::get()->info("Points retrieved: " . print_r($data, true));
	}else {
		$data = $srsDB->SRS_Marked_Raw_Data($left, $bottom, $right, $top, $clientMark, $include_old_data);
	}
    $srsDB->close();

    

    // Enclose data in GeoJson like structure.
    $ajxres = array();
    $features = array();
    $ajxres['type'] = 'FeatureCollection';

    // go through the list adding each one to the array to be returned
    if ($data && count($data) > 0)
        foreach ($data as $row) {
            $f = array();
            $f['geometry'] = json_decode($row['st_asgeojson']);
            $f['ppe'] = $row['ppe'];

            $features[] = $f;
        }

    // add the features array to the end of the ajxres array
    $ajxres['features'] = $features;

    echo json_encode($ajxres);
}

?>
