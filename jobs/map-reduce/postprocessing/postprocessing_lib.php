<?php

define('MIN_LABELS_COUNT', 8);
define('MIN_LINK_ROAD_LABELS_COUNT', 3);
define('MAX_RANGE_AROUND_INTERSECTION', 55); //was 42*3.6 = 151.2 km/h now is 55*3.6 = 198 km/h

define('MAX_DISTANCE_TO_INTERSECTION', 55); //was 42*3.6 = 151.2 km/h now is 55*3.6 = 198 km/h
define('MAX_INTRA_CROSS_DISTANCE', 10);
define('MIN_TUNNEL_LENGTH',55); //was 42*3.6 = 151.2 km/h now is 55*3.6 = 198 km/h




function setSubArray(& $arr, $index, $count, $label){

    $first = $index - $count;
    for($i = $first; $i < $index; $i++){
        $arr[$i] -> lineId = $label;
    }

}

function get_current($arr, $index, $count){

    $els = array();

    $first = $index - $count;
    for($i = $first; $i < $index; $i++){
        $els[] = $arr[$i];
    }

    return $els;
}

function splitSubArray(& $arr, $index, $count, $fstLabel, $sndLabel){

    $first = $index - $count;
    $second = $first + intval($count/2);
    for($i = $first; $i < $second; $i++){
        $arr[$i] -> lineId = $fstLabel;
    }
    for($i = $second; $i < $index; $i++){
        $arr[$i] -> lineId = $sndLabel;
    }

    return $index - $second;
}

function check_cached_var($variable, $expectedValue){
    return ($variable !== NULL ? $variable == $expectedValue : false);
}

/**
 * @param $srsDB
 * @param $ids
 * @param $changed
 * @param $mainCounter
 * @return mixed
 */
