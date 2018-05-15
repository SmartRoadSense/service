<?php

require_once 'postprocessing/postprocessing_lib.php';
require_once 'datautils/util.php';


$res = are_getting_closer(array(6, 5,4,1,2));

if($res == Direction::APPROACHING){
    printDebugln("IS GETTING CLOSER");
}else if ($res == Direction::LEAVING) {
    printDebugln("IS MOVING AWAY");
}else{
    printDebugln("WE DON'T KNOW");
}

$handle = fopen ("php://stdin","r");
$line = fgets($handle);

