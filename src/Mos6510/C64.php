<?php

namespace Mos6510;

use Mos6510\Io\IoInterface;
use Mos6510\Logging\LoggerInterface;

/**
 * The C64 is a collection of different components
 */

class C64 {

    /** @var LoggerInterface */
    protected $logger;

    /** @var Cpu 6502 CPU */
    protected $cpu;

    /** @var Memory Actual memory system */
    protected $memory;

    /** @var Cia1 First IO */
    protected $cia1;

    /** @var Cia2 Second IO */
    protected $cia2;

    /** @var Vic2 Video chip */
    protected $vic2;

    /** @var int Clock frequency. Unused at the moment  */
    protected $clock_freq = 0;

    const   CLOCK_PAL  =  985248;       // Frequency on PAL systems
    const   CLOCK_NTSC = 1022727;       // Frequency on NTSC systems

    /**
     * @param $logger
     */
    public function __construct(LoggerInterface $logger, IoInterface $io, $clock_freq = self::CLOCK_PAL)
    {
        $this->logger = $logger;
        $this->io = $io;

        // Create / initialize the different components. We use the C64 as a base for components to reach others
        $this->memory= new Memory($this, $this->logger);
        $this->cpu = new Cpu($this, $this->logger);
        $this->cia1 = new Cia1($this, 0xDC00, $io, $this->logger);
        $this->cia2 = new Cia2($this, 0xDD00, $io, $this->logger);
        $this->vic2 = new Vic2($this, 0xD000, $io, $this->logger);

        $this->clockFreq = $clock_freq;
    }

    /**
     * Boot up the C64
     * @param bool $wait_for_basic_ready
     */
    public function boot($wait_for_basic_ready = true) {
        $this->cpu->boot();

        if ($wait_for_basic_ready) {
            // Execute until we reach a certain point in kernel ram. This can be assumed as the "start" of basic.
            do {
                $pc = $this->cpu->readPc();
                $this->cycle();
            } while ($pc != 0xA65C);
        }

        $this->io->enableOutput(true);
    }

    /**
     * Cycle
     */
    public function cycle() {
        $t1 = microtime(true);

        $start_tick = $this->getCpu()->getTickCount();
        $this->cpu->cycle();

        // Set the tick counter for the current number of ticks that the CPU cycle needed
        $tick_counter = $this->getCpu()->getTickCount() - $start_tick;

        // Since Cpu::cycle() uses X amount of cycles, we must make sure that the Vic stays in sync by also executing
        // that many cycles.
        //
        // Normally it will be:  cpu-vic-cpu-vic-....
        // In the emulator it will be: cpu-cpu-cpu-vic-vic-vic-cpu-cpu-vic-vic-....
        while ($tick_counter > 0) {
            $tick_counter--;
            $this->vic2->cycle();
        }

        // Do CIA's. They are synced on 60hz by themselves by using the host microtime().
        $this->cia1->cycle();
        $this->cia2->cycle();

    }

    /**
     * @return Cpu
     */
    public function getCpu()
    {
        return $this->cpu;
    }

    /**
     * @return Memory
     */
    public function getMemory()
    {
        return $this->memory;
    }

    /**
     * @return Cia1
     */
    public function getCia1()
    {
        return $this->cia1;
    }

    /**
     * @return Cia2
     */
    public function getCia2()
    {
        return $this->cia2;
    }

    /**
     * @return Vic2
     */
    public function getVic2()
    {
        return $this->vic2;
    }

}
