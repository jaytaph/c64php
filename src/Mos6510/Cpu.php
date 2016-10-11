<?php

namespace Mos6510;

use Mos6510\Logging\LoggerInterface;

class Cpu
{

    // CPU Register
    protected $a  = 0x00;      // Accumulator                      8 bit
    protected $x  = 0x00;      // X register                       8 bit
    protected $y  = 0x00;      // Y register                       8 bit
    protected $pc = 0x00;      // program counter                  16 bit (PCL PCH)
    protected $p  = 0x00;      // processor status register        8 bit
    protected $s  = 0xFF;      // Stack pointer                    8 bit

    /** @var Memory */
    public $memory;

    /** @var Cia1 */
    public $cia1;

    /** @var Cia2 */
    public $cia2;

    /** @var Opcoder Handles all instructions */
    public $opcoder;

    // How many ticks already passed
    protected $ticks = 0;

    /** @var bool IRQ triggered */
    protected $irq = false;

    // Program flags
    const P_FLAG_CARRY          = 0;      // Unsigned overflow
    const P_FLAG_ZERO           = 1;      // result was zero
    const P_FLAG_IRQ_DISABLE    = 2;      // IRQ disabled  1 = disabled, only BRK and NMI
    const P_FLAG_DECIMAL        = 3;      // Decimal mode / 1 = BCD
    const P_FLAG_BREAK          = 4;      // Interrupt by BRK
    const P_FLAG_RESERVED       = 5;      // Not used, always 1
    const P_FLAG_OVERFLOW       = 6;      // result overflowed
    const P_FLAG_NEGATIVE       = 7;      // result negative

    const NMI_VECTOR       = 0xFFFA;        // Vector for NMI
    const COLDSTART_VECTOR = 0xFFFC;        // Vector for boot
    const IRQ_BRK_VECTOR   = 0xFFFE;        // Vector for IRQ and BRK

    /**
     * Cpu constructor.
     * @param C64 $c64
     * @param LoggerInterface $logger
     */
    public function __construct(C64 $c64, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->c64 = $c64;
    }


    /**
     * Bootup sequence
     */
    public function boot()
    {
        $this->opcoder = new Opcoder($this->c64->getCpu());
        $this->memory = $this->c64->getMemory();
        $this->cia1 = $this->c64->getCia1();
        $this->cia2 = $this->c64->getCia2();

        // Set processor flags
        $this->p = 0;
        $this->flagSet(self::P_FLAG_RESERVED, 1);
        $this->flagSet(self::P_FLAG_BREAK, 1);

        // Read program counter and start execution
        $this->pc = $this->memory->read16(Cpu::COLDSTART_VECTOR);

        // Emulate 6 clock cycles for system initialization
        $this->ticks = 6;
    }

    /**
     * Tick cycles
     *
     * @param $ticks
     */
    public function tick($ticks) {
        $this->ticks += $ticks;
    }

    /**
     * Perform a single cycle
     */
    public function cycle()
    {
        if ($this->irq) {
            $this->irq = false;

            $this->interrupt(Cpu::IRQ_BRK_VECTOR);
            return;
        }

        // Process instruction
        $this->opcoder->process();
    }

    /**
     * Trigger an IRQ
     */
    public function triggerIrq() {
        $this->irq = true;
    }


    /**
     * Start interrupt for both NMI and IRQ (just a different vector)
     *
     * @param $vector
     */
    protected function interrupt($vector) {
        $this->stackPush16($this->readPc());

        $this->flagSet(Cpu::P_FLAG_BREAK, 0);  // Break flag should be cleared
        $this->stackPush8($this->readP());

        // Disable interrupt (after we push the flags onto the stack
        $this->flagSet(Cpu::P_FLAG_IRQ_DISABLE, 1);
        $this->writePc($this->memory->read16($vector));
        $this->tick(7);
    }
    /**
     * Returns the current ticks
     * @return int
     */
    public function getTickCount()
    {
        return $this->ticks;
    }


