<?php

namespace Mos6510;

/*
 * The Cia2 is almost the same as a Cia1, but just a few pins are different.
 * We simply extend the CIA, and changes a few read/write ports.
 *
 * Controls the NMI instead of the IRQ line
 */

class Cia2 extends Cia1
{

    protected $memory_offset;

    public function readIo($location) {
        $value = 0;

        // Locations are repeated every 16 bytes
        switch (($location - $this->memory_offset) & 0x0F) {
            case 0:
                // Port A data lines
                return $this->getDataPortAValue();
                break;
            case 1:
                // Port B data lines
                return $this->getDataPortBValue();
                break;
            case 13:
                //
                break;
            default:
                // Other locations are just like CIA1
                $value = parent::readIo($location);
                break;
        }

        return $value;
    }

    /**
     * Returns the actual VIC bank (one of the four 16KB blocks of RAM that the VIC can see at the same time).
     *
     * @return int
     */
    public function getVicBank() {
        return 3 - ($this->getDataPortAValue() & 0x03);
    }

    /**
     * @param $location
     * @param $value
     */
    public function writeIo($location, $value) {
        // Locations are repeated every 16 bytes
        switch (($location - $this->memory_offset) & 0x0F) {
            case 13:
                break;
            default:
                // Other locations are just like CIA1
                parent::writeIo($location, $value);
                break;
        }
    }

    public function cycle() {
//        parent::cycle();
    }

}
