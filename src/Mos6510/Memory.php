<?php

namespace Mos6510;

use Mos6510\Logging\LoggerInterface;

/*
 * C64 memory is a bit complex.
 * It uses 64K of RAM memory, but some of the addresses are used by ROM and/or IO as well.
 * These address blocks called "banks" can be set to either RAM, ROM or IO.
 *
 * When set to ROM, it will read from the ROM mapped on top (like BASIC or KERNAL rom).
 * When set to ROM, it will always write to underlying RAM
 * When set to RAM, it will always read and write to RAM
 * When set to IO, it will read and write to IO ports (mostly CIA1, CIA2 and VIC).
 *
 * What each block is and does, can be set by memory location 0x0001
 */

class Memory {

    const MEMORY_SIZE = 0x10000; // 64K

    const ZERO_PAGE_ADDR    = 0x0000;       // Zero page address
    const STACK_PAGE_ADDR   = 0x0100;       // Stack page address

    const WRAP_BOUNDARY = true;
    const NOWRAP_BOUNDARY = false;

    const WRITE_DIRECT_RAM = false;            // When we need to read/write memory directly from RAM (without IO interfering)
    const WRITE_WITH_IO = true;                // When we need to read/write memory or IO, depending on the bank configuration (default)

    /* Complete memory map for RAM */
    protected $ram = array();

    /* Complete memory map for ROM, note that not all the memory is mapped in this ROM */
    protected $rom = array();

    /* Each bank is 8K and can be RAM,ROM or IO. Not all banks can be changed, just 3, 5 and 6 */
    protected $bank = array();

    // Banks and bank modes
    const BANK_BASIC    = 3;
    const BANK_CHAR     = 5;
    const BANK_KERNAL   = 6;

    const BANKMODE_RAM = 0;     // Bank is RAM
    const BANKMODE_ROM = 1;     // Bank is ROM
    const BANKMODE_IO  = 2;     // Bank is IO

    // Default memory addresses
    const BASIC_ADDR        = 0xA000;   // Basic rom page start
    const CHARACTER_ADDR    = 0xD000;   // Vic-ii ROM address
    const KERNAL_ADDR       = 0xE000;   // Kernal rom page start

    // It's hard to programmatically calculate bank modes based on the given bits. We use a lookup table instead
    protected $presetBanks = array(
        7 => array(self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_ROM, self::BANKMODE_ROM, self::BANKMODE_RAM, self::BANKMODE_IO , self::BANKMODE_ROM),
        6 => array(self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_ROM, self::BANKMODE_RAM, self::BANKMODE_IO , self::BANKMODE_ROM),
        5 => array(self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_IO , self::BANKMODE_RAM),
        4 => array(self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_RAM),
        3 => array(self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_ROM, self::BANKMODE_ROM, self::BANKMODE_RAM, self::BANKMODE_ROM, self::BANKMODE_ROM),
        2 => array(self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_ROM, self::BANKMODE_RAM, self::BANKMODE_ROM, self::BANKMODE_ROM),
        1 => array(self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_RAM),
        0 => array(self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_RAM, self::BANKMODE_RAM),
    );


    /** @var LoggerInterface */
    protected $logger;

    /** @var C64 */
    protected $c64;

    public function __construct(C64 $c64, LoggerInterface $logger) {
        $this->logger = $logger;
        $this->c64 = $c64;

        // Initialize RAM and ROM to zero
        for ($i=0; $i!=self::MEMORY_SIZE; $i++) {
            $this->ram[$i] = (int)0;
            $this->rom[$i] = (int)0;
        }

        // Default all banks to RAM
        for ($i=0; $i!=7; $i++) {
            $this->bank[$i] = self::BANKMODE_RAM;
        }

        // Load ROMs at certain locations
        $this->loadRom("./rom/64c.251913-01.bin",    0, 8192, self::BASIC_ADDR);
        $this->loadRom("./rom/64c.251913-01.bin", 8192, 8192, self::KERNAL_ADDR);
        $this->loadRom("./rom/character-rom.bin",    0, 4096, self::CHARACTER_ADDR);

        // Processor port data register.
        $this->write8(0x0000, 0x2F, self::WRITE_DIRECT_RAM);

        // Setup default banks
        $this->setupMemoryBankConfiguration(0x37);
    }


    /**
     * @param $value
     */
    protected function setupMemoryBankConfiguration($value) {
        // It's a bit hard to program which banks are which modes for which value. We use a lookup table instead.
        // Note that we only use the CHAREN, HIRAM, LORAM. But we should also check GAME and EXROM (if available).
        // This gives a total of 32 different bank modes. We use only the first 7 and assume GAME and EXROM to be 0.
        $this->bank = $this->presetBanks[($value & 0x07)];

        // Write bank value directly to RAM
        $this->write8(0x0001, $value, self::WRITE_DIRECT_RAM);
    }


    /**
     * Load ROM file
     *
     * @param $rom_file
     * @param $file_offset
     * @param $length
     * @param $memory_offset
     * @throws \Exception
     */
    public function loadRom($rom_file, $file_offset, $length, $memory_offset) {
        $f = fopen($rom_file, "r");
        if (! $f) {
            throw new \Exception("Cannot load ROM file: ".$rom_file);
        }

        fseek($f, $file_offset, SEEK_SET);
        $rom = fread($f, $length);

        for ($i=0; $i!=$length; $i++) {
            $this->rom[$memory_offset + $i] = ord($rom[$i]);
        }

        fclose($f);
    }

