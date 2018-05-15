<?php
class DebugCounter {

	private $cases = array(0 => 0,
        1 => 0,
        2 => 0,
        3 => 0,
        4 => 0,
        5 => 0,
        6 => 0,
        7 => 0,
        8 => 0,
        9 => 0);

    private $initTime;


    public function Start(){
        $this -> initTime = -microtime(true);
    }

    public function Stop(){
        $this -> initTime += microtime(true);
    }

	public function HitCase($index, $printPID = false) {

		$this -> cases[$index]++;
        printDebugln("CASE HIT $index", $printPID);
	}

	public function PrintHitsResume($title = "", $isDebug = true, $printPID = false){

        if($title != ""){
            if($isDebug){
                printDebugln("--- $title ---", $printPID);
            }else {
                printInfoln("--- $title ---", $printPID);
            }
        }

        foreach($this -> cases as $case => $hits){
            if($isDebug){
                printDebugln("CASE $case: $hits hits", $printPID);
            }else{
                printInfoln("CASE $case: $hits hits", $printPID);
            }
        }
    }

    public function PrintExecutionTime($isDebug = true, $printPID = false){
        if($isDebug) {
            printDebugln("Execution time: " . sprintf('%f', $this->initTime) . "s", $printPID);
        } else {
            printInfoln("Execution time: " . sprintf('%f', $this->initTime) . "s", $printPID);
        }
    }

    public function GetData(){
        return $this -> cases;
    }

    public function AddDebugCounter(DebugCounter $counter){
        $subArray =  $counter -> GetData();

        foreach ($subArray as $id=>$value) {
            $this -> cases[$id]+=$value;
        }

    }
}
?>