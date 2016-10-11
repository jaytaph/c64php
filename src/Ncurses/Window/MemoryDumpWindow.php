<?php

namespace Ncurses\Window;

use Mos6510\Memory;
use Ncurses\Window;

class MemoryDumpWindow extends AbstractWindow {

    /**
     * @var Memory
     */
    protected $memory;

    protected $dump_offset = 0;

    function __construct(Memory $memory)
    {
        $this->memory = $memory;

        $this->dump_offset = 0xA000;
    }

    function getTitle() {
        return "Memory Dump";
    }

    function getName() {
        return "memorydump";
    }

    function init()
    {
        $max = $this->windowManager->getSize();
        $cols = 81;
        $rows = $max['rows'] - 20;

        $this->window = new Window($this->windowManager->getNcurses(), $cols, $rows, 40, 0);
    }

    function drawBody()
    {
        $max = $this->windowManager->getSize();

        $this->window
            ->clearLine(1, NCURSES_COLOR_WHITE)
            ->drawString(2, 1, "       0  1  2  3 |  4  5  6  7 |  8  9  A  B |  C  D  E  F |", 'mem-header')
        ;

        $offset = 0;

        for ($y=2; $y!=$max['rows']-2; $y++) {
            $this->window->clearLine($y, NCURSES_COLOR_WHITE);

            if ($this->dump_offset + $offset > 0xFFFF) {
                continue;
            }

            $this->window
                ->drawString(2, $y, sprintf("%04X              |             |             |             | ", $this->dump_offset + $offset), 'mem-header')
            ;


            $dump = array();
            $ascii = "";
            for ($i=0; $i!=16; $i++) {
                $b = $this->memory->read8($this->dump_offset + $offset + $i);
                $dump[$i] = sprintf("%02X", $b);
                $ascii .= ($b >= 32 && $b <= 127) ? chr($b) : ".";
            }
            $offset += 16;

            for ($i=0; $i!=4; $i++) {
                $this->window
                    ->drawString( 8, $y, $dump[0]." ".$dump[1]." ".$dump[2]." ".$dump[3], 'mem-data')
                    ->drawString(22, $y, $dump[4]." ".$dump[5]." ".$dump[6]." ".$dump[7], 'mem-data')
                    ->drawString(36, $y, $dump[8]." ".$dump[9]." ".$dump[10]." ".$dump[11], 'mem-data')
                    ->drawString(50, $y, $dump[12]." ".$dump[13]." ".$dump[14]." ".$dump[15], 'mem-data')
                    ->drawString(64, $y, $ascii, 'mem-data')
                ;
            }
        }
    }

    function processKey($ch)
    {
        // Based on active window, process key.
        switch ($ch) {
            case NCURSES_KEY_END :
                $this->dump_offset = 0xFFFF + 1 - 0x0250;
                $this->drawBody();
                break;
            case NCURSES_KEY_HOME :
                $this->dump_offset = 0x0000;
                $this->drawBody();
                break;


            case NCURSES_KEY_NPAGE :
                $this->dump_offset += 0x0250;
                if ($this->dump_offset > 0x0FFFF + 1 - 0x0250) {
                    $this->dump_offset = 0x0FFFF + 1 - 0x0250;
                }
                $this->drawBody();
                break;
            case NCURSES_KEY_PPAGE :
                $this->dump_offset -= 0x0250;
                if ($this->dump_offset < 0) {
                    $this->dump_offset = 0;
                }
                $this->drawBody();
                break;

            case NCURSES_KEY_DOWN :
                $this->dump_offset += 0x0010;
                if ($this->dump_offset > 0x0FFFF + 1 - 0x0250) {
                    $this->dump_offset = 0x0FFFF + 1 - 0x0250;
                }
                $this->drawBody();
                break;
            case NCURSES_KEY_UP :
                $this->dump_offset -= 0x0010;
                if ($this->dump_offset < 0) {
                    $this->dump_offset = 0;
                }
                $this->drawBody();
                break;
        }
    }


}
