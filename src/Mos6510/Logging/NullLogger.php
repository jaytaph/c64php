<?php

namespace Mos6510\Logging;

class NullLogger implements LoggerInterface {

    function debug($str) {}

    function warning($str) {}

    function error($str) {}

}
