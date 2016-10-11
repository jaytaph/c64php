<?php

declare(ticks=5);

namespace Mos6510\Debugger;

/**
 * The debugger uses NCurses to create a pretty output.
 */

use Mos6510\C64;
use Ncurses\Collection\ActiveCollectionInterface;
use Ncurses\Ncurses;
use Ncurses\Window\CommandWindow;
use Ncurses\Window\DisassemblyWindow;
use Ncurses\Window\MainWindow;
use Ncurses\Window\MemoryDumpWindow;
use Ncurses\Window\StackWindow;
use Ncurses\Window\StatusWindow;
use Ncurses\WindowManager;

class Debugger {

    /** @var C64 */
    protected $c64;

    /** @var NCurses */
    protected $ncurses;

    /** @var WindowManager */
    protected $windowManager;

    protected $done = false;
    protected $deadlock = false;
    protected $breakpoints = array();


    /**
     * Debugger constructor.
     * @param C64 $c64
     */
    public function __construct(C64 $c64)
    {
        $this->c64 = $c64;
    }

    function sig_winch_handler($signo)
    {
        // Keep IDE happy
        unset($signo);

        $this->init();

        $this->windowManager->draw();
        $this->windowManager->setStatusBar('Window change detected at '.(new \DateTime())->format('H:i:s'));

        $this->windowManager->refresh();
    }

    protected function init()
    {
        $this->ncurses_init();
        $this->windowManager = new WindowManager($this->ncurses);
        $this->windowManager->register(new MainWindow());
        $this->windowManager->register(new DisassemblyWindow($this->c64));
        $this->windowManager->register(new MemoryDumpWindow($this->c64->getMemory()));
        $this->windowManager->register(new StackWindow($this->c64->getCpu()));
        $this->windowManager->register(new StatusWindow($this->c64->getCpu()));
        $this->windowManager->register(new CommandWindow($this));

        $this->windowManager->setActiveWindow('command');
    }

    protected function ncurses_init()
    {
        $this->ncurses = new Ncurses();
        $this->ncurses
            ->setEchoState(false)
            ->setNewLineTranslationState(true)
            ->setCursorState(Ncurses::CURSOR_INVISIBLE)
        ;
        $this->ncurses->refresh();

        $this->ncurses->initColor('default', NCURSES_COLOR_WHITE | NCURSES_A_BOLD, NCURSES_COLOR_BLACK);
        $this->ncurses->initColor('window-border', NCURSES_COLOR_BLUE | NCURSES_A_BOLD, NCURSES_COLOR_BLACK);
        $this->ncurses->initColor('window-title', NCURSES_COLOR_YELLOW | NCURSES_A_BOLD, NCURSES_COLOR_BLACK);
        $this->ncurses->initColor('window-status', NCURSES_COLOR_YELLOW | NCURSES_A_BOLD, NCURSES_COLOR_BLUE | NCURSES_A_BOLD);
        $this->ncurses->initColor('window-scrollbar', NCURSES_COLOR_WHITE | NCURSES_A_BOLD, NCURSES_COLOR_BLUE);
        $this->ncurses->initColor('default-selected', NCURSES_COLOR_WHITE | NCURSES_A_BOLD, NCURSES_COLOR_BLUE | NCURSES_A_BOLD);

        $this->ncurses->initColor('dis-location', NCURSES_COLOR_WHITE, NCURSES_COLOR_BLACK);
        $this->ncurses->initColor('dis-codes', NCURSES_COLOR_CYAN | NCURSES_A_BOLD, NCURSES_COLOR_BLACK);
        $this->ncurses->initColor('dis-mnemonic', NCURSES_COLOR_YELLOW | NCURSES_A_BOLD, NCURSES_COLOR_BLACK);

        $this->ncurses->initColor('mem-header', NCURSES_COLOR_WHITE, NCURSES_COLOR_BLACK);
        $this->ncurses->initColor('mem-data', NCURSES_COLOR_BLUE | NCURSES_A_BOLD, NCURSES_COLOR_BLACK);

        $this->ncurses->setColor('default');
    }


    public function start() {
        pcntl_signal(SIGWINCH, array($this, "sig_winch_handler"));
        $this->init();

        // Draw all windows
        $this->windowManager->draw();
        $this->windowManager->setStatusBar('Initialized');

        $this->done = false;
        while (! $this->done) {
            $this->windowManager->refresh();

            do {
                $r = array(STDIN);
                $w = NULL;
                $e = NULL;
                $hasData = @stream_select($r, $w, $e, 1);
            } while (! $hasData);

            $ch = $this->ncurses->getCh();
            $this->windowManager->setStatusBar(sprintf("Key %03d | Last update at %s", $ch, (new \DateTime())->format('H:i:s')));

            // Handle key input, global first, then for the specified window
            $this->processKeyGlobal($ch);
            $this->windowManager->getActiveWindow()->processKey($ch);
        }
    }

    protected function processKeyGlobal($ch) {
        switch ($ch) {
            case NCURSES_KEY_BTAB :
                $this->windowManager->seekActiveWindow(-1, ActiveCollectionInterface::SEEK_CUR);
                break;
            case Ncurses::KEY_TAB :
                $this->windowManager->seekActiveWindow(1, ActiveCollectionInterface::SEEK_CUR);
                break;
            default :
                break;
        }
    }

    function command_run() {
        // Run by cycling. When deadlock or breakpoint occurs, it will return false.
        while ($this->cycle()) {
        }

        // Redraw screen
        $this->windowManager->draw();
        $this->windowManager->refresh();
    }

    function command_step($count = 1) {
        // Step $count instructions, or until breakpoint or deadlock
        $i = $count;
        while ($i && $this->cycle()) {
            $i--;
        }

        $this->windowManager->draw();
        $this->windowManager->refresh();
    }

    function command_break($location) {
        // Set breakpoint
        $this->breakpoints[hexdec($location)] = 1;
    }


    /**
     * Cycle the C64 a single instruction (which spans multiple cycles). Returns false when deadlocked or breakpoint.
     *
     * @return bool
     */
    function cycle() {
        // Deadlocked
        if ($this->deadlock) {
            return false;
        }

        // Run cycle
        $pc1 = $this->c64->getCpu()->readPc();
        $this->c64->getCpu()->cycle();
        $pc2 = $this->c64->getCpu()->readPc();

        // Check for deadlock
        if ($pc1 == $pc2) {
            $this->deadlock = true;
            return false;
        }

        // Check for new breakpoint
        if ($this->breakpointDetected($pc2)) {
            return false;
        }

//        // Refresh display (if needed)
//        if ($this->c64->getCpu()->getTickCount() % 1000 < 2) {
//            $this->windowManager->draw();
//            $this->windowManager->refresh();
//        }

        return true;
    }

    /**
     * Returns true when the $pc hits one of the breakpoints (breakpoints as stored as KEYS, not values)
     * @param $pc
     * @return bool
     */
    function breakpointDetected($pc) {
        if (in_array($pc, array_keys($this->breakpoints))) {
            return true;
        }
        return false;
    }
}

