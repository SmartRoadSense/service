<?php

class SrsDB {
    private $conn;
    private $dbHost;
    private $dbName;
    private $dbUser;
    private $dbPass;
    private $dbPort;

    public function SrsDB($dbHost, $dbPort, $dbName, $dbUser, $dbPass) {
        $this->dbHost = $dbHost;
        $this->dbPort = $dbPort;
        $this->dbName = $dbName;
        $this->dbUser = $dbUser;
        $this->dbPass = $dbPass;
    }

    public function open() {
        $this->conn = pg_connect("host= " . $this->dbHost .
                                 " port=" . $this->dbPort .
                                 " dbname=" . $this->dbName .
                                 " user=" . $this->dbUser .
                                 " password=" . $this->dbPass);

        if (!$this->conn)
          throw new Exception("DB Connection Exception " . pg_last_error($this->conn));
    }

    public function close() {
        pg_close($this->conn);
    }


    public function SRS_Get_All_Current_Data($left, $bottom, $right, $top, $moduleFilter = 1) {
        $updatedIndexes = array();

        $filterCondition = "";
        if (is_numeric($moduleFilter) && $moduleFilter != 1) {
            $filterCondition = "(current.aggregate_id % $moduleFilter = 0) AND ";
        }

        $result = pg_query($this->conn, "SELECT ppe, st_asgeoJson(the_geom)
            FROM current
			  WHERE
			  $filterCondition
			  current.the_geom && ST_MakeEnvelope( $left, $bottom, $right, $top , 4326); ");

        //echo $result;
        if (!$result)
            throw new Exception("Error Getting SRS Data: " . pg_last_error($this->conn));

        $data = pg_fetch_all($result);

        return ($data);
    }


	public function SRS_Marked_Raw_Data($left, $bottom, $right, $top, $moduleFilter = 1, $mark, $all_data) {
        $updatedIndexes = array();

        $filterCondition = "";
        if (is_numeric($moduleFilter) && $moduleFilter != 1) {
            $filterCondition = "(single_data_id % $moduleFilter = 0) AND ";
        }
		
		$mark = mb_strtolower($mark);
		
		$query = "SELECT ppe, st_asgeoJson(position)
            FROM single_data LEFT JOIN track ON track.track_id = single_data.track_id 
			  WHERE $filterCondition 
			  LOWER(track.metadata::json->>'clientMark') = '$mark' AND 
			  single_data.position && ST_MakeEnvelope( $left, $bottom, $right, $top , 4326) ";
		
		if($all_data) {
			$query .= " UNION SELECT ppe, st_asgeoJson(position)
            FROM single_data_old LEFT JOIN track ON track.track_id = single_data_old.track_id 
			  WHERE $filterCondition 
			  LOWER(track.metadata::json->>'clientMark') = '$mark' AND 
			  single_data_old.position && ST_MakeEnvelope( $left, $bottom, $right, $top , 4326) ";
		}
		
		
        $result = pg_query($this->conn, "$query ;");

        //echo $result;
        if (!$result)
            throw new Exception("Error Updating SRS Data: " . pg_last_error($this->conn));

        $data = pg_fetch_all($result);

        return ($data);
    }
	
}