function fixOSMIds($srsDB, & $ids, & $changed, & $mainCounter, $printPID = false) {

    $index = 0; // where the analysis is
    $count = 0; // how many new labels
    $last_label = -1;
    $second_last_label = -1;

    $changed = false;

    $dbgCase = new DebugCounter();

    for(; $index < count($ids); $index++ ){

        $el = $ids[$index];

        if($el->lineId == NULL){
            printInfoln("Stopping osm label fixing algorithm since last element has null label. INDEX:".$index." ID:".$el -> singleDataId, $printPID);
            //echo "index: $index".PHP_EOL;
            break 1;
        }

        //printDebug($el -> lineId." \t\t "); //TODO remove me
        if($last_label == -1) { $last_label = $el -> lineId; }

        if($el -> lineId == $last_label){
            $count ++;
        } else {
            $countAdd = 0;
            if(get_second_last_label($ids, $index, $count) != -1){
                $are_AB_close =
                $is_intersection_close_enough_toAB =
                $is_an_ABC_intersection =
                $is_intersection_close_enough_toBC = NULL;


                if($count < MIN_LABELS_COUNT                                                   //current.len < n
                    && !($are_AB_close = are_AB_close($srsDB, $ids, $index, $count, MIN_TUNNEL_LENGTH))           //current far from last
                    && !are_BC_close($srsDB, $ids, $index, $count, MIN_TUNNEL_LENGTH)){         //next far from current
                    //CASE 8: discard
                    discard($srsDB, $ids, $index, $count, $changed);
                    $dbgCase ->HitCase(8, $printPID);
                    label_current($srsDB, $ids, $index, $count, 8);

                } else if(check_cached_var($are_AB_close, false) || !($are_AB_close = are_AB_close($srsDB, $ids, $index, $count, MIN_TUNNEL_LENGTH))){      //current far from last
                    //CASE 6: do nothing
                    $dbgCase ->HitCase(6, $printPID);
                    label_current($srsDB, $ids, $index, $count, 6);

                } else if($count < MIN_LINK_ROAD_LABELS_COUNT){                                 //current very very short
                    //CASE 9: split
                    split_between_last_next($ids, $index, $count, $changed, $countAdd, $el -> lineId);
                    $dbgCase ->HitCase(9, $printPID);
                    label_current($srsDB, $ids, $index, $count, 9);

                }else if(last_equals_next($el -> lineId, $ids, $index, $count)                  //next == last
                    && $count < MIN_LABELS_COUNT                                                //current.len < n
                    && (check_cached_var($is_intersection_close_enough_toAB,true) || $is_intersection_close_enough_toAB = is_intersection_close_enough_toAB($srsDB, $ids, $index, $count))){        //current reachable from last (takes also into account crossroad position)
                    //CASE 1: move to last
                    turn_to_last($ids, $index, $count, $changed, $last_label);
                    $dbgCase ->HitCase(1, $printPID);
                    label_current($srsDB, $ids, $index, $count, 1);

                } else if(!last_equals_next($el -> lineId, $ids, $index, $count)                //[NOT] next == last
                    && $count < MIN_LABELS_COUNT                                                //current.len < n
                    && (check_cached_var($is_intersection_close_enough_toAB,true) || $is_intersection_close_enough_toAB = is_intersection_close_enough_toAB($srsDB, $ids, $index, $count))          //current reachable from last (takes also into account crossroad position)
                    && (check_cached_var($is_an_ABC_intersection, true) || $is_an_ABC_intersection = is_an_ABC_intersection($srsDB, $ids, $index, $count))){                   //last, current and next cross in the same point
                    //CASE 2: split
                    split_between_last_next($ids, $index, $count, $changed, $countAdd, $el -> lineId);
                    $dbgCase ->HitCase(2, $printPID);
                    label_current($srsDB, $ids, $index, $count, 2);

                } else if(!last_equals_next($el -> lineId, $ids, $index, $count)                //[NOT] next == last
                    && $count < MIN_LABELS_COUNT                                                //current.len < n
                    && (check_cached_var($is_intersection_close_enough_toAB,true) || $is_intersection_close_enough_toAB = is_intersection_close_enough_toAB($srsDB, $ids, $index, $count))          //current reachable from last (takes also into account crossroad position)
                    && (check_cached_var($is_an_ABC_intersection, false) || !($is_an_ABC_intersection = is_an_ABC_intersection($srsDB, $ids, $index, $count)))                    //[NOT] last, current and next cross in the same point
                    && (check_cached_var($is_intersection_close_enough_toBC, true) || $is_intersection_close_enough_toBC = is_intersection_close_enough_toBC($srsDB, $ids, $index, $count))){        //next reachable from current (takes also into account crossroad position)
                    //CASE 3: do nothing
                    $dbgCase ->HitCase(3, $printPID);
                    label_current($srsDB, $ids, $index, $count, 3);

                } else if($count >= MIN_LABELS_COUNT                                            //[NOT] current.len < n
                    && (check_cached_var($is_intersection_close_enough_toAB,true) || $is_intersection_close_enough_toAB = is_intersection_close_enough_toAB($srsDB, $ids, $index, $count))          //current reachable from last (takes also into account crossroad position)
                    && (check_cached_var($is_an_ABC_intersection, true) || $is_an_ABC_intersection = is_an_ABC_intersection($srsDB, $ids, $index, $count))                     //last, current and next cross in the same point
                    && (check_cached_var($is_intersection_close_enough_toBC, false) || !($is_intersection_close_enough_toBC = is_intersection_close_enough_toBC($srsDB, $ids, $index, $count)))){       //[NOT] next reachable from current (takes also into account crossroad position)
                    //CASE 4: move to next
                    turn_to_next($ids, $index, $count, $changed, $last_label);
                    $dbgCase ->HitCase(4, $printPID);
                    label_current($srsDB, $ids, $index, $count, 4);

                } else if($count >= MIN_LABELS_COUNT                                            //[NOT] current.len < n
                    && (check_cached_var($is_intersection_close_enough_toAB, true) || $is_intersection_close_enough_toAB = is_intersection_close_enough_toAB($srsDB, $ids, $index, $count))          //current reachable from last (takes also into account crossroad position)
                    && (check_cached_var($is_intersection_close_enough_toBC, true) || $is_intersection_close_enough_toBC = is_intersection_close_enough_toBC($srsDB, $ids, $index, $count))){        //next reachable from current (takes also into account crossroad position)
                    //CASE 5: do nothing
                    $dbgCase ->HitCase(5, $printPID);
                    label_current($srsDB, $ids, $index, $count, 5);

                } else if($count >= MIN_LABELS_COUNT                                            //[NOT] current.len < n
                    && (check_cached_var($is_intersection_close_enough_toAB, false) || !($is_intersection_close_enough_toAB = is_intersection_close_enough_toAB($srsDB, $ids, $index, $count)))         //[NOT] current reachable from last (takes also into account crossroad position)
                    && (check_cached_var($is_an_ABC_intersection, true) || $is_an_ABC_intersection = is_an_ABC_intersection($srsDB, $ids, $index, $count))                     //last, current and next cross in the same point
                    && (check_cached_var($is_intersection_close_enough_toBC, true) || $is_intersection_close_enough_toBC = is_intersection_close_enough_toBC($srsDB, $ids, $index, $count))){        //next reachable from current (takes also into account crossroad position)
                    //CASE 7: move to last
                    turn_to_last($ids, $index, $count, $changed, $last_label);
                    $dbgCase ->HitCase(7, $printPID);
                    label_current($srsDB, $ids, $index, $count, 7);
                } else {
                    $dbgCase ->HitCase(0, $printPID);
                }

            }

            $last_label = $el -> lineId;
            $count = 1 + $countAdd;
        }

        //printDebugln($el -> lineId); //TODO remove me
    }

    //set Projection as fixed
    $srsDB->SRS_Set_As_Fixed( ($index < count($ids)) ?
        array_slice($ids, 0,$index, true):
        $ids);


    $dbgCase ->PrintHitsResume("", false, $printPID);
    $mainCounter -> AddDebugCounter($dbgCase);

    return $ids;

}


