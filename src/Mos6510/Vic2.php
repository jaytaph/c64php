<?php

namespace Mos6510;

use Mos6510\Io\IoInterface;
use Mos6510\Logging\LoggerInterface;

/**
 * VIC-ii video emulator. Will need some kind of IO interface/class to communicate with the real world.
 */

class Vic2
{

    const COLOR_RAM_ADDRESS = 0xD800;
    const SPRITE_VECTOR_OFFSET = 0x3F8;

    /** @var Cpu */
    protected $cpu;

    /** @var Cia2 */
    protected $cia2;

    /** @var Memory */
    protected $memory;

    /** @var int offset of vic in memory */
    protected $memory_offset;

    /** @var LoggerInterface */
    protected $logger;

    /** @var IoInterface */
    protected $io;



    protected $cr1;         // Screen control register 1
    protected $cr2;         // Screen control register 2

    protected $raster_line = 0;                 // Current raster line we are rendering
    protected $raster_line_interrupt = 0;       // Trigger interrupt on this raster line (if enabled)

    protected $sprite_enable = 0;

    protected $memory_setup = 0;

    // IRQ
    protected $interrupt_status = 0;            // IRQ status (which vic IRQs have been triggered)
    protected $interrupt_control = 0;           // Which IRQs will be triggered by VIC

    // Sprites
    // Sprite X offsets (uses 9 bits each for 0-320)
    protected $sprite_x = array(0, 0, 0, 0, 0, 0, 0, 0);
    // Sprite Y offsets (uses 8 bits for 0-200)
    protected $sprite_y = array(0, 0, 0, 0, 0, 0, 0, 0);
    protected $sprite_priority = 0;
    protected $sprite_multicolor = 0;
    protected $sprite_double_width = 0;
    protected $sprite_double_height = 0;
    protected $sprite_collision_sprite = 0;                         // Sprites collides with other sprites
    protected $sprite_collision_background = 0;                     // Sprites collides with background
    protected $sprite_extra_color = array(0, 0);                    // Two extra colors for multicolor sprites
    protected $sprite_colors = array(0, 0, 0, 0, 0, 0, 0, 0);       // Main colors of sprites
    protected $background_color = array(0, 0, 0, 0);                // Background colors

    protected $border_color = 0;            // Default border color

    protected $graphics_mode = 0;       // Actual screen mode (0-7)
    protected $screen_enabled = 0;      // Screen is enabled

    // At which pixel are we currently drawing onto the monitor? (0 - 157248). Includes HBLANK and VLANK regions as well
    protected $raster_beam = 0;

    // Framebuffer that holds the actual plotted screen colors
    protected $buffer;

    // Time in microseconds when a raster is started (used for timing / debug purposes)
    protected $raster_start = 0;


    /**
     * @param C64 $c64
     * @param $memory_offset
     * @param IoInterface $io
     * @param LoggerInterface $logger
     */
    public function __construct(C64 $c64, $memory_offset, IoInterface $io, LoggerInterface $logger)
    {
        $this->buffer = str_repeat(chr(0), 404 * 312);

        $this->cpu = $c64->getCpu();
        $this->memory = $c64->getMemory();
        $this->cia2 = $c64->getCia2();      // Only needed to fetch VIC bank address

        $this->memory_offset = $memory_offset;
        $this->io = $io;
        $this->logger = $logger;
    }

