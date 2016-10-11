<?php

namespace Ncurses;

class Wrapper {

    static public function wrap(callable $callable) {
        try {
            ncurses_init();
            ncurses_noecho();
            ncurses_cbreak();

            $stdscr = ncurses_newwin(0, 0, 0, 0);

            ncurses_keypad($stdscr, 1);
            ncurses_clear();

            $callable($stdscr);

        } finally {
            ncurses_keypad($stdscr, 0);
            ncurses_echo();
            ncurses_nocbreak();
            ncurses_end();
        }
    }
}
