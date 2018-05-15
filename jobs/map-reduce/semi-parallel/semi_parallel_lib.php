<?php
/**
 * Created by PhpStorm.
 * User: Saverio
 * Date: 22/06/2015
 * Time: 12.48
 */
// ******* semi parallel extensions ********//

include_once "Process.php";

define ('WORKERS', _getenv('WORKERS', 1));
//define ('WORKERS', 8);
define ('SUB_AGGREGATION_ROUTINE_CMD', 'sub_aggregation.php');
define ('SUB_MAPPING_ROUTINE_CMD', 'sub_mapping.php');
define ('OUTPUT_FILE', '_sub_');
define ('ERROR_FILE', '_sub_error_');
define ('LOG_EXTENSION', '.log');
define ('PID_FILE', 'sub_routine_pids.log');
define ('PHP_INTERPRETER_CMD', 'php');
define ('MERGE_CHECK_PERIOD', 500000);

function splitEntries($list, $chunk){
    $listlen = count( $list );
    $partlen = floor( $listlen / $chunk );
    $partrem = $listlen % $chunk;
    $partition = array();
    $mark = 0;
    for ($px = 0; $px < $chunk; $px++) {
        $incr = ($px < $partrem) ? $partlen + 1 : $partlen;
        $partition[$px] = array_slice( $list, $mark, $incr );
        $mark += $incr;
    }
    return $partition;
}



function start_parallel_execution($cmd, $data, $workersCount = WORKERS, $workersOffset = 1) {

    printDebugln("Using up to {$workersCount} sub processes");

    if(count($data) < 1){
        return FALSE;
    }

    $sets = splitEntries($data, $workersCount);
    printDebugLn("Sets count: ".count($sets));
    print_r($sets);
    flush();

    $processes = array();
    for($i = $workersOffset; $i < $workersCount; $i++){
        if(count($sets[$i]) > 0){
            $processes[] = new Process(PHP_INTERPRETER_CMD, $cmd, $sets[$i]);
            $processes[$i]->start();
        }
    }

    return $processes;
}

function merge_parallel_execution($processes) {
    do{
        $flag = false;

        /* @var $p Process */
        foreach($processes as $p){
            $isRunning = $p->isRunning();
            $flag = $flag || $isRunning;

            if(!$isRunning && !$p->isStopped()) {
                $processPid = $p->getPid();
                $exitCode = $p->stop();

                if ($exitCode != 0) {
                    printDebugln("Sub process {$processPid} exited with code {$exitCode}!");
                    throw new \RuntimeException("ERROR: Process {$processPid} exit with code {$exitCode}");
                }
            }
        }
        echo "still running...\n";
        usleep(MERGE_CHECK_PERIOD);
    }while($flag);

    printDebugln("Merged!");
}