    /**
        * Load RAM file
        *
        * @param $rom_file
        * @param $file_offset
        * @param $length
        * @param $memory_offset
        * @throws \Exception
        */
    public function loadRam($rom_file, $file_offset, $length, $memory_offset) {
        $f = fopen($rom_file, "r");
        if (! $f) {
            throw new \Exception("Cannot load RAM file: ".$rom_file);
        }

        fseek($f, $file_offset, SEEK_SET);
        $rom = fread($f, $length);

        for ($i=0; $i!=$length; $i++) {
            $this->ram[$memory_offset + $i] = ord($rom[$i]);
        }

        fclose($f);
    }

    /**
     * Reads directly from RAM (even when mapped by ROM or IO by the memory bank configuration)
     */
    public function read8ram($location) {
        return $this->ram[$location];
    }

    /**
     * Reads directly from ROM (even when mapped by RAM or IO by the memory bank configuration)
     */
    public function read8rom($location) {
        return $this->rom[$location];
    }


    /**
     * Read a single memory location, based on configuration
     *
     * @param $location
     * @return int
     */
    public function read8($location)
    {
        // Read from VIC IO or CHAR
        if ($location >= 0xD000 && $location <= 0xD3FF) {
            if ($this->bank[self::BANK_CHAR] == self::BANKMODE_IO) {
                // read from vic IO
                return $this->c64->getVic2()->read8($location);

            } else if ($this->bank[self::BANK_CHAR] == self::BANKMODE_ROM) {
                // read from rom
                return $this->rom[$location];
            } else {
                return $this->ram[$location];
            }
        }

        // Read from CIA1
        if ($location >= 0xDC00 && $location <= 0xDCFF) {
            if ($this->bank[self::BANK_CHAR] == self::BANKMODE_IO) {
                // read from cia register
                return $this->c64->getCia1()->read8($location);
            } else {
                return $this->ram[$location];
            }
        }

        // Read from CIA2
        if ($location >= 0xDD00 && $location <= 0xDDFF) {
            if ($this->bank[self::BANK_CHAR] == self::BANKMODE_IO) {
                // read from cia register
                return $this->c64->getCia2()->read8($location);
            } else {
                return $this->ram[$location];
            }
        }

        // Read from BASIC
        if ($location >= 0xA000 && $location <= 0xBFFF) {
            if ($this->bank[self::BANK_BASIC] == self::BANKMODE_ROM) {
                return $this->rom[$location];
            } else {
                return $this->ram[$location];
            }
        }

        // Read from Kernal
        if ($location >= 0xE000 && $location <= 0xFFFF) {
            if ($this->bank[self::BANK_KERNAL] == self::BANKMODE_ROM) {
                return $this->rom[$location];
            } else {
                return $this->ram[$location];
            }
        }

        // Default
        return $this->ram[$location];
    }

    /**
     * Read 16 bits by using reading directly from two memory locations
     *
     * @param $location
     * @param bool $wrap_boundary
     * @return int
     */
    public function read16($location, $wrap_boundary = self::NOWRAP_BOUNDARY) {

        // When we wrap boundary, it means that reading from 0x30FF means reading
        // from 0x30FF + 0x3000 instead of 0x30FF + 0x3100
        if ($wrap_boundary == self::WRAP_BOUNDARY && (($location & 0xFF) == 0xFF)) {
            return ($this->read8($location - 255) << 8) + $this->read8($location);
        }

        // MSB instead of LSB!
        return ($this->read8($location + 1) << 8) + $this->read8($location);
    }

    /**
     * Write memory. This could either be done to RAM, or to IO when a bank is set to IO-mode.
     *
     * @param $location
     * @param $value
     * @param bool $write_mode When false, we write directly to RAM, even when bank is set to IO
     */
    public function write8($location, $value, $write_mode = self::WRITE_WITH_IO)
    {
        assert($value <= 0xFF);

        // Do not use IO when writing directly to RAM
        if ($write_mode == self::WRITE_DIRECT_RAM) {
            $this->ram[$location] = $value;
            return;
        }

        // A write could be done to either RAM or IO. We need to check banks modes and pass it to
        // the correct handler (ie: 0xd000-0xd3ff IO is dealt with by the vic2 for instance)

        if ($location == 0x0001) {
            // Zero page 0001 location: change bank configuration
            $this->setupMemoryBankConfiguration($value);

        } else if ($location >= 0xD000 && $location <= 0xD3FF) {
            // vic write
            if ($this->bank[self::BANK_CHAR] == self::BANKMODE_IO) {
                // write IO
                $this->c64->getVic2()->write8($location, $value);
            } else {
                $this->ram[$location] = $value;
            }

        } else if ($location >= 0xDC00 && $location <= 0xDCFF) {
            // cia1 write
            if ($this->bank[self::BANK_CHAR] == self::BANKMODE_IO) {
                // write IO
                $this->c64->getCia1()->write8($location, $value);
            } else {
                $this->ram[$location] = $value;
            }

        } else if ($location >= 0xDD00 && $location <= 0xDDFF) {
            // cia2 write
            if ($this->bank[self::BANK_CHAR] == self::BANKMODE_IO) {
                // write IO
                $this->c64->getCia2()->write8($location, $value);
            } else {
                $this->ram[$location] = $value;
            }

        } else {
            $this->ram[$location] = $value;
        }
    }


    /**
     * Will return true when the location + increment will cross a page boundary. Works with negative numbers too
     *
     * @param $location
     * @param $increment
     * @return bool
     */
    public function willCrossPage($location, $increment) {
        return ($location & 0xFF00) != (($location + $increment) & 0xFF00);
    }

}
