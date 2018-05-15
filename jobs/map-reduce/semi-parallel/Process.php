<?php

/**
 * Created by PhpStorm.
 * User: SAVERI0
 * Date: 11/03/2016
 * Time: 12:01
 */

class Process{
    private $resource;
    private $command;
    private $defaultInterpreter;
    private $stopped = false;
    private $args;

    public function __construct($interpreter=false , $cmd=false, $args = false){
        $this->defaultInterpreter = $interpreter;
        $this->command = $cmd;
        $this->args = $args;
    }

    private function runCom(){

        $descriptorSpec = array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => STDOUT,  // stdout is a pipe that the child will write to
            2 => STDERR // stderr is a file to write to
        );

        $cwd = getcwd(); // the local directory is the local path for new processes

        $env = array('args' => serialize($this->args)); // set args
        $env = array_merge($env, $_ENV);

        $command = sprintf("%s %s", $this->defaultInterpreter, $this->command);

        //just for debug
        if($this->args){
            $command_args = $command." ".escapeshellarg(serialize($this->args));
            printDebugln("starting command: $command_args\n");
        }

        $this->resource = proc_open($command, $descriptorSpec, $pipes, $cwd, $env);

        if($this->resource){
            fclose($pipes[0]);
            return true;
        }
        throw new \RuntimeException('ERROR: Unable to launch new process!');

    }

    private function getProcessProperty($property, $throwIfInvalid = true){
        if(is_resource($this->resource)){
            $info = proc_get_status($this->resource);

            if($info){
                return $info[$property];

            } else {
                throw new \RuntimeException('ERROR: proc_get_status() failed!');
            }

        } else if ($throwIfInvalid) {
                throw new \RuntimeException('ERROR: Invalid Process.');
        }

        return false;
    }

    public function getPid(){
        return $this->getProcessProperty('pid');
    }

    public function isRunning(){
        return $this->getProcessProperty('running', false);
    }

    public function isStopped(){
        return $this->stopped;
    }

    public function getExitCode(){
        return $this->getProcessProperty('exitcode', false);
    }

    public function start(){
        if ($this->command != '') {
            $this->runCom();
        } else return true;
    }

    public function stop(){

        if(is_resource($this->resource)) {

            $exit_code = proc_close($this->resource);
            $ecode = ($this->isRunning() ? $exit_code : $this->getExitCode());
            $this->stopped = true;

            return $ecode;

        } else {
            throw new \RuntimeException('ERROR: Invalid Process.');
        }
    }

}
