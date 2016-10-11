<?php

namespace Mos6510\Logging;

use Mos6510\Cpu;

class FileLogger implements LoggerInterface {

    protected $logfile;

    function __construct($path) {
        $this->logfile = fopen($path, "a");

        $this->log("*** Starting logfile ***\n");
    }

    function debug($str) {
        $this->log("DEBG: ". $str);
    }

    function warning($str) {
        $this->log("WARN: ". $str);
    }

    function error($str) {
        $this->log("ERR: ". $str);
    }

    protected function log($str) {
        fwrite($this->logfile, trim($str) . "\n");
    }

}
