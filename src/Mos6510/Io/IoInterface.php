<?php

namespace Mos6510\Io;

interface IoInterface {

    /**
     * Write to specific $x $y with pixel color (0-15)
     *
     * Size of monitor is 402 * 292 (border + 320x200 screen)
     *
     * @param $x
     * @param $y
     * @param $p
     */
    public function writeMonitor($x, $y, $p);

    /**
     * Writes a complete buffer (403x284) string
     *
     * @param $buf
     */
    public function writeMonitorBuffer($buf);

    /**
     * Returns 2 bytes rows,cols on keyboard matrix
     */
    public function readKeyboard();

    public function enableOutput($output);
}
