<?php

namespace Mos6510\Logging;

interface LoggerInterface {

    function debug($str);

    function warning($str);

    function error($str);

}
