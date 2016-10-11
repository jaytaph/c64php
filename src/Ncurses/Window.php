<?php

namespace Ncurses;

class Window {

    /** @var Ncurses */
    protected $ncurses;

    /**
     * ncurses window resource
     */
    protected $windowResource;

    /**
     * cursor position
     */
    protected $cursorX;
    protected $cursorY;

    protected $bordered = false;

    /**
     * window geometry
     */
    protected $rows;
    protected $columns;
    protected $x;
    protected $y;


    /**
     * Create a window
     *
     * @param int $columns
     * @param int $rows
     * @param int $x
     * @param int $y
     */
    public function __construct(Ncurses $ncurses, $columns = 0, $rows = 0, $x = 0, $y = 0) {
        $this->ncurses = $ncurses;

        $this->x = $x;
        $this->y = $y;
        $this->windowResource = ncurses_newwin($rows, $columns, $y, $x);

        $max = $this->getSize();
        $this->columns = $columns == 0 ? $max['cols'] : $columns;
        $this->rows = $rows == 0 ? $max['rows'] : $rows;
    }

    public function getMaxXY() {
        ncurses_getmaxyx($this->windowResource, $x, $y);

        return array($x, $y);
    }

    /**
     * Destructs a window
     */
    public function __destruct() {
        ncurses_delwin($this->windowResource);
    }

    /**
     * Returns a ncurses window resource
     * @return resource
     */
    public function getWindow() {
        return $this->windowResource;
    }

    /**
     * Gets window size
     * @return array An array with elements 'columns' and 'rows'
     */
    public function getSize() {
        $rows = 0;
        $cols = 0;
        ncurses_getmaxyx($this->windowResource, $rows, $cols);
        return array('cols' => $cols, 'rows' => $rows);
    }

    /**
     * Draws a border around this window
     * @return Window This object
     */
    public function border($left = 0, $right = 0, $top = 0, $bottom = 0, $tl_corner = 0, $tr_corner = 0, $bl_corner = 0, $br_corner = 0) {
        $this->bordered = true;

        ncurses_wborder($this->windowResource, $left, $right, $top, $bottom, $tl_corner, $tr_corner, $bl_corner, $br_corner);
        return $this;
    }

    public function scrollbar() {
        $p = rand(0, 100);

        $y = round($this->rows - 2 / 100 * $p);
//        for ($y = 1; $y != $this->rows-1; $y++) {
            $this->drawString($this->columns-1, $y + 1, "*", 'window-scrollbar', 0);
//        }

        return $this;
    }


    /**
     * Refreshes (redraws) a window
     * @return Window This object
     */
    public function refresh() {
        ncurses_wrefresh($this->windowResource);
        return $this;
    }

    /**
     * Draws a window title
     * @param string $title Title
     * @return Window This object
     */
    public function title($title, $colorKey = -1, $active = false) {
        $this->drawString(1, 0, ' ' . $title . ' ', $colorKey, $active ? NCURSES_A_REVERSE : NCURSES_A_BOLD);
        return $this;
    }

    /**
   	 * Draws a window status (a line at the bottom)
   	 * @param string $status Status
   	 * @return Window This object
   	 */
   	public function status($status, $colorKey = -1, $attribs = NCURSES_A_BOLD) {
        $this->clearLine($this->rows-1, $colorKey);
        $this->drawString(1, $this->rows-1, $status, $colorKey, $attribs);

        $dt = (new \DateTime())->format('H:i:s');
        $this->drawString($this->columns-strlen($dt)-1, $this->rows-1, $dt, $colorKey, $attribs);

        return $this;
    }

    public function clearLine($y, $colorKey = -1)
    {
        ncurses_getmaxyx($this->windowResource, $max_y, $max_x);

        $s = str_repeat('  ', $max_x - ($this->bordered ? 0 : 0));

        $this->drawString($this->bordered ? 1 : 0, $y, $s, $colorKey);

        return $this;
    }

    /**
     * Erases a window
     * @return Window This object
     */
    public function erase($colorKey) {
        // So not efficient
        for ($y=1; $y != $this->rows; $y++) {
            $this->clearLine($y, $colorKey);
        }

        return $this;
    }

    public function drawString($x, $y, $string, $colorKey = -1, $attributes = NCURSES_A_BOLD) {
        $this->setColor($colorKey);

        $max = $this->getSize();
        $maxStrLen = $max['cols'] - $x - (($this->bordered) ? 2 : 0);

//        for ($i=0; $i!=strlen($string); $i++) {
//            $a = false;
//            if (ctype_digit($string[$i])) {
//                $a = range('0', '9');
//            }
//            if (ctype_alpha($string[$i])) {
//                if (ctype_lower($string[$i])) {
//                    $a = range('a', 'z');
//                } else {
//                    $a = range('A', 'Z');
//                }
//            }
//
//            if ($a) {
//                $string[$i] = $a[array_rand($a)];
//            }
//        }

        if ($y > $max['rows'] - (($this->bordered) ? 2 : 0)) {
            return $this;
        }


        ncurses_wattron($this->windowResource, $attributes);
        ncurses_wmove($this->windowResource, $y, $x);
        ncurses_waddstr($this->windowResource, $string, $maxStrLen);
        ncurses_wattroff($this->windowResource, $attributes);

        return $this;
    }



    function setColor($colorKey) {
        if ($colorKey == -1) {
            return $this;
        }

        $this->colorKey = $colorKey;

        $idx = $this->ncurses->getColorIdx($colorKey);
        ncurses_wcolor_set($this->windowResource, $idx);

        return $this;
    }

    function getNcurses()
    {
        return $this->ncurses;
    }

}
