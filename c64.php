<?php

use Mos6510\C64;
use Mos6510\Io\ShmIo;
use Mos6510\Logging\FileLogger;

require_once "./vendor/autoload.php";

date_default_timezone_set("Europe/Amsterdam");

$c64 = new C64(new FileLogger("output.log"), new ShmIo());
$c64->boot(true);

// Load PRG file if needed
if ($argc == 2 && file_exists($argv[1]) && substr($argv[1], -4, 4) == ".prg") {
    print "Loading PRG file: $argv[1]: ";
    $c64->getMemory()->loadPrg($argv[1]);
    print "done.\n";

    // Type "RUN\n" so the program will automatically run
    $c64->getMemory()->write8(0xC6, 4);
    $c64->getMemory()->write8(0x277, 82);
    $c64->getMemory()->write8(0x278, 85);
    $c64->getMemory()->write8(0x279, 78);
    $c64->getMemory()->write8(0x27A, 13);
}

// Loop de loop
while (true) {
    $c64->cycle();
};
