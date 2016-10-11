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

    public function read8($location) {
        $value = 0;

        // Locations are repeated every 16 bytes
        switch (($location - $this->memory_offset) & 0x0F) {
            case 0:
                // Port A data lines
                break;
            case 1:
                // Port B data lines
                break;
            case 13:
                // Data direction port A
                break;
            default:
                // Other locations are just like CIA1
                $value = parent::read8($location);
                break;
        }

        return $value;
    }

    public function write8($location, $value) {
        // Locations are repeated every 16 bytes
        switch (($location - $this->memory_offset) & 0x0F) {
            case 0:
                break;
            case 1:
                break;
            case 13:
                break;
            default:
                // Other locations are just like CIA1
                parent::write8($location, $value);
                break;
        }
    }

    public function cycle() {
//        parent::cycle();
    }

}
