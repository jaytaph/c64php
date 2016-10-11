<?php

namespace Mos6510\Io;

interface IoInterface {

    /**
     * Write to specific $x $y with pixel color (0-15)
     *
     * Size of monitor is 403 * 284 (border + 320x200 screen)
     *
     * @param $x
     * @param $y
     * @param $p
     */
    public function writeMonitor($x, $y, $p);

    /**
     * Sets the current raster line that we are currently adjusting. Meaning we can safely write the
     * previous rasterline.
     *
     * @param $y
     */
    public function setMonitorRasterLine($y);

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
}
