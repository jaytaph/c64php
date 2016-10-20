<?php

namespace Mos6510;

use Mos6510\Io\IoInterface;
use Mos6510\Logging\LoggerInterface;

class Cia1
{

    /** @var Cpu */
    protected $cpu;

    /** @var IoInterface */
    protected $io;

    /** @var LoggerInterface */
    protected $logger;

    /** @var int offset of cia in memory */
    protected $memory_offset;

    protected $data_port_a = 0;         // Data ports
    protected $data_port_b = 0;
    protected $data_dir_a = 0;          // Data direction
    protected $data_dir_b = 0;

    protected $timer_a = 0;             // Timer A properties
    protected $timer_a_latch = 0;
    protected $timer_a_started = 0;
    protected $timer_a_underflow = 0;
    protected $timer_a_overflow_mode = 0;
    protected $timer_a_restart_underflow = 0;
    protected $timer_a_control = 0;
    protected $timer_a_count_mode = 0;
    protected $timer_a_irq_enabled = 0;
    protected $timer_a_restart_mode = 0;

    protected $timer_b = 0;             // Timer B properties
    protected $timer_b_latch = 0;
    protected $timer_b_started = 0;
    protected $timer_b_underflow = 0;
    protected $timer_b_overflow_mode = 0;
    protected $timer_b_restart_underflow = 0;
    protected $timer_b_control = 0;
    protected $timer_b_count_mode = 0;
    protected $timer_b_irq_enabled = 0;
    protected $timer_b_restart_mode = 0;

    protected $interrupt_status;
    protected $timer_set_mode = 0;
    protected $ssr_direction = 0;
    protected $rtc_speed = 0;

    protected $rtc_start = 0;   // Time when the RTC has started (actual timestamp of the host)
    protected $rtc = 0;         // Real time clock
    protected $ssr = 0;         // Serial shift register

    // Tick count on the last process cycle. Used for syncing the timers
    protected $previous_tick_count = 0;

    // Real microtime since last cycle (used for syncing to 50/60hz)
    protected $oldtime = 0;


    /**
     * Cia1 constructor.
     * @param C64 $c64
     * @param $memory_offset
     * @param IoInterface $io
     * @param LoggerInterface $logger
     */
    public function __construct(C64 $c64, $memory_offset, IoInterface $io, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->cpu = $c64->getCpu();
        $this->io = $io;
        $this->memory_offset = $memory_offset;

        // Initialize with current timestamp
        $this->rtc = microtime(true);
        $this->rtc_start = microtime(true);
    }

    public function read8($location) {
        $value = 0;

        // Locations are repeated every 16 bytes
        switch (($location - $this->memory_offset) & 0x0F) {
            case 0x00:
                // Port A data lines
                // @TODO: Make sure data port A functions
                $value = 0;
                break;
            case 0x01:
                // Port B data lines
                $matrix = $this->io->readKeyboard();

                // Data port B has two modes: bits for RW and bits for RO. This is based on the data_dir_b entry.

                // Set value for bits set to RW. This is the keyboard matrix
                $rw_value = 0;
                if ($matrix[1] & ~$this->data_port_a) {
                    // Only act on the columns for the data port a, and set the rows taken from data
                    // port b values (note all are inversed bits (1 = don't read, 0 = read :( )
                    $rw_value = ($matrix[0] & ~$this->data_port_b);
                }

                // Set value for bits set to RO bits (joystick, timers)
                $ro_value = 0;
                // @TODO: joystick 1, timerA (bit 6) and timerB (bit 7)


                // Mix value with the RW and RO bits
                $value = 0;
                $value |= (~$rw_value & 0xFF);

                // @TODO: Make sure only RW bits are set based on data_dir_b ??
//                $value |= (~$rw_value & 0xFF) & ~($this->data_dir_b);
                // @TODO Make sure we merge the RO bits as well?
//                $value |= ($ro_value & $this->data_dir_b);

                break;
            case 0x02:
                // Data direction port A
                $value = $this->data_dir_a;
                break;
            case 0x03:
                // Data direction port B
                $value = $this->data_dir_b;
                break;
            case 0x04:
               // value timer A low
                $value = ($this->timer_a_latch & 0x00FF);
                break;
            case 0x05:
               // value timer A high
                $value = ($this->timer_a_latch & 0xFF00) >> 8;
                break;
            case 0x06:
                // value timer B low
                $value = ($this->timer_b_latch & 0x00FF);
                break;
            case 0x07:
                // value timer B high
                $value = ($this->timer_b_latch & 0xFF00) >> 8;
                break;
            case 0x08:
                // Real time clock 1/10s
                $time = microtime(true);
                $microtime = $time - floor($time);
                $microtime *= 10;
                $microtime = (int)($microtime);
                $value = Utils::dec2bcd($microtime);
                break;
            case 0x09:
                // Real time clock sec
                $now = \DateTime::createFromFormat('U', time());
                $value = Utils::dec2bcd($now->format("s"));
                break;
            case 0x0A:
                // Real time clock min
                $now = \DateTime::createFromFormat('U', time());
                $value = Utils::dec2bcd($now->format("m"));
                break;
            case 0x0B:
                // Real time clock hour
                $now = \DateTime::createFromFormat('U', time());
                $value = Utils::dec2bcd($now->format("h"));
                break;
            case 0x0C:
                // Serial shift register
                $value = $this->ssr;
                break;
            case 0x0D:
                // Interrupt status
                $value = $this->interrupt_status;

                // Upon reading the interrupt status, it is cleared.
                $this->interrupt_status = 0;
                break;
            case 0x0E:
                // Control timer A
                $value = 0;
                $value = Utils::bit_set($value, 0, $this->timer_a_started);
                $value = Utils::bit_set($value, 1, 0);      // @TODO: Underflow port B bit 6 ??
                $value = Utils::bit_set($value, 2, 0);      // @TODO: Invert on underflow
                $value = Utils::bit_set($value, 3, $this->timer_a_restart_mode);
                $value = Utils::bit_set($value, 4, 0);
                $value = Utils::bit_set($value, 5, $this->timer_a_count_mode);
                $value = Utils::bit_set($value, 6, $this->ssr_direction);
                $value = Utils::bit_set($value, 7, $this->rtc_speed);
                break;
            case 0x0F:
                // Control timer B
                $value = 0;
                $value = Utils::bit_set($value, 0, $this->timer_b_started);
                $value = Utils::bit_set($value, 1, 0);      // @TODO: Underflow port B bit 7 ??
                $value = Utils::bit_set($value, 2, 0);      // @TODO: Invert on underflow
                $value = Utils::bit_set($value, 3, $this->timer_a_restart_mode);
                $value = Utils::bit_set($value, 4, 0);
                $value = Utils::bit_set($value, 5, ($this->timer_a_count_mode & 0x01));
                $value = Utils::bit_set($value, 6, (($this->timer_a_count_mode & 0x02) >> 1));
                $value = Utils::bit_set($value, 7, $this->timer_set_mode);
                break;
        }

        return $value;
    }



