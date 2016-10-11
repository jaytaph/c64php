<?php

namespace Mos6510;

class Utils {

    /**
     * BCD to binary decimal   (0064 => 0040 (64 decimal))
     *
     * @param $value
     * @return float
     */
    static function bcd2dec($value) {
        return ( ( $value / 16 * 10) + ($value % 16) );
    }

    /**
     * Decimal to BCD
     *
     * @param $value
     * @return int
     */
    static function dec2bcd($value) {
        return ( (int)($value / 10) * 16 ) + ($value % 10);
    }

    /**
     * Sets / unset specified bit and returns new value
     *
     * @param $original
     * @param $bit_position
     * @param $bit_value
     * @return int
     */
    static function bit_set($original, $bit_position, $bit_value) {
        // Make sure bit is either 0 or 1
        $bit_value = ($bit_value) ? 1 : 0;

        if ($bit_value) {
            // Set the bit
            return $original | (1 << $bit_position);
        }

        // Unset the bit
        return $original & ~(1 << $bit_position);
    }

    /**
     * Tests for specific bit. Returns true when set, false when not set.
     *
     * @param $value
     * @param $bit_position
     * @return bool
     */
    static function bit_test($value, $bit_position) {
        return ((($value >> $bit_position) & 0x01) == 0x01);
    }

}
