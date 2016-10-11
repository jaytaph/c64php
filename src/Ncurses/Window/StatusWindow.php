<?php

namespace Ncurses\Window;

use Mos6510\Cpu;
use Ncurses\Window;

class StatusWindow extends AbstractWindow {

    /**
     * @var Cpu
     */
    protected $cpu;

    function __construct(Cpu $cpu)
    {
        $this->cpu = $cpu;
    }

    function getTitle() {
        return "Status";
    }

    function getName() {
        return "status";
    }

    function init()
    {
        $cols = 30;
        $rows = 5;

        $this->window = new Window($this->windowManager->getNcurses(), $cols, $rows, 121, 0);
    }

    function drawBody()
    {
        $max = $this->windowManager->getSize();

        $this->window
            ->clearLine(1, NCURSES_COLOR_WHITE)
            ->drawString(2, 1, " A:       PC: ", "mem-header")
            ->drawString(2, 2, " X:       S: ", "mem-header")
            ->drawString(2, 3, " Y:       P: ", "mem-header")
        ;

        $f = "";
        $f .= $this->cpu->flagIsSet(Cpu::P_FLAG_NEGATIVE) ? "N" : "n";
        $f .= $this->cpu->flagIsSet(Cpu::P_FLAG_OVERFLOW) ? "O" : "o";
        $f .= "-";
        $f .= $this->cpu->flagIsSet(Cpu::P_FLAG_BREAK) ? "B" : "b";
        $f .= $this->cpu->flagIsSet(Cpu::P_FLAG_DECIMAL) ? "D" : "d";
        $f .= $this->cpu->flagIsSet(Cpu::P_FLAG_IRQ_DISABLE) ? "I" : "i";
        $f .= $this->cpu->flagIsSet(Cpu::P_FLAG_ZERO) ? "Z" : "z";
        $f .= $this->cpu->flagIsSet(Cpu::P_FLAG_CARRY) ? "C" : "c";

        $this->window
            ->drawString(6, 1, sprintf("%02X", $this->cpu->readA()), "dis-mnemonic")
            ->drawString(6, 2, sprintf("%02X", $this->cpu->readX()), "dis-mnemonic")
            ->drawString(6, 3, sprintf("%02X", $this->cpu->readY()), "dis-mnemonic")
            ->drawString(16, 1, sprintf("%04X", $this->cpu->readPc()), "dis-mnemonic")
            ->drawString(16, 2, sprintf("%02X", $this->cpu->readS()), "dis-mnemonic")
            ->drawString(16, 3, $f, "dis-mnemonic")
        ;
    }

    function processKey($ch)
    {
    }


}
