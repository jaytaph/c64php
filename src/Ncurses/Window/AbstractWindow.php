<?php

namespace Ncurses\Window;

use Ncurses\WindowManager;
use Ncurses\Window;

abstract class AbstractWindow implements WindowInterface {

    /** @var WindowManager */
    protected $windowManager;

    /** @var Window */
    protected $window;

    function isSelectable() {
        return false;
    }

    function refresh() {
        if (! $this->window) {
            return;
        }

        $this->window->refresh();
    }

    function register(WindowManager $windowManager) {
        $this->windowManager = $windowManager;
    }

    function init() { }

    function drawBorder($active) {
        if (! $this->window) {
            return;
        }

        $this->window
            ->setColor('window-border')->border()->scrollbar()
            ->setColor('window-title')->title($this->getTitle(), -1, $active)
        ;
    }

    /**
     * @return window
     */
    public function getNcursesWindow()
    {
        return $this->window;
    }

    function drawBody() { }

    function processKey($ch) { }

}
