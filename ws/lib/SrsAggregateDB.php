<?php

class SrsAggregateDB {
	const CURRENT_TABLE_NAME = "current";
	const BOUNDARY_TABLE_NAME = "pilot_boundary";
	const ID_COLUMN = "osm_id";

	private $conn;
	private $dbHost;
	private $dbName;
	private $dbUser;
	private $dbPass;
	private $dbPort;

	public function SrsAggregateDB($dbHost, $dbPort, $dbName, $dbUser, $dbPass) {
		$this -> dbHost = $dbHost;
		$this -> dbPort = $dbPort;
		$this -> dbName = $dbName;
		$this -> dbUser = $dbUser;
		$this -> dbPass = $dbPass;

	}

	public function open() {
		$this -> conn = pg_connect("host=$this->dbHost port=$this->dbPort dbname=$this->dbName user=$this->dbUser password=$this->dbPass");

		if (!$this -> conn)
			throw new Exception("DB Connection Exception " . pg_last_error($this -> conn));
	}

	public function close() {
		pg_close($this -> conn);
	}


    public function SRS_PilotCount($pilot_id){

        $query = "select name from ".SrsAggregateDB::BOUNDARY_TABLE_NAME." where osm_id = ".$pilot_id."; ";
        $result = pg_query($this -> conn, $query);

        if (!$result)
            throw new Exception("Error Getting last aggregate data updated: " . pg_last_error($this -> conn));

        $row = pg_fetch_array($result);

        $r = false;
        if ($row) {
            $r = new stdClass;
            $r -> name = $row['name'];
        }


        $query = "select count(*) as c from ".SrsAggregateDB::CURRENT_TABLE_NAME." where st_intersects(the_geom, (select st_collect(way) from ".SrsAggregateDB::BOUNDARY_TABLE_NAME." where osm_id = ".$pilot_id." )); ";
        $result = pg_query($this -> conn, $query);

        if (!$result)
            throw new Exception("Error Getting last aggregate data updated: " . pg_last_error($this -> conn));

        $row = pg_fetch_array($result);

        if ($row && $r) {
            $r -> count = $row['c'];
        }

        return $r;
    }

}
