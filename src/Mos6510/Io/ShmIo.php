<?php

namespace Mos6510\Io;

class ShmIo implements IoInterface {

    const SHM_KEY = 0x6303b5ec;
    protected $shm_id;

    const BUFFER_SIZE = 117384 + 2 + 2;

    protected $output_enabled;

    public function __construct()
    {
        $this->shm_id = shmop_open(self::SHM_KEY, "c", 0644, self::BUFFER_SIZE);

        // Clear the screen of the monitor
        shmop_write($this->shm_id, str_repeat(chr(0), self::BUFFER_SIZE), 0);
        shmop_write($this->shm_id, $this->loadLogo("logo.png"), 0);

        // Initial clear of the keyboard
        shmop_write($this->shm_id, str_repeat(chr(0), 2), 117384);

        // Initial clear of the joysticks
        shmop_write($this->shm_id, str_repeat(chr(0), 2), 117384 + 2);
    }

    public function writeMonitorBuffer($buf) {
        if (! $this->output_enabled) {
            return;
        }

        shmop_write($this->shm_id, $buf, 0);
    }

    public function writeMonitor($x, $y, $p) {
        if (! $this->output_enabled) {
            return;
        }

        $offset = ($y * 402) + $x;

        if ($offset >= 117384-1) {
            // Can't write off the screen
            return;
        }
        shmop_write($this->shm_id, $p, 0 + $offset);
    }

    public function readKeyboard()
    {
        $buf = shmop_read($this->shm_id, 117384, 2);

        return array(ord($buf[0]), ord($buf[1]));
    }

    /**
     * @param mixed $output_enabled
     */
    public function enableOutput($output_enabled)
    {
        $this->output_enabled = $output_enabled;
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
                $buf[$off + 43 + $x] = chr($c & 0x0E);
            }
            $off += 402;
        }

        return $buf;
    }

    public function readJoystick1()
    {
        $buf = shmop_read($this->shm_id, 117384 + 2, 1);

        return ord($buf[0]);
    }

    public function readJoystick2()
    {
        $buf = shmop_read($this->shm_id, 117384 + 3, 1);

        return ord($buf[0]);

    }


}
