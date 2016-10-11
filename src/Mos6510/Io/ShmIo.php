<?php

namespace Mos6510\Io;

use Mos6510\Utils;

class ShmIo implements IoInterface {

    const SHM_KEY = 0x6303b5eb;
    protected $shm_id;

    const BUFFER_SIZE = 2 + 114452 + 2;

    public function __construct()
    {
        $this->shm_id = shmop_open(self::SHM_KEY, "c", 0644, self::BUFFER_SIZE);

        // Initial clear of the raster info (not used anymore)
        shmop_write($this->shm_id, str_repeat(chr(0), 2), 0);

        // Clear the screen of the monitor
        shmop_write($this->shm_id, str_repeat(chr(0), self::BUFFER_SIZE), 2);

        // Initial clear of the keyboard
        shmop_write($this->shm_id, str_repeat(chr(0), 2), 114454);
    }

    public function writeMonitorBuffer($buf) {
        shmop_write($this->shm_id, $buf, 2);
    }

    public function writeMonitor($x, $y, $p) {
        $offset = ($y * 403) + $x;

        if ($offset >= 114452) {
            // Can't write off the screen
            return;
        }
        shmop_write($this->shm_id, $p, 2 + $offset);
    }

    public function readKeyboard()
    {
        $buf = shmop_read($this->shm_id, 114454, 2);

        return array(ord($buf[0]), ord($buf[1]));
    }

    public function setMonitorRasterLine($y)
    {
        // Not used anymore.

        shmop_write($this->shm_id, chr($y & 0xFF), 0);
        shmop_write($this->shm_id, chr(($y >> 8) & 0xFF), 1);
    }


}
