<?php

namespace Mos6510;

class IllegalOpcoder extends Opcoder {

    // All illegal opcodes
//    const OPCODE_SLO
//    const OPCODE_RLA
//    const OPCODE_SRE
    const OPCODE_RRA_IZY    = 0x73;
//    const OPCODE_SAX
//    const OPCODE_LAX
//    const OPCODE_DCP
//    const OPCODE_ISC
//    const OPCODE_ANC
//            // ?
//    const OPCODE_ALR
//    const OPCODE_ARR
//    const OPCODE_XAA
//    const OPCODE_LAX
//    const OPCODE_AXS
    const OPCODE_SBC_IMM2   = 0xEB;
    const OPCODE_AHX_IZY    = 0x93;
    const OPCODE_AHX_ABY    = 0x9F;

    const OPCODE_SHY_ABX    = 0x9C;
    const OPCODE_SHX_ABY    = 0x9E;
    const OPCODE_TAS_ABY    = 0x9B;
    const OPCODE_LAS_ABY    = 0xBB;




    /**
     * Processes a single instruction. Note that an instruction can span multiple cycles. It is thus not possible to
     * interrupt during a instruction. Only after the complete instruction has been completed, the process() method will
     * return and interrupts and other things can occur. Cycles are updated before the instruction, and some addressing
     * modes and instructions will optionally add more cycle (when crossing page boundaries or when branching).
     */
    public function process()
    {
        // Read next instruction from memory at the PC (program counter)
        $opcode = $this->cpu->read8FromPc();

        switch ($opcode) {
            case self::OPCODE_RRA_IZY:
                // @TODO: How many ticks???

                $location = 0;
                $value = $this->fetchIdy($location);

                $newValue = $this->_opcode_ror($value);
                $newValue = $this->_opcode_adc($newValue);

                $this->cpu->memory->write8($location, $newValue);
                break;
            default:
                // Let the default opcode handler take care of the given opcode
                $this->cpu->writePc($this->cpu->readPc() - 1);
                parent::process();
        }
    }

}