    public function write8($location, $value) {
        // Locations are repeated every 16 bytes
        switch (($location - $this->memory_offset) & 0x0F) {
            case 0x00:
                // Port A data lines
                $this->data_port_a = $value;
                break;
            case 0x01:
                // Port B data lines
                $this->data_port_b = $value;
                break;
            case 0x02:
                // Data direction port A
                $this->data_dir_a = $value;
                break;
            case 0x03:
                // Data direction port B
                $this->data_dir_b = $value;
                break;
            case 0x04:
                // value timer A low
                $this->timer_a = ($this->timer_a & 0xFF00) | $value;
                break;
            case 0x05:
                // value timer A high
                $this->timer_a = ($this->timer_a & 0x00FF) | ($value << 8);
                break;
            case 0x06:
                // value timer B low
                $this->timer_b = ($this->timer_b & 0xFF00) | $value;
                break;
            case 0x07:
                // value timer B high
                $this->timer_b = ($this->timer_b & 0x00FF) | ($value << 8);
                break;
            case 0x08:
                // Real time clock 1/10s
                if ($this->timer_set_mode == 0) {
                    // @TODO
                    $this->logger->warning("Cannot set RTC yet");
                } else {
                    // @TODO
                    $this->logger->warning("Cannot set alarm yet");
                }
                break;
            case 0x09:
                // Real time clock sec
                if ($this->timer_set_mode == 0) {
                    // @TODO
                    $this->logger->warning("Cannot set RTC yet");
                } else {
                    // @TODO
                    $this->logger->warning("Cannot set alarm yet");
                }
                break;
            case 0x0A:
                // Real time clock min
                if ($this->timer_set_mode == 0) {
                    // @TODO
                    $this->logger->warning("Cannot set RTC yet");
                } else {
                    // @TODO
                    $this->logger->warning("Cannot set alarm yet");
                }
                break;
            case 0x0B:
                // Real time clock hour
                if ($this->timer_set_mode == 0) {
                    // @TODO
                    $this->logger->warning("Cannot set RTC yet");
                } else {
                    // @TODO
                    $this->logger->warning("Cannot set alarm yet");
                }
                break;
            case 0x0C:
                // Serial shift register
                $this->logger->warning("Cannot set SSR yet");
                break;
            case 0x0D:
                // Interrupt control
                $bit7 = Utils::bit_test($value, 7);

                if (Utils::bit_test($value, 0)) {
                    $this->timer_a_irq_enabled = $bit7;
                }
                if (Utils::bit_test($value, 1)) {
                    $this->timer_b_irq_enabled = $bit7;
                }

                // @TODO: clock=alarm: bit 2
                // @TODO: complete byte tx/rx bit 3
                // @TODO: positive slope CNT pin bit 4
                break;
            case 0x0E:
                // Control timer A
                $this->timer_a_started = Utils::bit_test($value, 0);
                // @TODO: These are hard
//                    $this->timer_a_underflow = Utils::bit_test($value, 1);
//                    $this->timer_a_overflow_mode = Utils::bit_test($value, 2);
                $this->timer_a_restart_mode = Utils::bit_test($value, 3);
                if (Utils::bit_test($value, 4)) {
                    $this->timer_a = $this->timer_a_latch;
                }
                $this->timer_a_count_mode = Utils::bit_test($value, 5);
                $this->ssr_direction = Utils::bit_test($value, 6);
                $this->rtc_speed = Utils::bit_test($value, 7);
                break;
            case 0x0F:
                // Control timer B
                $this->timer_b_started = Utils::bit_test($value, 0);
                // @TODO: These are hard
//                    $this->timer_b_underflow = Utils::bit_test($value, 1);
//                    $this->timer_b_overflow_mode = Utils::bit_test($value, 2);
                $this->timer_b_restart_mode = Utils::bit_test($value, 3);
                if (Utils::bit_test($value, 4)) {
                    $this->timer_b = $this->timer_b_latch;
                }

                $this->timer_b_count_mode = ($value & 0x60) >> 5;

                $this->timer_set_mode = Utils::bit_test($value, 7);
                break;
        }
    }

