<?php

namespace Ncurses\Window;

use Mos6510\C64;
use Mos6510\Disassembler;
use Ncurses\Window;

class DisassemblyWindow extends AbstractWindow {

    /**
     * @var C64
     */
    protected $c64;

    // Current process counter that is used as start inside the code
    protected $current_pc;

    function __construct(C64 $c64)
    {
        $this->c64 = $c64;

        $this->current_pc = $this->c64->getCpu()->readPc();
    }

    function getTitle() {
        return "Disassembly";
    }

    function getName() {
        return "disassembly";
    }

    function init()
    {
        $max = $this->windowManager->getSize();
        $cols = 40;
        $rows = $max['rows'] - 20;

        $this->window = new Window($this->windowManager->getNcurses(), $cols, $rows, 0, 0);
    }

    function drawBody()
    {
        $dis = new Disassembler($this->c64);
        $struct = $dis->disassemble($this->c64->getCpu()->readPc(), 40);

        $y = 1;
        foreach ($struct as $entry) {
            $this->window
                ->clearLine($y, NCURSES_COLOR_WHITE)
                ->drawString(2, $y, sprintf("%04X", $entry['offset']), 'dis-location')
                ->drawString(10, $y, $entry['operands_str'], 'dis-codes')
                ->drawString(20, $y, $entry['mnemonic'], 'dis-mnemonic')
            ;
            $y++;
        }
    }

    function processKey($ch)
    {
/*
        // Based on active window, process key.
        switch ($ch) {
            case NCURSES_KEY_END :
                $this->accountManager->getActiveAccount()->switchActiveFolder(0, ActiveCollectionInterface::SEEK_END);
                $this->drawBody();
                break;
            case NCURSES_KEY_HOME :
                $this->accountManager->getActiveAccount()->switchActiveFolder(0, ActiveCollectionInterface::SEEK_SET);
                $this->drawBody();
                break;


            case NCURSES_KEY_NPAGE :
                $max = $this->window->getSize();
                $this->accountManager->getActiveAccount()->switchActiveFolder($max['rows'], ActiveCollectionInterface::SEEK_CUR);
                $this->drawBody();
                break;
            case NCURSES_KEY_PPAGE :
                $max = $this->window->getSize();
                $this->accountManager->getActiveAccount()->switchActiveFolder(0 - $max['rows'], ActiveCollectionInterface::SEEK_CUR);
                $this->drawBody();
                break;

            case NCURSES_KEY_DOWN :
                $this->accountManager->getActiveAccount()->switchActiveFolder(1, ActiveCollectionInterface::SEEK_CUR);
                $this->drawBody();
                break;
            case NCURSES_KEY_UP :
                $this->accountManager->getActiveAccount()->switchActiveFolder(-1, ActiveCollectionInterface::SEEK_CUR);
                $this->drawBody();
                break;
        }
*/
    }


}
