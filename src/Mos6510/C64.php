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
            do {
                $pc = $this->cpu->readPc();
                $this->cycle();
            } while ($pc != 0xA65C);
        }
    }

    /**
     * Do a cycle for a single instruction. This will take between 2-7 cycles.
     */
    public function cycle() {
        $this->cpu->cycle();
        $this->cia1->cycle();
        $this->cia2->cycle();
        $this->vic2->cycle();
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
