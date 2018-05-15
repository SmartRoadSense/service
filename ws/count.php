<?php

require_once 'config.php';

$srsDB = new SrsMetaDB(DB_META_HOST, DB_META_PORT, DB_META_NAME, DB_META_USER, DB_META_PASS);
$srsDB->open();
$counts = $srsDB->SRS_LastMeta();
$srsDB->close();

echo '{"count_raw": ' . ($counts->raw + 5936853) . ', ';
echo '"count_aggregate": ' . $counts->aggregate . '}';

?>