    /**
     * Read VIC I/O
     *
     * @param $location
     * @return int
     */
    public function readIo($location) {
        $value = 0;

        $address = $location - $this->memory_offset;
        $address %= 0x40; // Make sure we always use 0xD000-0xD03F. Vic2 repeats every 0x40 bytes

        switch ($address) {
            case 0x00 :
            case 0x02 :
            case 0x04 :
            case 0x06 :
            case 0x08 :
            case 0x0A :
            case 0x0C :
            case 0x0E :
                $sprite_offset = $address / 2;
                $value = $this->sprite_x[(int)$sprite_offset] & 0xFF;
                break;
            case 0x01 :
            case 0x03 :
            case 0x05 :
            case 0x07 :
            case 0x09 :
            case 0x0B :
            case 0x0D :
            case 0x0F :
                $sprite_offset = ($address - 1) / 2;
                $value = $this->sprite_y[(int)$sprite_offset] & 0xFF;
                break;
            case 0x10 :
                // Set bit I to 8th bit of sprite X offset
                for ($i=0; $i!=8; $i++) {
                    $value = Utils::bit_set($value, $i, Utils::bit_get($this->sprite_x[$i], 8));
                }
                break;
            case 0x11:
                $value = $this->cr1;
                break;
            case 0x12:
                $value = $this->raster_line;
                break;
            case 0x13:
            case 0x14:
                // Light pen is not supported
                $value = 0;
                break;
            case 0x15:
                $value = $this->sprite_enable;
                break;
            case 0x16:
                $value = $this->cr2;
                break;
            case 0x17:
                $value = $this->sprite_double_height;
                break;
            case 0x18:
                $value = $this->memory_setup;
                break;
            case 0x19:
                $value = $this->interrupt_status;
                if (($value & 0x0F) > 0) {
                    // At least one IRQ is pending, so we need to set bit 7 as well
                    $value = Utils::bit_set($value, 7, 1);
                }
                $value |= 0x70; // These bits are always set to 1 (unused though)
                break;
            case 0x1A:
                $value = $this->interrupt_control;
                break;
            case 0x1B:
                $value = $this->sprite_priority;
                break;
            case 0x1C:
                $value = $this->sprite_multicolor;
                break;
            case 0x1D:
                $value = $this->sprite_double_width;
                break;
            case 0x1E:
                $value = $this->sprite_collision_sprite;
                break;
            case 0x1F:
                $value = $this->sprite_collision_background;
                break;
            case 0x20:
                $value = $this->border_color;
                break;
            case 0x21:
            case 0x22:
            case 0x23:
            case 0x24:
                $value = $this->background_color[$address - 0x21];
                break;
            case 0x25:
            case 0x26:
                $value = $this->sprite_extra_color[$address - 0x25];
                break;
            case 0x27:
            case 0x28:
            case 0x29:
            case 0x2A:
            case 0x2B:
            case 0x2C:
            case 0x2D:
            case 0x2E:
                $sprite_offset = ($address - 0x27);
                $value = $this->sprite_colors[$sprite_offset];
                break;
            default:
                // Unused
                $value = 0;
                break;
        }
        return $value;
    }

