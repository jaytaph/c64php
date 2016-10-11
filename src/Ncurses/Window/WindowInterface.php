<?php

namespace Ncurses\Window;

use Ncurses\WindowManager;

interface WindowInterface {

    function getTitle();
    function getName();

    function register(WindowManager $manager);
    function init();

    function isSelectable();

    function drawBorder($active);
    function drawBody();

    function refresh();
    function processKey($ch);
}
