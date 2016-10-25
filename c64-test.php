<?php

use Mos6510\C64;
use Mos6510\Disassembler;
use Mos6510\Io\NullIo;
use Mos6510\Logging\NullLogger;

require_once "./vendor/autoload.php";

/*
 * We are using the excellent functional tests from https://github.com/Klaus2m5/6502_65C02_functional_tests
 *
 * On failing tests, the emulator will create deadlocks, which are detected. On success, it will deadlock on a
 * JMP instruction which is detected by this tester (around address $3399).
 *
 * Note that the last 2 tests (ADC/SBC) will take a LONG time, as they will check every combination of adding
 * and subtracting 0x00-0xFF and 0x00-0x99 in decimal mode.
 */

define('BREAKPOINT_ADDRESS', 0);        // Address to set a breakpoint on (0 when not needed)
define('SCREEN_UPDATE', 10000);        // Update screen after these amount of CPU cycles


// Create out CPU
$c64 = new C64(new NullLogger(), new NullIo());
$c64->boot(false);

// Create disassembler
$disassembler = new Disassembler($c64);

// Load functional test inside RAM
$c64->getMemory()->loadRam("./rom/6502_functional_test.bin", 0, filesize("./rom/6502_functional_test.bin"), 0);
// Everything is memory mapped in RAM. We do not use any IO banks (disabled vic,cia IO)
$c64->getMemory()->write8(0x0001, 0);
// The tests starts at 0x0400
$c64->getCpu()->writePc(0x0400);


// When true, we wait for enter to step
$break = false;

// Test counters
$current_test = 1;
$current_test_pc = $c64->getCpu()->readPc();


$handle = fopen("php://stdin", "r");
while (true) {

    // Break on breakpoint, or somewhere around. Takes care of not landing direct on the given opcode address but on operands
    if ($c64->getCpu()->readPc() >= BREAKPOINT_ADDRESS &&  $c64->getCpu()->readPc() <= BREAKPOINT_ADDRESS + 3) {
        $break = true;
    }

    // Read TestID memory location, and update our test counters
    if ($c64->getMemory()->read8(0x0200) != $current_test) {
        $current_test_pc = $c64->getCpu()->readPc();
        $current_test = $c64->getMemory()->read8(0x0200);
    }

    // Display status if on break or at certain clock cycles
    if ($break || ($c64->getCpu()->getTickCount() % SCREEN_UPDATE < 3)) {
        dumpData();
    }

    // Wait for user enter if needed
    if ($break) {
        fgets($handle);
    }

    // Execute a single cycle
    $pc1 = $c64->getCpu()->readPc();
    $c64->cycle();
    $pc2 = $c64->getCpu()->readPc();


    // Detect if deadlocked (when we jump/branch to the same address)
    if ($pc1 == $pc2) {
        $c64->getCpu()->writePc($pc1 - 10);
        dumpData();

        // If the deadlock operation is a JMP, the tests have passed.
        if ($c64->getMemory()->read8($pc1) == 0x4C && $pc1 > 0x3000) {
            printf("Deadlock at %04X. It seems like the tests have passed successfully.\n", $pc1);
        } else {
            printf("Deadlock detected at %04X\n", $pc1);
        }
        exit();
    }

};


function dumpData() {
    global $disassembler;
    global $current_test;
    global $current_test_pc;

    print "\033[J\033[H";
    print $disassembler->getDebugSnapshot();
    print "--------------------------------------------------------------------------------\n";
    print $disassembler->getMemoryDump(0x0000, 10);
    print "--------------------------------------------------------------------------------\n";
    print $disassembler->getMemoryDump(0x0200, 10);
    print "--------------------------------------------------------------------------------\n";
    printf("Test: %02X (%04X)\n", $current_test, $current_test_pc);
}
