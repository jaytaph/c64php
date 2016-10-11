<?php

namespace Ncurses\Window;

use Mos6510\Cpu;
use Mos6510\Debugger\Debugger;
use Ncurses\Window;

class CommandWindow extends AbstractWindow {

    protected $output = array();
    protected $command = "";

    /** @var Debugger */
    protected $debugger;

    /**
     * CommandWindow constructor.
     */
    public function __construct(Debugger $debugger)
    {
        $this->debugger = $debugger;
    }


    function getTitle() {
        return "Command";
    }

    function getName() {
        return "command";
    }

    function init()
    {
        $max = $this->windowManager->getSize();
        $rows = $max['rows'] - 20;

        $this->window = new Window($this->windowManager->getNcurses(), $max['cols'], 20-1, 0, $max['rows']-20);
    }

    function drawBody()
    {
        $max = $this->window->getSize();

        $len = $max['rows'] - 3;
        if (count($this->output) > $len) {
            $tmp = array_slice($this->output, count($this->output) - $len, $len);
        } else {
            $tmp = $this->output;
        }

        $y = 0;
        foreach ($tmp as $line) {
            $y++;
            $this->window->clearLine($y)->drawString(2, $y, $line, 'mem-header');
        }

        $this->window->clearLine($len+1)->drawString(2, $len+1, "C64> " . $this->command, 'mem-data');
    }

    function processKey($ch)
    {
        if ($ch == 10) {
            // run command
            $this->output[] = "> ".$this->command;

            $this->processCommand($this->command);
            $this->command = "";
        }

        if ($ch >= 32 && $ch <= 127) {
            $this->command .= chr($ch);
        }

        if ($ch == 263) {
            $this->command = substr($this->command, 0, -1);
        }

        $this->drawBody();
    }


    protected function processCommand($command) {
        $cmd = explode(" ", $command);

        switch ($cmd[0]) {
            case "quit":
                $this->output[] = "Ok, quitting?";
                break;
            case "b":
            case "break":
                $this->output[] = "Setting breakpoint to ".$cmd[1];
                $this->debugger->command_break($cmd[1]);
                break;
            case "u":
            case "unbreak":
                $this->output[] = "Unsetting breakpoint to ".$cmd[1];
                break;
            case "r" :
            case "run":
                $this->output[] = "Running";
                $this->debugger->command_run();
                break;
            case "s" :
            case "step":
                $this->output[] = "Stepping";
                $this->debugger->command_step(isset($cmd[1]) ? $cmd[1] : 1);
                break;
            case "stepo":
                $this->output[] = "Stepping to RTS";
                break;
            default:
                $this->output[] = "Unknown command";
                break;
        }
    }

}
