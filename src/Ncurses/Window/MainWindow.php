<?php

namespace Ncurses\Window;

use Ncurses\Window;

class MainWindow extends AbstractWindow {

    function getTitle() {
        return "";
    }

    function getName() {
        return "main";
    }

    function isSelectable() {
        return false;
    }

    function init() {
        $this->window = new Window($this->windowManager->getNcurses(), 0, 0, 0, 0);
    }

    function drawBorder($active)
    {
        // Do nothing
    }


    function setStatusBar($msg) {
        $this->window->status($msg);
    }

}
