<?php

class SrsRawDB {
	const MAX_PAYLOAD_SIZE = 100;
	const POINTS_TABLE = "single_data";
	private $conn;
	private $dbHost;
	private $dbPort;
	private $dbName;
	private $dbUser;
	private $dbPass;

  public function SrsRawDB($dbHost = RAW_DB_HOST,
                           $dbPort = RAW_DB_PORT,
                           $dbName = RAW_DB_NAME,
                           $dbUser = RAW_DB_USER,
                           $dbPass = RAW_DB_PASS) {
		$this -> dbHost = $dbHost;
	  	$this -> dbPort = $dbPort;
	  	$this -> dbName = $dbName;
		$this -> dbUser = $dbUser;
		$this -> dbPass = $dbPass;

	}

	public function open() {
		$this -> conn = pg_connect("host=" . $this->dbHost . " port=".$this->dbPort." dbname=" . $this -> dbName . " user=" . $this -> dbUser . " password=" . $this -> dbPass);

		if (!$this -> conn)
			throw new Exception("DB Connection Exception " . pg_last_error($this -> conn));
	}

	public function SRS_Update_Position4326() {
		//NOT USED ANYMORE
		//return pg_query($this -> conn, 'UPDATE "' . SrsRawDB::POINTS_TABLE . '" SET "Position4326" =  ST_SetSRID("Position",4326) WHERE "Position4326" IS NULL;');
	}

	public function SRS_Update_Data_Projection($howMany = 10000) {
		$updatedIndexes = array();
		$result = pg_query($this -> conn, "SELECT DISTINCT srs_update_data_projections($howMany) AS v");

		if (!$result)
			throw new Exception("Error Updating SRS Data Projections: " . pg_last_error($this -> conn));

		$row = pg_fetch_array($result);
		while ($row) {
			/*if($row[0] != ""){
				// insert this value in the tmp CartoDB table
				pg_query($this -> conn, 'INSERT INTO "' . SrsRawDB::TMP_CARTODB_TABLE . '" (osmLineId) VALUES (' . $row[0] . ');');
			}*/
			// then push in the return array
			array_push($updatedIndexes, $row[0]);
			// fetch next row
			$row = pg_fetch_array($result);
		}

		return $updatedIndexes;
	}

    public function SRS_Clean_Not_Projectable_Points(){

        $result = pg_query($this -> conn, 'UPDATE '.SrsRawDB::POINTS_TABLE.' SET evaluate = 0 where osm_line_id IS NULL  AND projection_fixed = 0 AND evaluate = 1 AND srs_closest_street(position, 0.0018018018) IS NULL;');

        if (!$result)
            throw new Exception("Error Cleaning not projectable points: " . pg_last_error($this -> conn));
    }

    public function SRS_Points_In_Range($refPoint, $points, $range){

        $arraySql = "";

        foreach($points as $p){
            $arraySql .= "'$p',";
        }

        $arraySql = rtrim($arraySql,",");

		$result = pg_query($this -> conn, "SELECT srs_points_in_range(ARRAY[$arraySql], '$refPoint', $range) AS in_range;");

		if (!$result)
			throw new Exception("Error fetching SRS Points in range: " . pg_last_error($this -> conn));

		$row = pg_fetch_array($result);

        return ($row['in_range'] === 't')? true: false;

    }

    public function SRS_Intersection_Close_Enough($aRoad, $bRoad, $points, $range){

        $arraySql = "";

        foreach($points as $p){
            $arraySql .= "'$p',";
        }

        $arraySql = rtrim($arraySql,",");
		$query = "SELECT srs_intersection_exists($aRoad, $bRoad, ARRAY[$arraySql], $range) AS close_enough;";
		$result = pg_query($this -> conn, "SELECT srs_intersection_exists($aRoad, $bRoad, ARRAY[$arraySql], $range) AS close_enough;");

		if (!$result)
			throw new Exception("Error fetching SRS Intersection close enough: " . pg_last_error($this -> conn));

		$row = pg_fetch_array($result);

        return ($row['close_enough'] === 't')? true: false;

    }

