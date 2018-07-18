<?php

require_once 'config.php';



$srsDB = new SrsAggregateDB(DB_META_HOST, DB_META_PORT, DB_META_NAME, DB_META_USER, DB_META_PASS);
$srsDB->open();
$counts = $srsDB->SRS_PilotCount($_GET['pid']);
$srsDB->close();

echo '{"count_aggregate": ' .$counts->count. '}';

?>
