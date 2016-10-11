<?php

namespace Ncurses;

use Ncurses\Collection\ActiveCollection;
use Ncurses\Collection\ActiveCollectionInterface;
use Ncurses\Ncurses;
use Ncurses\Window\WindowInterface;

class WindowManager {

    /** @var NCurses */
    protected $ncurses;

    /** @var ActiveCollectionInterface */
    protected $windows;


    function __construct(NCurses $ncurses) {
        $this->ncurses = $ncurses;
        $this->windows = new ActiveCollection(true);
    }

    function register(WindowInterface $window) {
        $window->register($this);
        $window->init();
        $this->windows->addItem($window->getName(), $window);
    }

    function getSize() {
        $rows = 0;
        $cols = 0;
        ncurses_getmaxyx(STDSCR, $rows, $cols);
        return array('cols' => $cols, 'rows' => $rows);
    }

    function draw() {
        foreach ($this->windows as $window) {
            $window->drawBorder($this->isActive($window));
            $window->drawBody();
        }
    }

    function refresh() {
        foreach ($this->windows as $window) {
            $window->refresh();
        }
    }

    /**
     * @return Ncurses
     */
    public function getNcurses()
    {
        return $this->ncurses;
    }

    /**
     * @param $key
     * @return Window
     */
    function getWindow($key) {
        return $this->windows->getItem($key);
    }


    protected function redrawActiveWindow() {
        foreach ($this->windows as $window) {
            $window->drawBorder($this->isActive($window));
        }
    }

    function getActiveWindow() {
        return $this->windows->getActiveItem();
    }

    function getWindows() {
        return $this->windows;
    }

    /**
     * @param WindowInterface $window
     * @return bool
     */
    function isActive(WindowInterface $window) {
        return $this->windows->isActive($window);
    }

    function seekActiveWindow($offset, $mode) {
        $this->windows->seekActive($offset, $mode);
        $this->redrawActiveWindow();
    }

    function setActiveWindow($windowKey) {
        $this->windows->setActiveByKey($windowKey);

        $this->redrawActiveWindow();
    }

    function setStatusBar($status) {

        $this->getWindow('main')->getNcursesWindow()->status($status, 'window-status');
    }

    /**
     * @param array $cols
     * @return array
     */
    function generateColumns(array $cols) {
        $p = 0;
        $f = 0;
        foreach ($cols as $col) {
            if ($col[strlen($col)-1] == '%') {
                $p += (int)(substr($col, 0, -1));
            } else {
                $f += (int)$col;
            }
        }
        if ($p != 100) {
            throw new \InvalidArgumentException('Percentage should be equal to 100%');
        }

        $max = $this->getSize();
        $maxCols = $max['cols'];
        $maxCols -= $f;

        $ret = array(0);
        foreach ($cols as $col) {
            if ($col[strlen($col)-1] == '%') {
                $p = (int)(substr($col, 0, -1));
                $ret[] = array_sum($ret) + round($maxCols / 100 * $p);
            } else {
                $ret[] = array_sum($ret) + (int)$col;
            }
        }
        array_pop($ret);

        return $ret;
    }

}
