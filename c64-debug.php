<?php

use Mos6510\C64;
use Mos6510\Cpu;
use Mos6510\Disassembler;
use Mos6510\Io\ShmIo;
use Mos6510\Logging\FileLogger;


// Simple (yet complex) commandline debugger. Basically for quick debugging for my own
// needs. You probably want to use the c64-debugger.php.

// Breakpoint settings you can adjust for your own needs
$break = false;                         // Break before the next cycle
$break_at_rts = false;                  // Automatically break at a given RTS
$breakpoints = array();                 // List of memory breakpoints


// When a breakpoint has been triggered: the following can be done:
//
//   Press S to skip code when it's a subroutine (skips a complete JSR)
//   Press R to run until the next breakpoint
//   Press O to run until the first RTS


require_once "./vendor/autoload.php";

date_default_timezone_set("Europe/Amsterdam");

$c64 = new C64(new FileLogger("output.log"), new ShmIo());
$c64->boot(true);

if ($argc == 2 && file_exists($argv[1]) && substr($argv[1], -4, 4) == ".prg") {
    $c64->getMemory()->loadPrg($argv[1]);

    $c64->getMemory()->write8(0xC6, 4);           // Write RUN
    $c64->getMemory()->write8(0x277, 82);
    $c64->getMemory()->write8(0x278, 85);
    $c64->getMemory()->write8(0x279, 78);
    $c64->getMemory()->write8(0x27A, 13);
}

$disassembler = new Disassembler($c64);

$handle = fopen("php://stdin", "r");
system("stty -icanon -echo");           // Allow reading of single char (or escape sequence)

// Other variables
$break_rts_stack = 0;                   // Stacksize when the RTS should occur (so we only break at the correct RTS)

while (true) {
    $pc = $c64->getCpu()->readPc();
    $current_opcode = $c64->getMemory()->read8($pc);

    // Display screen on certain amount of ticks
    if ($c64->getCpu()->getTickCount() % 10000 <= 3) {
        print "\033[J\033[H";
        print $disassembler->getDebugSnapshot();

        // This allows you to add memory dumps of certain locations if needed
        print $disassembler->getMemoryDump(0x0000, 16, true, true);
        print $disassembler->getMemoryDump(0x0800, 16, true, true);
        print "\n";
    }

    // Trigger on breakpoint
    if (in_array($c64->getCpu()->readPc(), $breakpoints)) {
        $break = true;
    }

    if ($break && $c64->getCpu()->getIrqStatus() == Cpu::IRQ_OUTSIDE) {
        print "\033[J\033[H";
        print $disassembler->getDebugSnapshot();

        // This allows you to add memory dumps of certain locations if needed
        print $disassembler->getMemoryDump(0x0000, 16, true, true);
        print $disassembler->getMemoryDump(0x0800, 16, true, true);
        print "\n";

        // Read either single char or escape code
        $escape_code = false;
        $c = ord(fgetc($handle));
        if ($c == 27) {
            $c = ord(fgetc($handle));   // Ignore second escape char
            $c = ord(fgetc($handle));
            $escape_code = true;
        }

        if (! $escape_code && $c == ord('s') && in_array($current_opcode, array(0x20))) {
            $break_at_rts = true;
            $break_rts_stack = $c64->getCpu()->readS()-2;
            $break = false;
        }

        if (! $escape_code && $c == ord('r')) {
            $break = false;
        }

        if (! $escape_code && $c == ord('o')) {
            $break = false;
            $break_at_rts = true;
            $break_rts_stack = $c64->getCpu()->readS()-2;
        }
     }

    // If we need to break at the RTS, and we are at a RTS *and* the RTS is for the original JSR
    // (and not a sub-rts from a JSR inside the JSR), then break at the next cycle (thus after the RTS)
    if ($break_at_rts && $current_opcode == 0x60 && $c64->getCpu()->readS() == $break_rts_stack) {
        $break = true;
        $break_at_rts = false;
    }

    $c64->cycle();
};