    /**
     * Write VIC I/O
     *
     * @param $location
     * @param $value
     */
    public function writeIo($location, $value) {
        $address = $location - $this->memory_offset;
        $address %= 0x40; // Make sure we always use 0xD000-0xD03F. Vic2 repeats every 0x40 bytes

        $this->logger->debug(sprintf("VIC2: Writing %02X to %04X\n", $value, $location));

        switch ($address) {
            case 0x00 :
            case 0x02 :
            case 0x04 :
            case 0x06 :
            case 0x08 :
            case 0x0A :
            case 0x0C :
            case 0x0E :
                $sprite_offset = (int)($address / 2);

                // Make sure we mask off bit 9. It must not change.
                $v = $this->sprite_x[$sprite_offset] & 0x100;
                $v |= ($value & 0xFF);
                $this->sprite_x[$sprite_offset] = $v;
                break;
            case 0x01 :
            case 0x03 :
            case 0x05 :
            case 0x07 :
            case 0x09 :
            case 0x0B :
            case 0x0D :
            case 0x0F :
                $sprite_offset = ($address - 1) / 2;
                $this->sprite_y[(int)$sprite_offset] = ($value & 0xFF);
                break;
            case 0x10 :
                // Set bit I to 8th bit of sprite X offset
                for ($i=0; $i!=8; $i++) {
                    $bit = Utils::bit_get($value, $i);
                    $this->sprite_x[$i] = Utils::bit_set($this->sprite_x[$i], 8, $bit);
                }
                break;
            case 0x11 :
                $this->cr1 = $value;
                $this->updateVideoSettings();
                break;
            case 0x12:
                $this->raster_line_interrupt = $value;
                break;
            case 0x13:
            case 0x14:
                // Read only addresses for light pen
                break;
            case 0x15:
                $this->sprite_enable = $value;
                break;
            case 0x16 :
                $this->cr2 = $value;
                $this->updateVideoSettings();
                break;
            case 0x17:
                $this->sprite_double_height = $value;
                break;
            case 0x18:
                $this->memory_setup = $value;
                break;
            case 0x19:
                // Acknowledge the given interrupts
                $this->interrupt_status &= ~($value & 0x0F);
                break;
            case 0x1A:
                $this->interrupt_control = $value;
                break;
            case 0x1B:
                $this->sprite_priority = $value;
                break;
            case 0x1C:
                $this->sprite_multicolor = $value;
                break;
            case 0x1D:
                $this->sprite_double_width = $value;
                break;
            case 0x1E:
                // @TODO: Are these to write?
                break;
            case 0x1F:
                // @TODO: Are these to write?
                break;
            case 0x20:
                $this->border_color = ($value & 0x0F);
                break;
            case 0x21:
            case 0x22:
            case 0x23:
            case 0x24:
                $this->background_color[$address - 0x21] = ($value & 0x0F);
                break;
            case 0x25:
            case 0x26:
                $this->sprite_extra_color[$address - 0x25] = ($value & 0x0F);
                break;
            case 0x27:
            case 0x28:
            case 0x29:
            case 0x2A:
            case 0x2B:
            case 0x2C:
            case 0x2D:
            case 0x2E:
                $this->sprite_colors[$address - 0x27] = $value;
                break;
            default:
                // Other address are unused
                break;
        }
    }

    /**
     * Single VIC cycle
     */
    public function cycle() {
        // Are there still unacknowledged interrupts pending?
        if (Utils::bit_test($this->interrupt_status, 7)) {
            // Trigger IRQ when enabled
            if (! $this->cpu->flagIsSet(Cpu::P_FLAG_IRQ_DISABLE)) {
                $this->cpu->triggerIrq();
            }
            return;
        }

        // Iterate 8 times (so we get 8 VIC cycles on each cycle)
        for ($i=0; $i!=64; $i++) {

            // X / Y coordinates are starting from the top left HBLANK (404x312)
            $x = $this->raster_beam % 404;
            $y = ($this->raster_beam - $x) / 404;

            // Check if X and Y are in non-blank region
            if ($x > 2 && $y > 7 && $y <= 7 + 43 + 200 + 49) {

                // X / Y coordinates are now starting from the top left border (402x292)
                $x -= 2;
                $y -= 7;

                if (($x > 46 && $x <= 46 + 320 && $y > 43 && $y <= 43 + 200) && $this->screen_enabled) {

                    // Set current raster line
                    $this->raster_line = $y - 43;

                    // Find the exact pixel color for the given position (based on screenmode, sprites, textdata etc)
                    $p = $this->findPixelToRender($x - 46, $y - 43);
                } else {
                    // Write border to monitor
                    $p = $this->border_color;
                }

                // Check sprites and change pixel color if overlapped by any sprite(s)
                $p = $this->handleSprites($x - 20, $y + 7, $p);

                // Write pixel to buffer
                $this->buffer[$y * 402 + $x] = chr($p);

            } else {
                // HBLANK or VBLANK
            }

            // Move raster beam
            $this->raster_beam++;

            // Check raster line interrupt and trigger if needed
            if ($this->raster_line == $this->raster_line_interrupt && Utils::bit_test($this->interrupt_control, 0)) {
                // Set interrupt status to display that we are issuing a raster interrupt IRQ
                $this->interrupt_status = Utils::bit_set($this->interrupt_status, 0, 1);

                $this->cpu->triggerIrq();
                return;
            }


            // Reset raster beam when we reached end of screen
            if ($this->raster_beam >= (404 * 312)) {
                $this->io->writeMonitorBuffer($this->buffer);

                // Go back to left top corner
                $this->raster_beam = 0;

                // Should this also be reset here?
                $this->raster_line = 0;

                $diff = microtime(true) - $this->raster_start;
//                print "Raster done within $diff \n";
                $this->raster_start = microtime(true);
            }
        }

    }

