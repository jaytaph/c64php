<?php

namespace Ncurses;

use wapmorgan\NcursesObjects\Ncurses as BaseNcurses;

class Ncurses extends BaseNcurses
{
    protected $colors = array(0 => 'background-colors');
    protected $has_colors = false;

    public function __construct()
    {
        parent::__construct();

        $this->has_colors = ncurses_has_colors();

        if ($this->has_colors) {
            ncurses_start_color();
        }
        ncurses_refresh(0);
    }

    public function __destruct() {
        parent::__destruct();
    }

    public function getColorIdx($key)
    {
        $idx = array_search($key, $this->colors);
        if (!$idx) $idx = 1;

        return $idx;
    }


    public function setColor($key) {
        if (! $this->has_colors) {
            return $this;
        }

        $idx = $this->getColorIdx($key);
        ncurses_color_set($idx);

        return $this;
    }

    public function initColor($key, $fg, $bg) {
        if (! $this->has_colors) {
            return;
        }

        $idx = count($this->colors);
        $this->colors[] = $key;

        ncurses_init_pair($idx, $fg, $bg);
    }

    public function getMaxXY() {
        $x = 0;
        $y = 0;
        ncurses_getmaxyx(STDSCR, $y, $x);

        return array($x, $y);
    }
}
