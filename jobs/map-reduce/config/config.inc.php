<?php

// return the env variable or the default value
function _getenv($env, $default) {
  $value = getenv($env);
  return ($value? $value : $default);
}

// raw database
DEFINE("RAW_DB_HOST", _getenv("RAW_DB_HOST", "raw-db"));
DEFINE("RAW_DB_PORT", _getenv("RAW_DB_PORT", "5432"));
DEFINE("RAW_DB_NAME", _getenv("RAW_DB_NAME", "srs_raw_db"));
DEFINE("RAW_DB_USER", _getenv("RAW_DB_USER", "crowd4roads_sw"));
DEFINE("RAW_DB_PASS", _getenv("RAW_DB_PASS", "password"));

// aggregate database
DEFINE("AGG_DB_HOST", _getenv("AGG_DB_HOST", "agg-db"));
DEFINE("AGG_DB_PORT", _getenv("AGG_DB_PORT", "5432"));
DEFINE("AGG_DB_NAME", _getenv("AGG_DB_NAME", "srs_agg_db"));
DEFINE("AGG_DB_USER", _getenv("AGG_DB_USER", "crowd4roads_sw"));
DEFINE("AGG_DB_PASS", _getenv("AGG_DB_PASS", "password"));

// general options
DEFINE("SINGLE_INSTANCE", true);
DEFINE("SINGLE_INSTACE_SCRIPT_NAME", "semi_parallel_updater.php");
DEFINE("NEW_LINE", "\n");
DEFINE("SRS_UPDATE_DATA_PROJECTIONS_SIZE", 30000);
DEFINE("ROAD_ROUGHNESS_METERS", 20);
DEFINE("ROAD_ROUGHNESS_RANGE", 40);
DEFINE("TRY_TO_FIX_PROJECTIONS", true);
DEFINE("UPLOAD_TO_CARTODB", true);
DEFINE("HISTORIFY_OLD_RAW_DATA", true);

date_default_timezone_set("Europe/Rome");

if (SINGLE_INSTANCE) {
	// check with PS AUX if this script is already running (avoiding the grep "virtual" process)
	exec('ps aux | grep "php ' . SINGLE_INSTACE_SCRIPT_NAME . '" | grep -v grep', $output);
	if (count($output) > 1)
		// if there is more than one instance (this one) it means another script instance is already running, so close this one.
		exit("Another instance of this script is already running.");

}