    /**
     * Changes the graphics mode depending on the cr1 and cr2 registers
     */
    protected function updateVideoSettings() {
        // Screen enabled or not
        $this->screen_enabled = Utils::bit_test($this->cr1, 4);

        // Set graphics mode between 0-7
        $bmm = Utils::bit_get($this->cr1, 5); // bitmap mode
        $ecm = Utils::bit_get($this->cr1, 6); // extended background mode
        $mcm = Utils::bit_get($this->cr2, 4); // multicolor mode

        $this->graphics_mode = ($bmm << 2 | $ecm << 1 | $mcm);
        $this->logger->debug(sprintf("Setting VIC2 graphics mode to %02X\n", $this->graphics_mode));
    }

    /**
     * Returns the actual color of the given X Y coordinate from the screen (excluding border, only 320x200)
     *
     * @param $x
     * @param $y
     * @return int
     */
    protected function findPixelToRender($x, $y)
    {
        // We assume standard character mode
        switch ($this->graphics_mode) {
            case 0 :
                // Standard character mode
                $c = $this->standardCharacterMode($x, $y);
                break;
            case 1:
                // Multicolor character mode
                $c = $this->multiColorCharacterMode($x, $y);
                break;
            case 2:
                // Standard bitmap mode
                $c = 0;
                // @TODO: Not implemented yet
                break;
            case 3:
                // Multicolor bitmap mode
                $c = 0;
                // @TODO: Not implemented yet
                break;
            case 4:
                // Extended background color character mode
                $c = $this->extendedCharacterMode($x, $y);
                break;
            case 5:
                // Extended background color multicolor character mode
                // Do not use
                $c = 0;
                break;
            case 6:
                // Extended background color standard bitmap mode
                // Do not use
                $c = 0;
                break;
            case 7:
                // Extended background color multicolor bitmap bitmap mode
                // Do not use
                $c = 0;
                break;

            default :
                $c = 0;

        }

        return ($c & 0x0F);
    }

    /**
     * Returns offset of the currently configured screen memory
     *
     * @return int
     */
    protected function getScreenMemoryOffset() {
        $bank_offset = $this->cia2->getVicBank() * 0x4000;

        // This is where the memory of the screen resides
        $screenmem_offset = ($this->memory_setup >> 4) & 0x0F;
        $screenmem_offset *= 0x400;
        $screenmem_offset += $bank_offset;

        return $screenmem_offset;
    }

    /**
     * Returns offset of the currently configured bitmap memory
     *
     * @return int
     */
    protected function getBitmapMemoryOffset() {
        $bank_offset = $this->cia2->getVicBank() * 0x4000;

        $bitmapmem_offset = ($this->memory_setup >> 3) & 0x01;
        $bitmapmem_offset *= 0x2000;
        $bitmapmem_offset += $bank_offset;

        return $bitmapmem_offset;
    }

    /**
     * Returns offset of the currently configured character memory
     * @return int
     */
    protected function getCharMemoryOffset() {
        $bank_offset = $this->cia2->getVicBank() * 0x4000;

        $charmem_offset = ($this->memory_setup >> 1) & 0x07;
        $charmem_offset *= 0x800;
        $charmem_offset += $bank_offset;

        return $charmem_offset;
    }

