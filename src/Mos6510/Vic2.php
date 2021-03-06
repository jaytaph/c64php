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

    // At which pixel are we currently drawing onto the monitor? Includes HBLANK and VBLANK regions as well
    protected $raster_beam = 0;

    protected $raster_line = 0;                 // Current raster line we are rendering
    protected $raster_line_interrupt = 0;       // Trigger interrupt on this raster line (if enabled)

    protected $memory_setup = 0;

    // IRQ
    protected $interrupt_status = 0;            // IRQ status (which vic IRQs have been triggered)
    protected $interrupt_control = 0;           // Which IRQs will be triggered by VIC

    // Sprites
    // Sprite X offsets (uses 9 bits each for 0-320)
    protected $sprite_x = array(0, 0, 0, 0, 0, 0, 0, 0);
    // Sprite Y offsets (uses 8 bits for 0-200)
    protected $sprite_y = array(0, 0, 0, 0, 0, 0, 0, 0);
    protected $sprite_enabled = array(false, false, false, false, false, false, false, false);
    protected $sprite_priority = array(false, false, false, false, false, false, false, false);
    protected $sprite_multicolor = array(false, false, false, false, false, false, false, false);
    protected $sprite_double_width = array(false, false, false, false, false, false, false, false);
    protected $sprite_double_height = array(false, false, false, false, false, false, false, false);
    protected $sprite_collision_sprite = 0;                         // Sprites collides with other sprites
    protected $sprite_collision_background = 0;                     // Sprites collides with background
    protected $sprite_extra_color = array(0, 0);                    // Two extra colors for multicolor sprites
    protected $sprite_colors = array(0, 0, 0, 0, 0, 0, 0, 0);       // Main colors of sprites
    protected $background_color = array(0, 0, 0, 0);                // Background colors

    protected $border_color = 0;            // Default border color

    protected $graphics_mode = 0;           // Actual screen mode (0-7)
    protected $screen_enabled = 0;          // Screen is enabled

    // Framebuffer that holds the actual plotted screen colors
    protected $buffer;

    // Precalculated memory offsets
    protected $screenmem_offset = 0;
    protected $bitmapmem_offset = 0;
    protected $charmem_offset = 0;

    protected $cached_vic_bank = -1;        // Holds the current known value of the vic bank from the CIA2.

    // Per raster-scan cache. This CAN fail if things like address, characters change inside a single raster scan
    protected $cache;


    /**
     * @param C64 $c64
     * @param $memory_offset
     * @param IoInterface $io
     * @param LoggerInterface $logger
     */
    public function __construct(C64 $c64, $memory_offset, IoInterface $io, LoggerInterface $logger)
    {
        $this->buffer = str_repeat(chr(0), 402 * 292);

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
                $value = Utils::pack_bits($this->sprite_enabled);
                break;
            case 0x16:
                $value = $this->cr2;
                $value |= 0xD0;             // Unused bits
                break;
            case 0x17:
                $value = Utils::pack_bits($this->sprite_double_height);
                break;
            case 0x18:
                $value = $this->memory_setup;
                $value |= 0x01;             // Unused bits
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
                $value |= 0x70; // These bits are always set to 1 (unused though)
                break;
            case 0x1B:
                $value = Utils::pack_bits($this->sprite_priority);
                break;
            case 0x1C:
                $value = Utils::pack_bits($this->sprite_multicolor);
                break;
            case 0x1D:
                $value = Utils::pack_bits($this->sprite_double_width);
                break;
            case 0x1E:
                $value = $this->sprite_collision_sprite;

                // Automatically clear after read
                $this->sprite_collision_sprite = 0;
                break;
            case 0x1F:
                $value = $this->sprite_collision_background;

                // Automatically clear after read
                $this->sprite_collision_background = 0;
                break;
            case 0x20:
                $value = $this->border_color;
                $value |= 0xF0; // Unused bits
                break;
            case 0x21:
            case 0x22:
            case 0x23:
            case 0x24:
                $value = $this->background_color[$address - 0x21];
                $value |= 0xF0; // Unused bits
                break;
            case 0x25:
            case 0x26:
                $value = $this->sprite_extra_color[$address - 0x25];
                $value |= 0xF0; // Unused bits
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
                $value |= 0xF0; // Unused bits
                break;
            default:
                // Unused
                $value = 0xFF;
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

        $this->logger->debug(sprintf("VIC2: [W] Port %04X: %02X\n", $location, $value));

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

                // Update video modes if needed
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
                $this->sprite_enabled = Utils::unpack_bits($value);
                break;
            case 0x16 :
                $this->cr2 = $value;

                // Update video modes if needed
                $this->updateVideoSettings();
                break;
            case 0x17:
                $this->sprite_double_height = Utils::unpack_bits($value);
                break;
            case 0x18:
                $this->memory_setup = $value;

                // Recalculate memory locations based on the new memory setup
                $this->updateMemoryLocations();
                break;
            case 0x19:
                // Acknowledge the given interrupts
                $this->interrupt_status &= ~($value & 0x0F);
                break;
            case 0x1A:
                $this->interrupt_control = $value;
                break;
            case 0x1B:
                $this->sprite_priority = Utils::unpack_bits($value);
                break;
            case 0x1C:
                $this->sprite_multicolor = Utils::unpack_bits($value);
                break;
            case 0x1D:
                $this->sprite_double_width = Utils::unpack_bits($value);
                break;
            case 0x1E:
                // Unable to write to this register
                break;
            case 0x1F:
                // Unable to write to this register
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

    protected $t1 = 0;

    /**
     * Single VIC cycle
     */
    public function cycle() {
        // When the CIA2 has changed the VIC banks, we need to update the memory locations as well.
        if ($this->cached_vic_bank != $this->cia2->getVicBank() ) {
            $this->updateMemoryLocations();
            $this->cached_vic_bank = $this->cia2->getVicBank();
        }

        // Are there still unacknowledged interrupts pending?
        if ($this->interrupt_status & 0x80) {
            // Trigger IRQ when enabled
            if (! $this->cpu->flagIsSet(Cpu::P_FLAG_IRQ_DISABLE)) {
                $this->cpu->triggerIrq();
            }
            return;
        }

        // Iterate a few times. This basically decides how much PHP time the VIC gets compared to the CPU.
        for ($i=0; $i!=4; $i++) {

            // X / Y coordinates are starting from the top left HBLANK (404x312)
            $x = $this->raster_beam % 404;
            $y = ($this->raster_beam - $x) / 404;

            // Check if X and Y are in non-blank region
            if ($x >= 2 && $y >= 7 && $y < 299) {

                // X / Y coordinates are now starting from the top left border (402x292)
                $x -= 2;
                $y -= 7;

                // Check if the coordinate is a screen or border pixel (and screen is enabled)
                if ($this->screen_enabled && $x >= 46 && $x < 366 && $y > 43 && $y < 243) {

                    // Set current raster line
                    $this->raster_line = $y;

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
            if ($this->raster_beam >= 126048) {
                $t2 = microtime(true);
                print "Push on ".($t2-$this->t1)." sec         \n";
                $this->t1 = microtime(true);
                $this->io->writeMonitorBuffer($this->buffer);

                // Go back to left top corner
                $this->raster_beam = 0;

                // Reset raster line to 0
                $this->raster_line = 0;

                // Clear any caches.
                $this->cache = null;
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
     * Updates memory locations based on the memory setup register and cia2's VIC bank
     */
    protected function updateMemoryLocations() {
        $bank_offset = $this->cia2->getVicBank() * 0x4000;

        // Screen memory
        $this->screenmem_offset = ($this->memory_setup >> 4) & 0x0F;
        $this->screenmem_offset *= 0x400;
        $this->screenmem_offset += $bank_offset;

        // Bitmap
        $this->bitmapmem_offset = ($this->memory_setup >> 3) & 0x01;
        $this->bitmapmem_offset *= 0x2000;
        $this->bitmapmem_offset += $bank_offset;

        // Character ROM/RAM
        $this->charmem_offset = ($this->memory_setup >> 1) & 0x07;
        $this->charmem_offset *= 0x800;
        $this->charmem_offset += $bank_offset;

        $this->logger->debug(sprintf("Updating VIC2 video offsets:\n"));
        $this->logger->debug(sprintf("  Screen offset: %04X\n", $this->screenmem_offset));
        $this->logger->debug(sprintf("  Bitmap offset: %04X\n", $this->bitmapmem_offset));
        $this->logger->debug(sprintf("  Char   offset: %04X\n", $this->charmem_offset));
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
     * Find the offset of the given sprite
     *
     * @param $sprite_idx
     * @return int
     */
    protected function getSpriteShapeOffset($sprite_idx) {
        // Fetch the "index" of the sprite shape. This means sprites are bound to blocks fo 255 * 64bytes
        $location = $this->screenmem_offset + self::SPRITE_VECTOR_OFFSET + $sprite_idx;
        $index = $this->memory->read8($location);

        $bank_offset = $this->cia2->getVicBank() * 0x4000;
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
     * Reads a complete bitmap line (8 bytes) from a given petscii code.
     *
     * @param int $petscii_code
     * @param int $line Line number to read (between 0 and 7)
     * @return array
     */
    protected function readCharBitmap($petscii_code) {
        $location = $this->charmem_offset + ($petscii_code * 8);

        // This is tricky: 0x1000-0x1FFF and 0x9000-0x9FFF are actually hardwired by
        // the VIC to read from (Character) ROM instead of RAM

        $readFromRom = false;
        if ($location >= 0x1000 and $location <= 0x1FFF) {
            $readFromRom = true;
            $location -= 0x1000;
        }
        if ($location >= 0x9000 and $location <= 0x9FFF) {
            $readFromRom = true;
            $location -= 0x9000;
        }

        $bitmap_line = array();

        if ($readFromRom) {
            // ROM starts at 0xD000 (actually read from $this->memory_offset)
            for ($i=0; $i!=8; $i++) {
                $bitmap_line[$i] = $this->memory->read8rom($this->memory_offset + $location + $i);
            }
        } else {
            // Everything else we can simply retrieve directly from RAM
            for ($i=0; $i!=8; $i++) {
                $bitmap_line[$i] = $this->memory->read8ram($location + $i);
            }
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
        // Assume default / transparent color
        $color = $default_color;

        // We start painting sprite 7 first, up to sprite 0, which has the highest priority
        for ($i = 7; $i >= 0; $i--) {
            // Only handle sprite when it's enabled
            if ($this->sprite_enabled[$i]) {
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
     * @return array|mixed
     */
    protected function handleSprite($sprite_idx, $x, $y, $color) {
        $x_coord = $this->sprite_x[$sprite_idx];
        $y_coord = $this->sprite_y[$sprite_idx];

        $double_width = $this->sprite_double_width[$sprite_idx];
        $double_height = $this->sprite_double_height[$sprite_idx];

        $size_x = $double_width ? 48 : 24;
        $size_y = $double_height ? 42 : 21;

        if ($x >= $x_coord && $x < ($x_coord + $size_x) &&
            $y >= $y_coord && $y < ($y_coord + $size_y)) {

            // X Y falls into the coords of the current sprite

            // Sprite is written BEHIND the background, so we do not care about writing of the sprite
            if ($this->sprite_priority[$sprite_idx] == false && $color != $this->background_color[0]) {
                return $color;
            }

            $x_off = ($x - $x_coord);
            $y_off = ($y - $y_coord);

            if ($double_width) {
                $x_off >>= 1;
            }
            if ($double_height) {
                $y_off >>= 1;
            }

            $offset = ($y_off * 24) + $x_off;

            $byte_offset = ($offset >> 3);
            $bit_offset = 7 - ($offset % 8);

            // Fetch location of the sprite
            if (! isset($this->cache["sprite.location.$sprite_idx"])) {
                $this->cache["sprite.location.$sprite_idx"] = $this->getSpriteShapeOffset($sprite_idx);
            }
            $location = $this->cache["sprite.location.$sprite_idx"];

            $tmp = $location + $byte_offset;
            if (! isset($this->cache["sprite.location.value.$tmp"])) {
                $value = $this->memory->read8($location + $byte_offset);
                $this->cache["sprite.location.value.$tmp"] = $value;
            }
            $value = $this->cache["sprite.location.value.$tmp"];


            if ($this->sprite_multicolor[$sprite_idx]) {
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
        // Find the actual char for pixel $x, $y
        $char_index = ($y >> 3) * 40 + ($x >> 3);

        // Read the given character from the screen memory
        if (! isset($this->cache["screen.+$char_index"])) {
            $this->cache["screen.+$char_index"] = $this->memory->read8($this->screenmem_offset + $char_index);
        }
        $char = $this->cache["screen.+$char_index"];


        // Find the start of the matching bitmap inside the character ROM
        if (! isset($this->cache["charbitmap.$char"])) {
            $this->cache["charbitmap.$char"] = $this->readCharBitmap($char);
        }

        $line_idx = ($y & 0x07);
        $char_bitmap_line = $this->cache["charbitmap.$char"][$line_idx];
        $pixel = Utils::bit_get($char_bitmap_line, 7 - ($x & 0x07));

        if ($pixel == 0) {
            // Pixel turned off. Use background color
            $color = $this->background_color[0];
        } else {
            // Read from color RAM
            if (! isset($this->cache["colorram.$char_index"])) {
                $this->cache["colorram.$char_index"] = $this->readFromColorRam($char_index);
            }
            $color = $this->cache["colorram.$char_index"];
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
        $char_index = ($y >>3) * 40 + ($x >>  3);

        // If bit 3 of the color of the given character to plot is 0, plot it as
        // a standard character. This way we can actually "mix" multicolor and
        // standard characters, based on their color values.
        $color = $this->readFromColorRam($char_index);
        if (Utils::bit_test($color, 3) == 0) {
            return $this->standardCharacterMode($x, $y);
        }

        // Read the given character from the screen memory
        $char = $this->memory->read8($this->screenmem_offset + $char_index);

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
        $char = $this->memory->read8($this->screenmem_offset + $char_index);

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
