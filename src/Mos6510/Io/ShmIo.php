<?php

namespace Mos6510\Io;

use Mos6510\Utils;

class ShmIo implements IoInterface {

    const SHM_KEY = 0x6303b5eb;
    protected $shm_id;

    const BUFFER_SIZE = 117384 + 2;

    public function __construct()
    {
        $this->shm_id = shmop_open(self::SHM_KEY, "c", 0644, self::BUFFER_SIZE);

        // Clear the screen of the monitor
        shmop_write($this->shm_id, str_repeat(chr(0), self::BUFFER_SIZE), 2);

        // Initial clear of the keyboard
        shmop_write($this->shm_id, str_repeat(chr(0), 2), 117384);
    }

    public function writeMonitorBuffer($buf) {
        shmop_write($this->shm_id, $buf, 2);
    }

    public function writeMonitor($x, $y, $p) {
        $offset = ($y * 402) + $x;

        if ($offset >= 117384) {
            // Can't write off the screen
            return;
        }
        shmop_write($this->shm_id, $p, 2 + $offset);
    }

    public function readKeyboard()
    {
        $buf = shmop_read($this->shm_id, 117384, 2);

        return array(ord($buf[0]), ord($buf[1]));
    }

}