    protected function getSpriteShapeOffset($sprite_idx) {
        $bank_offset = $this->cia2->getVicBank() * 0x4000;

        // Fetch the "index" of the sprite spape (0-255)
        $location = $this->getScreenMemoryOffset() + self::SPRITE_VECTOR_OFFSET + $sprite_idx;
        $index = $this->memory->read8($location);

        return $bank_offset + ($index * 64);
    }

    /**
     * Reads a color from color ram at specified index
     *
     * @param $index
     * @return int
     */
    protected function readFromColorRam($index) {
        return $this->memory->read8ram(self::COLOR_RAM_ADDRESS + $index) & 0x0F;
    }

    /**
     * Reads a single bitmap line (8 bits) from a given petscii code. By reading
     * 8 lines (0-7), you retrieve a complete 8x8 bitmap of a single character.
     *
     * @param int $petscii_code
     * @param int $line Line number to read (between 0 and 7)
     * @return int
     */
    protected function readCharBitmapLine($petscii_code, $line) {
        // Find the location of the start of the character in the character memory
        $location = $this->getCharMemoryOffset() + ($petscii_code * 8) + ($line % 8);

        // This is tricky: 0x1000-0x1FFFF and 0x9000-0x9FFF are actually hardwired by
        // the VIC to read from ROM instead of RAM

        $readFromRom = false;
        if ($location >= 0x1000 and $location <= 0x1FFF) {
            $readFromRom = true;
        }
        if ($location >= 0x9000 and $location <= 0x9FFF) {
            $readFromRom = true;
        }

        if ($readFromRom) {
            // ROM starts at 0xD000 (actually read from $this->memory_offset)
            $o = (($location % 0x7FFF) - 0x1000);
            $bitmap_line = $this->memory->read8rom($this->memory_offset + $o);
        } else {
            // Everything else we can simply retrieve directly from RAM
            $bitmap_line = $this->memory->read8ram($location);
        }

        return $bitmap_line;
    }

    /**
     * Handle sprites and returns a specific color that is needed on the given position. Also deals
     * with things like collisions etc.
     *
     * @param $x
     * @param $y
     * @param $default_color int The current color when nothing is needed to be plotted (or when the color is transparant)
     * @return array|mixed
     */
    protected function handleSprites($x, $y, $default_color)
    {
        // Assume default / transparant color
        $color = $default_color;

        // We start painting sprite 7 first, up to sprite 0, which has the highest priority
        for ($i = 7; $i >= 0; $i--) {
            // Only handle sprite when it's enabled
            if (Utils::bit_test($this->sprite_enable, $i)) {
                $color = $this->handleSprite($i, $x, $y, $color);
            }
        }

        return $color;
    }

    /**
     * Handle a single sprite
     *
     * @param $sprite_idx
     * @param $x
     * @param $y
     * @param $color
     */
    protected function handleSprite($sprite_idx, $x, $y, $color) {
        // Fetch location of the sprite
        $location = $this->getSpriteShapeOffset($sprite_idx);

        $x_coord = $this->sprite_x[$sprite_idx];
        $y_coord = $this->sprite_y[$sprite_idx];

        // @TODO: Double width / height
        $size_x = 24;
        $size_y = 21;

        if ($x >= $x_coord && $x < ($x_coord + $size_x) &&
            $y >= $y_coord && $y < ($y_coord + $size_y)) {
            // X Y falls into the coords of the current sprite

            $x_off = ($x - $x_coord);
            $y_off = ($y - $y_coord);

            $offset = ($y_off * 24) + $x_off;

            $byte_offset = ($offset >> 3);
            $bit_offset = 7 - ($offset % 8);

            $value = $this->memory->read8($location + $byte_offset);

            if (Utils::bit_test($this->sprite_multicolor, $sprite_idx)) {
                // Multicolor sprite
                $bit_offset >>= 1;
                $bit_offset *= 2;

                switch (($value >> $bit_offset) & 0x03) {
                    case 0:
                        // Color is the current default color
                        break;
                    case 1:
                        $color = $this->sprite_extra_color[0];
                        break;
                    case 2:
                        // Note that 2 (bit pair 10) will use sprite colors. This is
                        // different than other modes that use multicolor.
                        $color = $this->sprite_colors[$sprite_idx];
                        break;
                    case 3:
                        $color = $this->sprite_extra_color[1];
                        break;
                }
            } else {
                if (Utils::bit_get($value, $bit_offset)) {
                    $color = $this->sprite_colors[$sprite_idx];
                }
            }
        }

        return $color;
    }

