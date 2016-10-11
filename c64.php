<?php

use Mos6510\C64;
use Mos6510\Io\ShmIo;
use Mos6510\Logging\FileLogger;

require_once "./vendor/autoload.php";

date_default_timezone_set("Europe/Amsterdam");

$c64 = new C64(new FileLogger("output.log"), new ShmIo());
$c64->boot();

// Loop de loop
while (true) {
    $c64->cycle();
};
