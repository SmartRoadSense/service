<?php

class SrsMetaDB {
	const TABLE_NAME = "count";
	const RAW_COLUMN = "raw";
	const AGGREGATE_COLUMN = "aggregate";
	const DATE_COLUMN = "date";

	private $conn;
	private $dbHost;
	private $dbName;
	private $dbUser;
	private $dbPass;

	public function SrsMetaDB($dbHost, $dbPort, $dbName, $dbUser, $dbPass) {
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

    public function SRS_LastMeta(){

        $query = "select * from ".SrsMetaDB::TABLE_NAME." order by ".SrsMetaDB::DATE_COLUMN." desc limit 1;";
        $result = pg_query($this -> conn, $query);

        if (!$result)
            throw new Exception("Error Getting last aggregate data updated: " . pg_last_error($this -> conn));

        $row = pg_fetch_array($result);

		$r = false;
        if ($row) {
            $r = new stdClass;
            $r -> raw = $row[SrsMetaDB::RAW_COLUMN];
            $r -> aggregate = $row[SrsMetaDB::AGGREGATE_COLUMN];

        }

        return $r;
    }

}
