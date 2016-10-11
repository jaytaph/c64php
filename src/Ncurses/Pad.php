<?php

namespace Ncurses;

class Pad {

    /** @var Ncurses */
    protected $ncurses;

    /** ncurses pad resource */
    protected $padResource;

    /** @var int */
    protected $rows;
    /** @var int */
    protected $columns;

    /**
     * Create a pad
     *
     * @param int $columns
     * @param int $rows
     */
    public function __construct(Ncurses $ncurses, $columns, $rows) {
        $this->ncurses = $ncurses;

        $this->columns = $columns;
        $this->rows = $rows;

        $this->padResource = ncurses_newpad($rows, $columns);
    }

    /**
     * Destructs a pad
     */
    public function __destruct() {
        ncurses_delwin($this->padResource);
    }

    /**
     * Returns a ncurses pad resource
     * @return resource
     */
    public function getPad() {
        return $this->padResource;
    }

    /**
     * Gets pad size
     * @return array An array with elements 'columns' and 'rows'
     */
    public function getSize() {
        $rows = 0;
        $cols = 0;
        ncurses_getmaxyx($this->padResource, $rows, $cols);
        return array('columns' => $cols, 'rows' => $rows);
    }

    public function drawString($x, $y, $string, $colorKey = -1, $attributes = NCURSES_A_BOLD) {
        $this->setColor($colorKey);

        ncurses_wattron($this->padResource, $attributes);
        ncurses_wmove($this->padResource, $y, $x);
        ncurses_waddstr($this->padResource, $string);
        ncurses_wattroff($this->padResource, $attributes);

        return $this;
    }

    function setColor($colorKey) {
        if ($colorKey == -1) {
            return $this;
        }

        $this->colorKey = $colorKey;

        $idx = $this->ncurses->getColorIdx($colorKey);
        ncurses_wcolor_set($this->padResource, $idx);

        return $this;
    }

}