    /**
     * This will return true on the given frequency. This means we can use it to "sync" cycles: the cpu can run in
     * a different speed (full speed), while the CIA only fires after 50hz. This will sync the IRQ's a bit better.
     */
    protected function fireAtFreq($freq) {
        $hz = 1 / $freq;

        $diff = microtime(true) - $this->oldtime;
        if ($this->oldtime != 0 && $diff < $hz) {
            return false;
        }

        $this->oldtime = microtime(true);
        return true;
    }


    public function cycle()
    {
        if (! $this->fireAtFreq(50)) {
            // This cia cycle is not yet 50hz old
            return;
        }

        if ($this->timer_a_started) {
            $this->cycleTimerA();
        }

        if ($this->timer_b_started) {
            $this->cycleTimerB();
        }

        $this->previous_tick_count = $this->cpu->getTickCount();

        // @TODO: Set RTC
//        $this->rtc = time();
    }

    protected function cycleTimerA() {
        switch ($this->timer_a_count_mode) {
            case 0 :
                // Counts system cycles

                // Timer A elapsed?
                if ($this->timer_a <= 0) {
                    // Interrupt status: Underflow of timer A occurred
                    $this->interrupt_status = Utils::bit_set($this->interrupt_status, 0, 1);

                    if ($this->timer_a_irq_enabled && $this->cpu->flagIsSet(Cpu::P_FLAG_IRQ_DISABLE) == 0) {
                        // Fire IRQ
                        $this->cpu->triggerIrq();

                        // Interrupt status: IRQ has been triggered
                        $this->interrupt_status = Utils::bit_set($this->interrupt_status, 7, 1);
                    }
                    if ($this->timer_a_restart_mode == 0) {
                        // Reset timer to original latch value
                        $this->timer_a = $this->timer_a_latch;
                    } else {
                        // Disable timer. One time fire only
                        $this->timer_a_started = false;
                    }
                }

                // Decrease timer with the number of cycles since last run
                $ticks = $this->cpu->getTickCount() - $this->previous_tick_count;
                $this->timer_a -= $ticks;

                break;
            case 1 :
                // Counts CNT pin
                $this->logger->warning("CIA timer A uses CNT mode which is not supported");
                break;
        }
    }

    protected function cycleTimerB() {
        switch ($this->timer_b_count_mode) {
            case 0 :
                // Counts system cycles

                // Timer B elapsed?
                if ($this->timer_b <= 0) {
                    // Interrupt status: Underflow of timer B occurred
                    $this->interrupt_status = Utils::bit_set($this->interrupt_status, 1, 1);

                    if ($this->timer_b_irq_enabled && $this->cpu->flagIsSet(Cpu::P_FLAG_IRQ_DISABLE) == 0) {
                        // Fire IRQ if needed
                        $this->cpu->triggerIrq(); // @TODO: NMI?

                        // Interrupt status: IRQ has been triggered
                        $this->interrupt_status = Utils::bit_set($this->interrupt_status, 7, 1);
                    }
                    if ($this->timer_b_restart_mode == 0) {
                        // Reset timer to original latch value
                        $this->timer_b = $this->timer_b_latch;
                    } else {
                        // Disable timer. One time fire only
                        $this->timer_b_started = false;
                    }
                }

                // Decrease timer with the number of cycles since last run
                $c = $this->cpu->getTickCount() - $this->previous_tick_count;
                $this->timer_b -= $c;

                break;
            case 1 :
                // Counts CNT pin
                $this->logger->warning("CIA timer B uses CNT mode which is not supported");
                break;
            case 2 :
                // @TODO: This should be easy enough to implement
                $this->logger->warning("CIA timer B uses underflow timer A which is not supported");
                break;
            case 3 :
                $this->logger->warning("CIA timer B uses underflow timer A on CNT mode which is not supported");
                break;
        }
    }

}