function last_equals_next($next, $ids, $index, $count){
    return $next == get_second_last_label($ids, $index, $count);
}


function turn_to_last(&$ids, $index, $count, &$changed, &$last_label){
    //Change last values to second_last_label
    setSubArray($ids, $index, $count, get_second_last_label($ids, $index, $count));
    printDebugln("turn_to_last: at $index change last $count values to ".get_second_last_label($ids, $index, $count));
    $changed = true;

    $last_label = get_second_last_label($ids, $index, $count); //?
}

function discard($srsDB, &$ids, $index, $count, &$changed){

    $curr = get_current($ids, $index, $count);
    $currIds = extract_array_of_ids($curr);
    printDebugln("discarding: ". join(",",$currIds));

    $srsDB -> SRS_Do_Not_Evaluate($currIds);

    $changed = true;
    //$last_label = get_second_last_label($ids, $index, $count); //?
}

function turn_to_next(&$ids, $index, $count, &$changed, &$last_label){
    //Change last values to second_last_label
    setSubArray($ids, $index, $count, $ids[$index] -> lineId);
    printDebugln("turn_to_next: at $index change last $count values to ".$ids[$index] -> lineId);
    $changed = true;

    $last_label = get_second_last_label($ids, $index, $count); //?
}

function split_between_last_next(&$ids, $index, $count, &$changed, &$countAdd, $newlabel){
    $countAdd = splitSubArray($ids, $index, $count, get_second_last_label($ids, $index, $count), $newlabel);
    printDebugln("at $index change last $count values needs to be split between side lines");
    $changed = true;
}

function get_second_last_label($ids, $index, $count){
    $pos = $index - $count - 1;
    $ret = ($pos >= 0)? $ids[$pos] -> lineId: -1;
    return $ret;
}

function get_second_last_point($ids, $index, $count){
    $pos = $index - $count - 1;
    $ret = ($pos >= 0)? $ids[$pos] -> position : -1;
    return $ret;
}

function get_last_label($ids, $index, $count){
    $pos = $index - 1;
    $ret = $ids[$pos] -> lineId;
    return $ret;
}

function get_last_point($ids, $index, $count){
    $pos = $index - 1;
    $ret = $ids[$pos] -> position;
    return $ret;
}

function get_last_pointId($ids, $index, $count){
    $ret = array();
    $pos = $index - 1;
    $ret[] = $ids[$pos] -> singleDataId;
    return $ret;
}

function get_this_label($ids, $index, $count){
    $pos = $index;
    $ret = $ids[$pos] -> lineId;
    return $ret;
}

function get_this_point($ids, $index, $count){
    $pos = $index;
    $ret = $ids[$pos] -> position;
    return $ret;
}

function fix_projections($srsDB, $tracks = array(), $printPID = false){

    $dbgMainCase = new DebugCounter();
    $dbgMainCase ->Start();

    if(!$tracks || count($tracks) < 1) {
        $tracks = $srsDB->SRS_Tracks();
        //print_r($tracks);
    }

    foreach($tracks as $val){

        printDebugln("Track: $val");
        $els = $srsDB -> SRS_Track_Vals($val);

        $changed = false;
        fixOSMIds($srsDB, $els, $changed, $dbgMainCase, $printPID);

        if($changed){
            //echo "############# CHANGED #########";
            $srsDB -> SRS_Update_Fixed_Projections($els);
        }
    }
    $dbgMainCase ->Stop();

    $dbgMainCase ->PrintHitsResume("Hits Resume", $printPID);
    $dbgMainCase ->PrintExecutionTime(false, $printPID);

    return $dbgMainCase;
}

