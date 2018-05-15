<?php

class SrsAggregateDB {
	const MAX_PAYLOAD_SIZE = 100;
	const TABLE_NAME = "current";
	const PPE_COLUMN = "ppe";
	const OSM_ID_COLUMN = "osm_id";
	const GEOM_COLUMN = "the_geom";
	const HIGHWAY_COLUMN = "highway";
    const UPDATED_AT_COLUMN = "updated_at";
	private $conn;
	private $dbHost;
	private $dbPort;
	private $dbName;
	private $dbUser;
	private $dbPass;

  public function SrsAggregateDB($dbHost = AGG_DB_HOST,
                                 $dbPort = AGG_DB_PORT,
                                 $dbName = AGG_DB_NAME,
                                 $dbUser = AGG_DB_USER,
                                 $dbPass = AGG_DB_PASS) {
		$this -> dbHost = $dbHost;
		$this -> dbPort = $dbPort;
		$this -> dbName = $dbName;
		$this -> dbUser = $dbUser;
		$this -> dbPass = $dbPass;
	}

	public function open() {
		$this -> conn = pg_connect("host=" . $this->dbHost ." port= ".$this->dbPort." dbname=" . $this -> dbName . " user=" . $this -> dbUser . " password=" . $this -> dbPass);

		if (!$this -> conn)
			throw new Exception("DB Connection Exception " . pg_last_error($this -> conn));
	}

	public function close() {
		pg_close($this -> conn);
	}

	private function SRS_UploadNewAggregateData($data) {
		// use UPSERT statement
		$upsert = "WITH
 		-- write the new values
 		n(ppe, the_geom, osm_id, highway) AS (
 			VALUES ";
		// prepare each VALUES element
		$values = array();
		foreach ($data as $d)
			array_push($values, "(" . $d -> ppe . ",ST_SetSRID(ST_Point(" . $d -> longitude . "," . $d -> latitude . "),4326), ". $d -> osmid.", '". $d -> highway ."')");
		// then complete the insert statement imploding values with commas
		$upsert .= implode(",", $values);
		$upsert .= "
		),
 		-- update existing rows
 		upsert AS (
			UPDATE " . SrsAggregateDB::TABLE_NAME . " real_table
			SET " . SrsAggregateDB::PPE_COLUMN . " = ((real_table.".SrsAggregateDB::PPE_COLUMN ." + n.ppe)/ 2), " . SrsAggregateDB::OSM_ID_COLUMN . " = n.osm_id, " . SrsAggregateDB::HIGHWAY_COLUMN . " = n.highway, ".SrsAggregateDB::UPDATED_AT_COLUMN." = NOW()
			FROM n WHERE real_table.the_geom = n.the_geom
			RETURNING real_table.the_geom
		)
		-- insert missing rows
		INSERT INTO " . SrsAggregateDB::TABLE_NAME . " (" . SrsAggregateDB::PPE_COLUMN . ", " . SrsAggregateDB::GEOM_COLUMN . ", " . SrsAggregateDB::OSM_ID_COLUMN . ", " . SrsAggregateDB::HIGHWAY_COLUMN . ")
		SELECT n.ppe, n.the_geom, n.osm_id, n.highway FROM n
		WHERE n.the_geom NOT IN (
			SELECT the_geom FROM upsert
		);";
		// finally ask CartoDB to run this statement
		printDebugln("Query to execute " . $upsert, true);

		return pg_query($this -> conn, $upsert);
	}

	public function SRS_UploadAggregateData($data) {
		// check that the list has some data
		if (count($data) == 0)
			throw new Exception("Empty data specified for the upload");

		// to avoid requests too big, split list in sublists
		$arrays = array_chunk($data, SrsAggregateDB::MAX_PAYLOAD_SIZE);
		foreach ($arrays as $arr)
			$this -> SRS_UploadNewAggregateData($arr);
	}

    public function SRS_LastUpdatedValues(){
        $updatedValues = array();
        $query = "SELECT ppe, st_asgeojson(the_geom) as position, osm_id, highway FROM current as real_table WHERE real_table.updated_at > NOW() - INTERVAL '5 hours';";

        $result = pg_query($this -> conn, $query);

        if (!$result)
            throw new Exception("Error Getting last aggregate data updated: " . pg_last_error($this -> conn));

        $row = pg_fetch_array($result);
        while ($row) {
            $r = new stdClass;
            $r -> position = $row['position'];
            $r -> ppe = $row['ppe'];
            $r -> highway = $row['highway'];
            $r -> osm_id = $row['osm_id'];
            array_push($updatedValues, $r);

            // fetch next row
            $row = pg_fetch_array($result);
        }

        return $updatedValues;
    }

    public function SRS_History_Step($limit = 2000000){
        $query = "SELECT srs_current_to_history($limit);";

        $result = pg_query($this -> conn, $query);

        if (!$result)
            throw new Exception("Error in history step process: " . pg_last_error($this -> conn));


    }

}
