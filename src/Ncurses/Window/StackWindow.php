<?php

namespace Ncurses\Window;

use Mos6510\Cpu;
use Mos6510\Disassembler;
use Ncurses\Window;

class StackWindow extends AbstractWindow {

    /**
     * @var Cpu
     */
    protected $cpu;

    function __construct(Cpu $cpu)
    {
        $this->cpu = $cpu;
    }

    function getTitle() {
        return "Stack";
    }

    function getName() {
        return "stack";
    }

    function init()
    {
        $max = $this->windowManager->getSize();
        $cols = 30;
        $rows = $max['rows'] - 5 - 20;

        $this->window = new Window($this->windowManager->getNcurses(), $cols, $rows, 121, 5);
    }

    function drawBody()
    {
        $max = $this->windowManager->getSize();

        $i = 0;
        for ($y=1; $y!=$max['rows']-2; $y++) {
            $c = sprintf("%02X", $this->cpu->memory->read8(0x01FF - $i));
            if ($this->cpu->readS() >= (0xFF - $i)) {
                $c = "--";
            }
            $this->window
                ->clearLine($y, NCURSES_COLOR_WHITE)
                ->drawString(2, $y, sprintf("%04X", 0x01FF - $i, 'dis-location'))
                ->drawString(7, $y, $c, 'dis-codes')
            ;

            $i++;
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
