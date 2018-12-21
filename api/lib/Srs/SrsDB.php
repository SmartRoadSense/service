<?php
namespace Srs;

class SrsDB {
    const POINTS_TABLE = "single_data";
    const QUERY_DISTINCT_TRACKS = "SELECT DISTINCT track AS track FROM single_data;";
    const QUERY_COUNT_RAW_POINTS = "SELECT count(*) AS tot FROM single_data";
    const QUERY_COUNT_AGGR_POINTS = "SELECT count(*) AS tot FROM current";
    private $conn;
    private $dbHost;
    private $dbName;
    private $dbUser;
    private $dbPass;
    private $dbPort;

    public function SrsDB($dbHost, $dbName, $dbUser, $dbPass, $dbPort) {
        $this->dbHost = $dbHost;
        $this->dbName = $dbName;
        $this->dbUser = $dbUser;
        $this->dbPass = $dbPass;
        $this->dbPort = $dbPort;
    }

    public function open() {
        $this->conn = pg_connect("host= " . $this->dbHost . " dbname=" . $this->dbName . " user=" . $this->dbUser . " password=" . $this->dbPass . " port=" . $this->dbPort);

        if (!$this->conn)
            throw new Exception("DB Connection Exception " . pg_last_error($this->conn));
    }

    public function SRS_Update_Data_Projection($howMany = 10000) {
        $updatedIndexes = array();
        $result = pg_query($this->conn, "SELECT DISTINCT srs_update_data_projections_sav($howMany) AS v");

        if (!$result)
            throw new Exception("Error Updating SRS Data Projections: " . pg_last_error($this->conn));

        $row = pg_fetch_array($result);
        while ($row) {
            // then push in the return array
            array_push($updatedIndexes, $row[0]);
            // fetch next row
            $row = pg_fetch_array($result);
        }

        return $updatedIndexes;
    }

    public function SRS_Points_In_Range($refPoint, $points, $range) {
        $arraySql = "";

        foreach ($points as $p) {
            $arraySql .= "'$p',";
        }

        $arraySql = rtrim($arraySql, ",");

        $result = pg_query($this->conn, "SELECT srs_points_in_range(ARRAY[$arraySql], '$refPoint', $range) AS in_range;");

        if (!$result)
            throw new Exception("Error fetching SRS Points in range: " . pg_last_error($this->conn));

        $row = pg_fetch_array($result);

        return ($row['in_range'] === 't') ? true : false;

    }

    public function SRS_Intersection_Close_Enough($aRoad, $bRoad, $points, $range) {

        $arraySql = "";

        foreach ($points as $p) {
            $arraySql .= "'$p',";
        }

        $arraySql = rtrim($arraySql, ",");

        $query = "SELECT srs_intersection_exists($aRoad, $bRoad, ARRAY[$arraySql], $range) AS close_enough;";

        if ($aRoad == 36855184 || $bRoad == 36855184) {
            LogUtil::get()->debug("SRS_Intersection_Close_Enough QUERY:  $query");
        }

        $result = pg_query($this->conn, $query);

        if (!$result)
            throw new Exception("Error fetching SRS Intersection close enough: " . pg_last_error($this->conn));

        $row = pg_fetch_array($result);

        return ($row['close_enough'] === 't') ? true : false;

    }

    public function SRS_Points_Close($aPoint, $bPoint, $range) {

        return $this->SRS_Points_In_Range($aPoint, array($bPoint), $range);

    }