    /**
     * Plots pixel in standard character mode
     *
     * @param $x
     * @param $y
     * @return int
     */
    protected function standardCharacterMode($x, $y) {
        $color = 0;

        // Find the actual char for pixel $x, $y
        $char_index = floor($y / 8) * 40 + floor($x / 8);

        // Read the given character from the screen memory
        $char = $this->memory->read8($this->getScreenMemoryOffset() + $char_index);

        // Find the start of the matching bitmap inside the character ROM
        $char_bitmap_line = $this->readCharBitmapLine($char, ($y % 8));

        $pixel = Utils::bit_get($char_bitmap_line, 7 - ($x % 8));

        switch ($pixel) {
            case 0:
                // Pixel turned off. Use background color
                $color = $this->background_color[0];
                break;
            case 1:
                // Read from color RAM
                $color = $this->readFromColorRam($char_index);
                break;
        }

        return ($color & 0x0F);
    }

    /**
     * Plots pixel in multicolor character mode
     * @param $x
     * @param $y
     * @return int
     */
    protected function multiColorCharacterMode($x, $y) {
        // Find the actual char for pixel $x, $y
        $char_index = floor($y / 8) * 40 + floor($x / 8);

        // If bit 3 of the color of the given character to plot is 0, plot it as
        // a standard character. This way we can actually "mix" multicolor and
        // standard characters, based on their color values.
        $color = $this->readFromColorRam($char_index);
        if (Utils::bit_test($color, 3) == 0) {
            return $this->standardCharacterMode($x, $y);
        }

        // Read the given character from the screen memory
        $char = $this->memory->read8($this->getScreenMemoryOffset() + $char_index);

        // Find the start of the matching bitmap inside the character ROM
        $char_bitmap_line = $this->readCharBitmapLine($char, ($y % 8));

        // Fetch two pixels from the bitmap (as 33221100 instead of the regular 76543210)
        $o = $x % 8;
        $o = (7 - $o);
        $o >>= 1;
        $pixel = ($char_bitmap_line >> ($o * 2)) & 0x03;

        switch ($pixel) {
            case 0:
            case 1:
            case 2:
                $color = $this->background_color[$pixel];
                break;
            case 3:
                // Color is actually lowest 2 bytes of color ram
                $color = ($color & 0x07);
                break;
        }

        return ($color & 0x0F);
    }

    /**
     * Plots pixel in extended character mode
     *
     * @param $x
     * @param $y
     * @return int
     */
    protected function extendedCharacterMode($x, $y) {
        $color = 0;

        // Find the actual char for pixel $x, $y
        $char_index = floor($y / 8) * 40 + floor($x / 8);

        // Read the given character from the screen memory
        $char = $this->memory->read8($this->getScreenMemoryOffset() + $char_index);

        // Find the start of the matching bitmap inside the character ROM. Only the first 64 chars are available!
        $char_bitmap_line = $this->readCharBitmapLine(($char & 0x3F), ($y % 8));

        $pixel = Utils::bit_get($char_bitmap_line, 7 - ($x % 8));
        switch ($pixel) {
            case 0:
                // Color depends on bit 6-7 from the actual character (hence only 64 are available)
                $color = $this->background_color[($char >> 6) & 0x03];
                break;
            case 1:
                // Read from color RAM
                $color = $this->readFromColorRam($char_index);
                break;
        }

        return ($color & 0x0F);
    }

}