    public function SRS_Points_Close($aPoint, $bPoint, $range){

        return $this ->SRS_Points_In_Range($aPoint, array($bPoint), $range);

    }

	public function removeIdFromCartoDBTmp($osmLineId){
		// NOT needed anymore
		//pg_query($this -> conn, 'DELETE FROM "' . SrsRawDB::TMP_CARTODB_TABLE . '" WHERE "osmLineId" = ' . $row[0]);
	}

	public function SRS_Road_Roughness_Values($geomId, $meters = 20, $range = 40, $min_position_resolution = 20, $days = 7) {
		$updatedRoughness = array();
        $query = "SELECT ST_AsGeoJson(avg_point) AS p,
						avg_roughness AS r
				  	FROM
						srs_road_roughness_values($geomId, $meters, $range, $min_position_resolution, $days)
						AS result(avg_roughness float, avg_point geometry)";

		$result = pg_query($this -> conn, $query);

		if (!$result)
			throw new Exception("Error Updating SRS Road Roughness: " . pg_last_error($this -> conn));

		$row = pg_fetch_array($result);
		while ($row) {
			$r = new stdClass;
			$r -> point = $row['p'];
			$r -> avgRoughness = $row['r'];
			array_push($updatedRoughness, $r);

			// fetch next row
			$row = pg_fetch_array($result);
		}

		return $updatedRoughness;
	}

	public function SRS_Tracks() {
		$tracks = array();

		$result = pg_query($this -> conn, 'SELECT DISTINCT track_id as track FROM  '.SrsRawDB::POINTS_TABLE.' WHERE osm_line_id IS NOT NULL  AND projection_fixed  = 0 AND evaluate = 1 AND track_id IS NOT NULL;');

		if (!$result)
			throw new Exception("Error fetching SRS Tracks list: " . pg_last_error($this -> conn));

		while ($row = pg_fetch_array($result)) {
			array_push($tracks, $row['track']);
		}

		return $tracks;
	}

	public function SRS_Track_Vals($trackId) {
		$track_vals = array();
		$result = pg_query($this -> conn, "SELECT * FROM srs_get_points_on_track('$trackId') order by id;");

		if (!$result)
			throw new Exception("Error fetching SRS Track's values: " . pg_last_error($this -> conn));

		while ($row = pg_fetch_array($result)) {
			$record = new stdClass;
			$record -> lineId = $row['line_id'];
			$record -> singleDataId = $row['id'];
            $record -> position = $row['pos'];
			//print_r($record);
			array_push($track_vals, $record);
		}

		return $track_vals;
	}

    public function SRS_CountRawRecord() {
        $tracks = array();
        $result = pg_query($this -> conn, 'SELECT count(*) as tot FROM  '.SrsRawDB::POINTS_TABLE.' WHERE osm_line_id IS NULL AND projection_fixed  = 0 AND evaluate = 1;');

        if (!$result)
            throw new Exception("Error fetching SRS raw records count: " . pg_last_error($this -> conn));

        $row = pg_fetch_array($result);

        return $row['tot'];
    }

    public function SRS_Road_Intersections($roadId){
        $intersection_vals = array();
		$result = pg_query($this -> conn, "SELECT * FROM srs_touching_points('$roadId');");

		if (!$result)
			throw new Exception("Error fetching SRS intersecion points: " . pg_last_error($this -> conn));

		while ($row = pg_fetch_array($result)) {
			$record = new stdClass;
			$record -> lineId = $row['osmid'];
			$record -> intersectionPoint = $row['intersection'];

			//print_r($record);
			array_push($intersection_vals, $record);
		}

		return $intersection_vals;
    }

    public function SRS_Distance_From_Point($positions, $refPoint){
        $distances = array();
        $arraySql = "";

        foreach($positions as $p){
            $arraySql .= "'$p',";
        }
        $arraySql = rtrim($arraySql,",");
        printDebugln("query: ". "select  srs_distance_from_point(ARRAY[".$arraySql."], '$refPoint');");
		$result = pg_query($this -> conn, "select  srs_distance_from_point(ARRAY[".$arraySql."], '$refPoint');");

		if (!$result)
			throw new Exception("Error fetching SRS distance from point: " . pg_last_error($this -> conn));

		while ($row = pg_fetch_array($result)) {
			$distances[] = $row['srs_distance_from_point'];
		}

		return $distances;
    }

