<?php

use Mos6510\C64;
use Mos6510\Io\NullIo;
use Mos6510\Logging\NullLogger;

require_once "./vendor/autoload.php";

/*
 * Fires up the C64 debugger. Not completely functional
 */

// When set to true, we are debugging the functional test suite
define('DEBUG_TEST_SUITE', true);

$c64 = new C64(new NullLogger(), new NullIo());
$c64->boot();

if (DEBUG_TEST_SUITE) {
    $c64->getMemory()->loadRam("./rom/6502_functional_test.bin", 0, filesize("./rom/6502_functional_test.bin"), 0);
    // Everything is memory mapped in RAM. We do not use any IO banks (disabled vic,cia IO)
    $c64->getMemory()->write8(0x0001, 0);
    // Start running at 0x0400
    $c64->getCpu()->writePc(0x0400);
}

$debugger = new Mos6510\Debugger\Debugger($c64);
$debugger->start();
