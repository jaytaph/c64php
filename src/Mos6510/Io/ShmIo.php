<?php

namespace Mos6510\Io;

use Mos6510\Utils;

class ShmIo implements IoInterface {

    const SHM_KEY = 0x6303b5eb;
    protected $shm_id;

    const BUFFER_SIZE = 117384 + 2;

    protected $output;

    public function __construct()
    {
        $this->shm_id = shmop_open(self::SHM_KEY, "c", 0644, self::BUFFER_SIZE);

        // Clear the screen of the monitor
//        shmop_write($this->shm_id, str_repeat(chr(0), self::BUFFER_SIZE), 2);
        shmop_write($this->shm_id, $this->loadLogo("logo.png"), 2);

        // Initial clear of the keyboard
        shmop_write($this->shm_id, str_repeat(chr(0), 2), 117384);
    }

    public function writeMonitorBuffer($buf) {
        if (! $this->output) {
            return;
        }

        shmop_write($this->shm_id, $buf, 2);
    }

    public function writeMonitor($x, $y, $p) {
        if (! $this->output) {
            return;
        }

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

    /**
     * @param mixed $output
     */
    public function enableOutput($output)
    {
        $this->output = $output;
    }


    protected function loadLogo($logo) {
        $buf = str_repeat(chr(0), self::BUFFER_SIZE);
        $img = imagecreatefrompng($logo);

        if (!$img) {
            return $buf;
        }

        $off = 402 * 43;
        for ($y=0; $y!=200; $y++) {
            for ($x=0; $x!=320; $x++) {
                $c = imagecolorat($img, $x, $y);
                $buf[$off + 43 + $x] = ($c & 0x0F);
            }
            $off += 402;
        }

        return $buf;
    }


}