    public function SRS_Do_Not_Evaluate($positions){
        $distances = array();
        $arraySql = "";

        foreach($positions as $p){
            $arraySql .= "'$p',";
        }
        $arraySql = rtrim($arraySql,",");

        printDebugln("query: ". 'UPDATE single_data SET evaluate = 0 WHERE single_data.single_data_id IN ('.$arraySql.');');
		return pg_query($this -> conn, 'UPDATE single_data SET evaluate = 0 WHERE single_data.single_data_id IN ('.$arraySql.');');

    }

	public function SRS_Set_As_Fixed($ids){

		if(count($ids) < 1){
			return;
		}

		$arraySql = "";

		foreach($ids as $p){
			$arraySql .= "'$p->singleDataId',";
		}
		$arraySql = rtrim($arraySql,",");

		printDebugln("query: ". 'UPDATE single_data SET projection_fixed = 1 WHERE single_data.single_data_id IN ('.$arraySql.');');
		return pg_query($this -> conn, 'UPDATE single_data SET projection_fixed = 1 WHERE single_data.single_data_id IN ('.$arraySql.');');

	}

    public function SRS_Debug_Label($positions, $label){
        $distances = array();
        $arraySql = "";

        foreach($positions as $p){
            $arraySql .= "'$p',";
        }
        $arraySql = rtrim($arraySql,",");

        //printDebugln("query: ". 'UPDATE single_data SET debug = '.$label.' WHERE single_data_id IN ('.$arraySql.');');
		return pg_query($this -> conn, 'UPDATE single_data SET debug = '.$label.' WHERE single_data_id IN ('.$arraySql.');');

    }

    public function SRS_HistoryFy_Old_Raw_Values(){

        $hours = 7*24; //7 days ago.

        return pg_query($this -> conn, 'SELECT srs_move_raw_to_history('.$hours.');');

    }

	public function SRS_Update_Fixed_Projections($data){

		$query = $this -> build_update_fixed_projections_query($data);

		return pg_query($this -> conn, $query);
	}

    public function SRS_ABC_Intersection_Exists($aRoad, $bRoad, $cRoad, $ref_point, $cross_max_distance, $intersection_max_distance){

        $result = pg_query($this -> conn, "SELECT srs_ABC_intersection_exists($aRoad, $bRoad, $cRoad, '$ref_point', $cross_max_distance, $intersection_max_distance) AS intersection_exists;");

		if (!$result)
			throw new Exception("Error fetching SRS Intersection exists enough: " . pg_last_error($this -> conn));

		$row = pg_fetch_array($result);

        return ($row['intersection_exists'] === 't')? true: false;

    }

	public function SRS_Get_OsmRoad_Data($roadId){

		$result = pg_query($this -> conn, "SELECT * from planet_osm_line  where osm_id = $roadId;");

		if (!$result)
			throw new Exception("Error fetching SRS Intersection exists enough: " . pg_last_error($this -> conn));

		$row = pg_fetch_array($result);

		return $row['highway'];

	}

	private function build_update_fixed_projections_query($data){
		$values = "";

		$changed = false;

		foreach($data as $entry){
			if($entry->lineId != NULL) {
				$values .= "(" . $entry->singleDataId . ", " . $entry->lineId . "),";
				$changed = true;
			}
		}

		if(!$changed) { return; }

		$values = rtrim($values, ",");

		return 'UPDATE '.SrsRawDB::POINTS_TABLE.' AS sd SET osm_line_id = c.lineid
					FROM (values
						'.$values.'
					) AS c(sdid, lineid)
					WHERE c.sdid = sd.single_data_id;';
	}


    public function SRS_Reset_Projections(){
        pg_query($this ->conn, 'SELECT srs_reset_projections();');
    }


	public function close() {
		pg_close($this -> conn);
	}

}
