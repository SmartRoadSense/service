<?php

class TimeCounter {

    public static $STEP_CAST = "CAST";
    public static $STEP_PROJECTION = "PROJECTION";
    public static $STEP_CLEAN = "CLEAN";
    public static $STEP_MAP_MATCHING = "MAP-MATCHING";
    public static $STEP_LOCAL_AGGREGATION = "LOCAL-AGGREGATION";
    public static $STEP_REMOTE_UPLOAD = "REMOTE-UPLOAD";
    public static $STEP_MOVING_OLD_DATA = "HISTORIFIYNG";

    private $initTime;
    private $prefix = "##PROFILE## ";

    public function Start(){
        $this -> initTime = -microtime(true);
    }

    public function Stop(){
        $this -> initTime += microtime(true);
    }

    public function PrintTime($stepName = "", $printPID = false){
        $this->Stop();
        $this->PrintExecutionTime($stepName, $printPID);
        $this->Start();
    }


    public function PrintExecutionTime($stepName = "", $printPID = false){
        printInfoln($this->prefix."$stepName: \t\t\t [".date(DATE_RFC2822)."] \t\t".sprintf('%f', $this->initTime) . "s", $printPID);
        //printInfoln($this->prefix."$stepName: " . sprintf('%f', $this->initTime) . "s", $printPID);
    }

    public function PrintHowMany($processed_row_count, $total_raw_count, $printPID = false){
        printInfoln($this->prefix."Processing $processed_row_count on $total_raw_count records", $printPID);
    }

}
?>