    public function SRS_Road_Roughness_Values($geomId, $meters = 20, $range = 40) {
        $updatedRoughness = array();
        $result = pg_query($this->conn, "SELECT 	ST_AsGeoJson(avg_point) AS p,
													avg_roughness AS r
											FROM 
												SRS_Road_Roughness_Values_sav($geomId, $meters, $range) AS
												result(avg_roughness float, avg_point geometry)");

        if (!$result)
            throw new Exception("Error Updating SRS Road Roughness: " . pg_last_error($this->conn));

        $row = pg_fetch_array($result);
        while ($row) {
            $r = new stdClass;
            $r->point = $row['p'];
            $r->avgRoughness = $row['r'];
            array_push($updatedRoughness, $r);

            // fetch next row
            $row = pg_fetch_array($result);
        }

        return $updatedRoughness;
    }

    public function SRS_Tracks() {
        $tracks = array();
        $result = pg_query($this->conn, SrsDB::QUERY_DISTINCT_TRACKS);

        if (!$result)
            throw new Exception("Error fetching SRS Tracks list: " . pg_last_error($this->conn));

        while ($row = pg_fetch_array($result)) {
            array_push($tracks, $row['track']);
        }

        return $tracks;
    }

    public function SRS_Track_Vals($trackId) {
        $track_vals = array();
        $result = pg_query($this->conn, "SELECT * FROM srs_get_points_on_track('$trackId') order by id;");

        if (!$result)
            throw new Exception("Error fetching SRS Track's values: " . pg_last_error($this->conn));

        while ($row = pg_fetch_array($result)) {
            $record = new stdClass;
            $record->lineId = $row['lineid'];
            $record->singleDataId = $row['id'];
            $record->position = $row['pos'];
            array_push($track_vals, $record);
        }

        return $track_vals;
    }

    public function SRS_Road_Intersections($roadId) {
        $intersection_vals = array();
        $result = pg_query($this->conn, "SELECT * FROM srs_touching_points('$roadId');");

        if (!$result)
            throw new Exception("Error fetching SRS intersecion points: " . pg_last_error($this->conn));

        while ($row = pg_fetch_array($result)) {
            $record = new stdClass;
            $record->lineId = $row['osmid'];
            $record->intersectionPoint = $row['intersection'];

            array_push($intersection_vals, $record);
        }

        return $intersection_vals;
    }

    public function SRS_Distance_From_Point($positions, $refPoint) {
        $distances = array();
        $arraySql = "";

        foreach ($positions as $p) {
            $arraySql .= "'$p',";
        }
        $arraySql = rtrim($arraySql, ",");

        $query = "select  srs_distance_from_point(ARRAY[" . $arraySql . "], '$refPoint');";

        LogUtil::get()->debug("query: " . $query);
        $result = pg_query($this->conn, $query);

        if (!$result)
            throw new Exception("Error fetching SRS distance from point: " . pg_last_error($this->conn));

        while ($row = pg_fetch_array($result)) {
            $distances[] = $row['srs_distance_from_point'];
        }

        return $distances;
    }

    public function SRS_Do_Not_Evaluate($positions) {
        $arraySql = "";

        foreach ($positions as $p) {
            $arraySql .= "'$p',";
        }
        $arraySql = rtrim($arraySql, ",");

        $query = 'UPDATE ' . SrsDB::POINTS_TABLE . ' SET evaluate = 0 WHERE single_data_id IN (' . $arraySql . ');';

        LogUtil::get()->debug("query: " . $query);
        return pg_query($this->conn, $query);
    }

    public function SRS_Debug_Label($positions, $label) {
        $arraySql = "";

        foreach ($positions as $p) {
            $arraySql .= "'$p',";
        }
        $arraySql = rtrim($arraySql, ",");

        $query = 'UPDATE ' . SrsDB::POINTS_TABLE . ' SET debug = ' . $label . ' WHERE single_data_id IN (' . $arraySql . ');';

        LogUtil::get()->debug("query: " . $query);
        return pg_query($this->conn, $query);
    }

    public function SRS_Update_Fixed_Projections($data) {

        $query = $this->build_update_fixed_projections_query($data);

        return pg_query($query);
    }

    public function SRS_ABC_Intersection_Exists($aRoad, $bRoad, $cRoad, $ref_point, $cross_max_distance, $intersection_max_distance) {

        $result = pg_query($this->conn, "SELECT srs_ABC_intersection_exists($aRoad, $bRoad, $cRoad, '$ref_point', $cross_max_distance, $intersection_max_distance) AS intersection_exists;");

        if (!$result)
            throw new Exception("Error fetching SRS Intersection exists enough: " . pg_last_error($this->conn));

        $row = pg_fetch_array($result);

        return ($row['intersection_exists'] === 't') ? true : false;

    }

    private function build_update_fixed_projections_query($data) {
        $values = "";

        foreach ($data as $entry) {
            $values .= "(" . $entry->singleDataId . ", " . $entry->lineId . "),";
        }

        $values = rtrim($values, ",");

        return 'UPDATE ' . SrsDB::POINTS_TABLE . ' AS sd SET osm_line_id = c.lineid
					FROM (values
						' . $values . '
					) AS c(sdid, lineid) 
					WHERE c.sdid = sd.single_data_id;';
    }


    public function SRS_Reset_projections() {
        pg_query('SELECT reset_projections();');
    }


    public function close() {
        pg_close($this->conn);
    }


    //Serra: to clean
    public function SRS_Get_All_Current_Data($left, $bottom, $right, $top, $moduleFilter = 1) {
        $filterCondition = "";
        if (is_numeric($moduleFilter) && $moduleFilter != 1) {
            $filterCondition = "(current.aggregate_id % $moduleFilter = 0) AND ";
        }

        $result = pg_query($this->conn, "SELECT ppe, st_asgeoJson(the_geom)
            FROM current
			  WHERE
			  $filterCondition
			  current.the_geom && ST_MakeEnvelope( $left, $bottom, $right, $top , 4326); ");

        if (!$result)
            throw new Exception("Error Updating SRS Data Projections: " . pg_last_error($this->conn));

        $data = pg_fetch_all($result);

        return ($data);
    }

    public function SRS_Get_raw_count() {
        $result = pg_query($this->conn, SrsDB::QUERY_COUNT_RAW_POINTS);
        $data = pg_Fetch_Object($result, 0);
        return $data->tot;
    }

    public function SRS_Get_aggregate_count() {
        $result = pg_query($this->conn, SrsDB::QUERY_COUNT_AGGR_POINTS);
        $data = pg_Fetch_Object($result, 0);
        return $data->tot;
    }
}
