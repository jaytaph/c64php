<?php

namespace Mos6510\Io;

class NullIo implements IoInterface {

    public function writeMonitor($x, $y, $p) {
    }

    public function readKeyboard() {
    }

    public function writeMonitorBuffer($buf) {
    }

}