function is_link_road($srsDB, $ids, $index, $count) {
    $aRoad = get_second_last_label($ids, $index, $count);
    $bRoad = $ids[$index-1] -> lineId;
    $cRoad = $ids[$index] -> lineId;

    $bRoad_intersections = $srsDB -> SRS_Road_Intersections($bRoad);
    $aInters = NULL;
    $cInters = NULL;

    if(is_road_linked($bRoad_intersections, $aRoad, $cRoad, $aInters, $cInters)){
        $very_first_a_point_index = $index - $count - 2; //check
        printDebugln("very_first_a_point_index: $very_first_a_point_index");

        if($very_first_a_point_index > -1
            && is_approcching($srsDB, $aInters, extract_array_of_points(array($ids[$very_first_a_point_index], $ids[$very_first_a_point_index + 1])))){

            $bRoad_records = array_slice($ids, $index - $count, $count);
            $bRoad_points = extract_array_of_points($bRoad_records);

            if(is_leaving($srsDB, $aInters, $bRoad_points)
                && is_approcching($srsDB, $cInters, $bRoad_points)){

                if(count($ids) >= $index+1
                    && is_leaving($srsDB, $cInters, extract_array_of_points(array($ids[$index],$ids[$index+1])))){
                    return true; // this seems a little difficult to achieve!
                }
            }
        }
    }

    return false;

}

function are_roads_consecutives($srsDB, $ids, $index, $count) {

    $aRoad = get_second_last_label($ids, $index, $count);
    $bRoad = $ids[$index-1] -> lineId;

    $bRoad_intersections = $srsDB -> SRS_Road_Intersections($bRoad);
    $aInters = NULL;

    /*if(are_points_in_range($srsDB, $aInters, array(get_second_last_point($ids, $index, $count), $ids[$index - 1] -> position))){
        return true; //in this case roads are not consecutieves but there is some other issue.... ?????
    }*/

    if(are_road_linked($bRoad_intersections, $aRoad, $aInters)){
        $very_first_a_point_index = $index - $count - 2; //check
        printDebugln("very_first_a_point_index: $very_first_a_point_index");

        if($very_first_a_point_index > -1
            && is_approcching($srsDB, $aInters, extract_array_of_points(array($ids[$very_first_a_point_index], $ids[$very_first_a_point_index + 1])))){

            $bRoad_records = array_slice($ids, $index - $count, $count);
            $bRoad_points = extract_array_of_points($bRoad_records);

            if(is_leaving($srsDB, $aInters, $bRoad_points)) {
                return true; // this seems a little difficult to achieve!
            }
        }
    }

    return false;

}

function are_points_in_range($srsDB, $refPoint, $points){
    return $srsDB -> SRS_Points_In_Range($refPoint, $points, MAX_RANGE_AROUND_INTERSECTION);
}

function is_intersection_close_enough_toAB($srsDB, $ids, $index, $count, $range = MAX_DISTANCE_TO_INTERSECTION){

    $points = array();

    $points[] = get_second_last_point($ids, $index, $count);
    $aRoad = get_second_last_label($ids, $index, $count);

    $points[] = $ids[$index - $count] -> position;
    $bRoad = $ids[$index - $count] -> lineId;

    //echo "AB $aRoad | $bRoad (".$ids[$index-$count]->singleDataId.") index: $index ". PHP_EOL;
    return $srsDB -> SRS_Intersection_Close_Enough($aRoad, $bRoad, $points, $range);
}

function is_intersection_close_enough_toBC($srsDB, $ids, $index, $count, $range = MAX_DISTANCE_TO_INTERSECTION){

    $points = array();

    $points[] = $ids[$index - 1] -> position; //TODO: check index
    $bRoad = $ids[$index - 1] -> lineId;

    $points[] = $ids[$index] -> position;
    $cRoad = $ids[$index] -> lineId;
    //echo "BC $bRoad (".$ids[$index - 1]->singleDataId.") | $cRoad index: $index ". PHP_EOL;
    return $srsDB -> SRS_Intersection_Close_Enough($bRoad, $cRoad, $points, $range);
}