    /**
     * Set or unset the given processor flag
     *
     * @param $bit
     * @param $value
     */
    function flagSet($bit, $value)
    {
        $this->p = Utils::bit_set($this->p, $bit, $value);

        // Always set the reserved flag
        $this->p = Utils::bit_set($this->p, self::P_FLAG_RESERVED, 1);
    }

    /**
     * Returns true when bit in processor flag is set
     *
     * @param $bit
     * @return bool
     */
    function flagIsSet($bit)
    {
        return Utils::bit_test($this->p, $bit);
    }

    /**
     * Reads a byte from the program counter
     *
     * @return mixed
     */
    function read8FromPc()
    {
        $val = $this->memory->read8($this->pc);
        $this->pc++;

        return $val;
    }

    /**
     * Reads a word from the program counter
     *
     * @return mixed
     */
    function read16FromPc()
    {
        $val = $this->memory->read16($this->pc);
        $this->pc += 2;

        return $val;
    }

    /**
     * Change the program counter on branching. This will identify any page crossing and returns the correct used cycle count
     *
     * @param $value
     * @return int
     */
    function branchPc($value)
    {
        // Negative numbers is a branch backwards. Get actual value from 2-complements.
        if (($value & 0x80) == 0x80) {
            $value = 0 - (255 - $value) - 1;
        }

        // We check from the CURRENT pc (which will be the next operation if the branch wasn't taken)
        if ($this->memory->willCrossPage($this->pc, $value)) {
            $this->tick(1);
        }

        $this->pc += $value;
    }


    /*
     * Stack functionality
     */

    /**
     * @param $increment
     */
    protected function updateStackPointer($increment)
    {
        $this->s += $increment;

        if ($this->s < 0 || $this->s > 0xFF) {
            $this->s &= 0xFF;
            $this->logger->warning("Stack pointer wrapped");
        }
    }

    /**
     * @param $value
     */
    public function stackPush8($value)
    {
        assert($value <= 0xFF);

        $this->memory->write8(Memory::STACK_PAGE_ADDR + $this->s, $value);
        $this->updateStackPointer(-1);
    }

    /**
     * @param $value
     */
    public function stackPush16($value)
    {
        $this->stackPush8(($value & 0xFF00) >> 8);
        $this->stackPush8(($value & 0xFF));
    }

    /**
     * @return int
     */
    public function stackPop8()
    {
        $this->updateStackPointer(1);
        $value = $this->memory->read8(Memory::STACK_PAGE_ADDR + $this->s);


        return $value;
    }

    /**
     * @return int
     */
    public function stackPop16()
    {
        return $this->stackPop8() + ($this->stackPop8() << 8);
    }


    /*
     * Read / Write internal CPU registers
     */

    function readX() {
        return $this->x;
    }

    function writeX($x) {
        assert($x <= 0xFF);
        $this->x = $x;
    }

    function readY() {
        return $this->y;
    }

    function writeY($y) {
        assert($y <= 0xFF);
        $this->y = $y;
    }
    function readA() {
        return $this->a;
    }

    function writeA($a) {
        assert($a <= 0xFF);
        $this->a = $a;
    }

    function readP() {
        // We must be sure that the reserved is set to 1. (just to make sure: we also set it through flag_set() and through writeP())
        return ($this->p | (1 << self::P_FLAG_RESERVED));
    }

    function writeP($p) {
        assert($p <= 0xFF);

        // We set P manually, so we must be sure that the reserved is set to 1
        $this->p = $p | (1 << self::P_FLAG_RESERVED);
    }

    function readS() {
        return $this->s;
    }

    function writeS($s) {
        assert($s <= 0xFF);
        $this->s = $s;
    }

    function readPc() {
        return $this->pc;
    }

    function writePc($pc) {
        // PC is 16 bits
        assert($pc <= 0xFFFF);

        $this->pc = $pc;
    }

}