function are_AB_close($srsDB, $ids, $index, $count, $range = MAX_RANGE_AROUND_INTERSECTION){

    $aPoint = get_second_last_point($ids, $index, $count);
    $bPoint = $ids[$index - $count] -> position;

    return $srsDB -> SRS_Points_Close($aPoint, $bPoint, $range);
}

/**
 * @param SrsRawDB $srsDB
 * @param $ids
 * @param $index
 * @param $count
 * @param int $range
 * @return mixed
 */
function are_BC_close($srsDB, $ids, $index, $count, $range = MAX_RANGE_AROUND_INTERSECTION){

    $bPoint = $ids[$index - 1] -> position;
    $cPoint = $ids[$index] -> position;

    return $srsDB -> SRS_Points_Close($bPoint, $cPoint, $range);
}

function extract_array_of_points($arr){
    $points = array();

    foreach($arr as $a){
        $points[] = $a -> position;
    }

    return $points;
}

function extract_array_of_ids($arr){
    $ids = array();

    foreach($arr as $a){
        $ids[] = $a -> singleDataId;
    }

    return $ids;
}


function is_road_linked($intersections, $aRoad, $cRoad, & $aIntersection, & $cIntersection){

    $aFlag = false;
    $cFlag = false;

    foreach($intersections as $i){
        if($i -> lineId == $aRoad){
            $aIntersection = $i -> intersectionPoint;
            $aFlag = true;
        } else if ($i -> lineId == $cRoad){
            $cIntersection = $i -> intersectionPoint;
            $cFlag = true;
        }
    }

    return $aFlag && $cFlag;

}

function are_road_linked($intersections, $aRoad, & $aIntersection){


    foreach($intersections as $i){
        if($i -> lineId == $aRoad){
            $aIntersection = $i -> intersectionPoint;
            return true;
        }
    }

    return false;
}


function is_an_ABC_intersection($srsDB, $ids, $index, $count) {
    $aRoad = get_second_last_label($ids, $index, $count);
    $bRoad = $ids[$index-1] -> lineId;
    $cRoad = $ids[$index] -> lineId;

    if($aRoad == 36855184 || $bRoad == 36855184 || $cRoad == 36855184){
        printDebugln("testing is_an_ABC_intersection $aRoad | $bRoad | $cRoad (id:".$ids[$index]->singleDataId.")");
    }

    $last_a_point_index = $index - $count - 1;
    $last_aRoad_point = $ids[$last_a_point_index] -> position;

    //echo "ABC $aRoad | $bRoad (".$ids[$index - 1]->singleDataId.") | $cRoad index: $index ". PHP_EOL;

    return $srsDB -> SRS_ABC_Intersection_Exists($aRoad, $bRoad, $cRoad, $last_aRoad_point, MAX_INTRA_CROSS_DISTANCE, MAX_DISTANCE_TO_INTERSECTION);

}

function label_current($srsDB, $ids, $index, $count, $label){

    $pts = get_last_pointId($ids, $index, $count);

    //print_r($pts);

    return $srsDB -> SRS_Debug_Label($pts, $label);
}

abstract class Direction
{
    const APPROACHING = 1;
    const LEAVING = -1;
    const UNKNOW = 0;
}

function is_approcching($srsDb, $refPoint, $positions){
    $distances = $srsDb -> SRS_Distance_From_Point($positions, $refPoint);

    return (are_getting_closer($distances) == Direction::APPROACHING);
}

function is_leaving($srsDb, $refPoint, $positions){
    $distances = $srsDb -> SRS_Distance_From_Point($positions, $refPoint);

    return (are_getting_closer($distances) == Direction::LEAVING);
}

function are_getting_closer($points){
    $challenges = count($points) - 1;
    $anomaly_threshold = intval($challenges*2/10); //from 0 to 4 points no anomaly are allowed.
    $anomaly_count = 0;

    for($i = 1; $i < count($points); $i++){
        if($points[$i] >= $points[$i-1]){
            $anomaly_count++;
        }
    }

    printDebugln("Anomalies count: $anomaly_count on $challenges challenges.");

    if($anomaly_count > $anomaly_threshold){
        if(($challenges - $anomaly_count) > $anomaly_threshold){
            return Direction::UNKNOW;
        } else {
            return Direction::LEAVING;
        }
    }

    return Direction::APPROACHING;

}