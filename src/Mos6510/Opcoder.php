<?php

namespace Mos6510;

class Opcoder {

    // All official instructions. Unofficial instructions should be created by extending this class to keep them separate.
    const OPCODE_ADC_IMM    = 0x69;
    const OPCODE_ADC_ZP     = 0x65;
    const OPCODE_ADC_ZPX    = 0x75;
    const OPCODE_ADC_ABS    = 0x6D;
    const OPCODE_ADC_ABX    = 0x7D;
    const OPCODE_ADC_ABY    = 0x79;
    const OPCODE_ADC_IDX    = 0x61;
    const OPCODE_ADC_IDY    = 0x71;

    const OPCODE_AND_IMM    = 0x29;
    const OPCODE_AND_ZP     = 0x25;
    const OPCODE_AND_ZPX    = 0x35;
    const OPCODE_AND_IDX    = 0x21;
    const OPCODE_AND_IDY    = 0x31;
    const OPCODE_AND_ABS    = 0x2D;
    const OPCODE_AND_ABX    = 0x3D;
    const OPCODE_AND_ABY    = 0x39;

    const OPCODE_ASL        = 0x0A;
    const OPCODE_ASL_ZP     = 0x06;
    const OPCODE_ASL_ZPX    = 0x16;
    const OPCODE_ASL_ABS    = 0x0E;
    const OPCODE_ASL_ABX    = 0x1E;

    const OPCODE_BCC        = 0x90;
    const OPCODE_BCS        = 0xB0;
    const OPCODE_BEQ        = 0xF0;
    const OPCODE_BIT_ZP     = 0x24;
    const OPCODE_BIT_ABS    = 0x2C;
    const OPCODE_BMI        = 0x30;
    const OPCODE_BNE        = 0xD0;
    const OPCODE_BPL        = 0x10;
    const OPCODE_BRK        = 0x00;
    const OPCODE_BVC        = 0x50;
    const OPCODE_BVS        = 0x70;

    const OPCODE_CLC        = 0x18;
    const OPCODE_CLD        = 0xD8;
    const OPCODE_CLI        = 0x58;
    const OPCODE_CLV        = 0xB8;

    const OPCODE_CMP_IMM    = 0xC9;
    const OPCODE_CMP_ZP     = 0xC5;
    const OPCODE_CMP_ZPX    = 0xD5;
    const OPCODE_CMP_IZX    = 0xC1;
    const OPCODE_CMP_IZY    = 0xD1;
    const OPCODE_CMP_ABS    = 0xCD;
    const OPCODE_CMP_ABX    = 0xDD;
    const OPCODE_CMP_ABY    = 0xD9;

    const OPCODE_CPX_IMM    = 0xE0;
    const OPCODE_CPX_ZP     = 0xE4;
    const OPCODE_CPX_ABS    = 0xEC;

    const OPCODE_CPY_IMM    = 0xC0;
    const OPCODE_CPY_ZP     = 0xC4;
    const OPCODE_CPY_ABS    = 0xCC;

    const OPCODE_DEC_ZP     = 0xC6;
    const OPCODE_DEC_ZPX    = 0xD6;
    const OPCODE_DEC_ABS    = 0xCE;
    const OPCODE_DEC_ABX    = 0xDE;

    const OPCODE_DEX        = 0xCA;
    const OPCODE_DEY        = 0x88;

    const OPCODE_EOR_IMM    = 0x49;
    const OPCODE_EOR_ZP     = 0x45;
    const OPCODE_EOR_ZPX    = 0x55;
    const OPCODE_EOR_IDX    = 0x41;
    const OPCODE_EOR_IDY    = 0x51;
    const OPCODE_EOR_ABS    = 0x4D;
    const OPCODE_EOR_ABX    = 0x5D;
    const OPCODE_EOR_ABY    = 0x59;

    const OPCODE_INC_ZP     = 0xE6;
    const OPCODE_INC_ZPX    = 0xF6;
    const OPCODE_INC_ABS    = 0xEE;
    const OPCODE_INC_ABX    = 0xFE;

    const OPCODE_INX        = 0xE8;
    const OPCODE_INY        = 0xC8;

    const OPCODE_JMP_ABS    = 0x4C;
    const OPCODE_JMP_IND    = 0x6C;
    const OPCODE_JSR        = 0x20;

    const OPCODE_LDA_IMM    = 0xA9;
    const OPCODE_LDA_ZP     = 0xA5;
    const OPCODE_LDA_ZPX    = 0xB5;
    const OPCODE_LDA_IDX    = 0xA1;
    const OPCODE_LDA_IDY    = 0xB1;
    const OPCODE_LDA_ABS    = 0xAD;
    const OPCODE_LDA_ABX    = 0xBD;
    const OPCODE_LDA_ABY    = 0xB9;

    const OPCODE_LDX_IMM    = 0xA2;
    const OPCODE_LDX_ZP     = 0xA6;
    const OPCODE_LDX_ZPY    = 0xB6;
    const OPCODE_LDX_ABS    = 0xAE;
    const OPCODE_LDX_ABY    = 0xBE;
    const OPCODE_LDY_IMM    = 0xA0;
    const OPCODE_LDY_ZP     = 0xA4;
    const OPCODE_LDY_ZPX    = 0xB4;
    const OPCODE_LDY_ABS    = 0xAC;
    const OPCODE_LDY_ABX    = 0xBC;

    const OPCODE_LSR        = 0x4A;
    const OPCODE_LSR_ZP     = 0x46;
    const OPCODE_LSR_ZPX    = 0x56;
    const OPCODE_LSR_ABS    = 0x4E;
    const OPCODE_LSR_ABX    = 0x5E;

    const OPCODE_NOP        = 0xC2;
    const OPCODE_NOP2       = 0xEA;
    const OPCODE_NOP3       = 0x82;
    const OPCODE_NOP4       = 0xDA;

    const OPCODE_ORA_IMM    = 0x09;
    const OPCODE_ORA_ZP     = 0x05;
    const OPCODE_ORA_ZPX    = 0x15;
    const OPCODE_ORA_IDX    = 0x01;
    const OPCODE_ORA_IDY    = 0x11;
    const OPCODE_ORA_ABS    = 0x0D;
    const OPCODE_ORA_ABX    = 0x1D;
    const OPCODE_ORA_ABY    = 0x19;

    const OPCODE_PHA        = 0x48;
    const OPCODE_PHP        = 0x08;
    const OPCODE_PLA        = 0x68;
    const OPCODE_PLP        = 0x28;

    const OPCODE_ROL        = 0x2A;
    const OPCODE_ROL_ZP     = 0x26;
    const OPCODE_ROL_ZPX    = 0x36;
    const OPCODE_ROL_ABS    = 0x2E;
    const OPCODE_ROL_ABX    = 0x3E;

    const OPCODE_ROR        = 0x6A;
    const OPCODE_ROR_ZP     = 0x66;
    const OPCODE_ROR_ZPX    = 0x76;
    const OPCODE_ROR_ABS    = 0x6E;
    const OPCODE_ROR_ABX    = 0x7E;

    const OPCODE_RTI        = 0x40;
    const OPCODE_RTS        = 0x60;

    const OPCODE_SBC_IMM    = 0xE9;
    const OPCODE_SBC_ZP     = 0xE5;
    const OPCODE_SBC_ZPX    = 0xF5;
    const OPCODE_SBC_ABS    = 0xED;
    const OPCODE_SBC_ABX    = 0xFD;
    const OPCODE_SBC_ABY    = 0xF9;
    const OPCODE_SBC_IDX    = 0xE1;
    const OPCODE_SBC_IDY    = 0xF1;

    const OPCODE_SEC        = 0x38;
    const OPCODE_SED        = 0xF8;
    const OPCODE_SEI        = 0x78;
    const OPCODE_STA_ZP     = 0x85;
    const OPCODE_STA_ZPX    = 0x95;
    const OPCODE_STA_ABS    = 0x8D;
    const OPCODE_STA_ABX    = 0x9D;
    const OPCODE_STA_ABY    = 0x99;
    const OPCODE_STA_IDX    = 0x81;
    const OPCODE_STA_IDY    = 0x91;

    const OPCODE_STX_ZP     = 0x86;
    const OPCODE_STX_ZPY    = 0x96;
    const OPCODE_STX_ABS    = 0x8E;

    const OPCODE_STY_ZP     = 0x84;
    const OPCODE_STY_ZPX    = 0x94;
    const OPCODE_STY_ABS    = 0x8C;

    const OPCODE_TAX        = 0xAA;
    const OPCODE_TAY        = 0xA8;
    const OPCODE_TSX        = 0xBA;
    const OPCODE_TXA        = 0x8A;
    const OPCODE_TXS        = 0x9A;
    const OPCODE_TYA        = 0x98;


    public function __construct(Cpu $cpu) {
        $this->cpu = $cpu;
    }


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
            case self::OPCODE_ADC_IMM:
                $this->cpu->tick(3);
                $this->_opcode_adc($this->fetchImm());
                break;
            case self::OPCODE_ADC_ZP:
                $this->cpu->tick(3);
                $this->_opcode_adc($this->fetchZp());
                break;
            case self::OPCODE_ADC_ZPX:
                $this->cpu->tick(4);
                $this->_opcode_adc($this->fetchZpx());
                break;
            case self::OPCODE_ADC_ABS:
                $this->cpu->tick(4);
                $this->_opcode_adc($this->fetchAbs());
                break;
            case self::OPCODE_ADC_ABX:
                $this->cpu->tick(4);
                $this->_opcode_adc($this->fetchAbx());
                break;
            case self::OPCODE_ADC_ABY:
                $this->cpu->tick(4);
                $this->_opcode_adc($this->fetchAby());
                break;
            case self::OPCODE_ADC_IDX:
                $this->cpu->tick(6);
                $this->_opcode_adc($this->fetchIdx());
                break;
            case self::OPCODE_ADC_IDY:
                $this->cpu->tick(5);
                $this->_opcode_adc($this->fetchIdy());
                break;

            case self::OPCODE_AND_IMM:
                $this->cpu->tick(2);
                $this->cpu->writeA($this->_opcode_and($this->cpu->readA(), $this->fetchImm()));
                break;
            case self::OPCODE_AND_ZP:
                $this->cpu->tick(2);
                $this->cpu->writeA($this->_opcode_and($this->cpu->readA(), $this->fetchZp()));
                break;
            case self::OPCODE_AND_ZPX:
                $this->cpu->tick(3);
                $this->cpu->writeA($this->_opcode_and($this->cpu->readA(), $this->fetchZpx()));
                break;
            case self::OPCODE_AND_IDX:
                $this->cpu->tick(6);
                $this->cpu->writeA($this->_opcode_and($this->cpu->readA(), $this->fetchIdx()));
                break;
            case self::OPCODE_AND_IDY:
                $this->cpu->tick(5);
                $this->cpu->writeA($this->_opcode_and($this->cpu->readA(), $this->fetchIdy()));
                break;
            case self::OPCODE_AND_ABS:
                $this->cpu->tick(4);
                $this->cpu->writeA($this->_opcode_and($this->cpu->readA(), $this->fetchAbs()));
                break;
            case self::OPCODE_AND_ABX:
                $this->cpu->tick(4);
                $this->cpu->writeA($this->_opcode_and($this->cpu->readA(), $this->fetchAbx()));
                break;
            case self::OPCODE_AND_ABY:
                $this->cpu->tick(4);
                $this->cpu->writeA($this->_opcode_and($this->cpu->readA(), $this->fetchAby()));
                break;

            case self::OPCODE_ASL:
                $this->cpu->tick(2);
                $this->cpu->writeA($this->_opcode_asl($this->cpu->readA()));
                break;
            case self::OPCODE_ASL_ZP:
                $this->cpu->tick(5);

                $location = 0;
                $operand = $this->fetchZp($location);
                $this->cpu->memory->write8($location, $this->_opcode_asl($operand));
                break;
            case self::OPCODE_ASL_ZPX:
                $this->cpu->tick(6);

                $location = 0;
                $operand = $this->fetchZpx($location);
                $this->cpu->memory->write8($location, $this->_opcode_asl($operand));
                break;
            case self::OPCODE_ASL_ABS:
                $this->cpu->tick(6);

                $location = 0;
                $operand = $this->fetchAbs($location);
                $this->cpu->memory->write8($location, $this->_opcode_asl($operand));
                break;
            case self::OPCODE_ASL_ABX:
                $this->cpu->tick(7);

                $location = 0;
                $operand = $this->fetchAbx($location);
                $this->cpu->memory->write8($location, $this->_opcode_asl($operand));
                break;

            case self::OPCODE_BCC:
                $this->cpu->tick(2);

                $offset = $this->fetchImm();
                if (! $this->cpu->flagIsSet(Cpu::P_FLAG_CARRY)) {
                    $this->cpu->branchPc($offset);
                }
                break;
            case self::OPCODE_BCS:
                $this->cpu->tick(2);

                $offset = $this->fetchImm();
                if ($this->cpu->flagIsSet(Cpu::P_FLAG_CARRY)) {
                    $this->cpu->branchPc($offset);
                }
                break;
            case self::OPCODE_BEQ:
                $this->cpu->tick(2);

                $offset = $this->fetchImm();
                if ($this->cpu->flagIsSet(Cpu::P_FLAG_ZERO)) {
                    $this->cpu->branchPc($offset);
                }
                break;
            case self::OPCODE_BIT_ZP:
                $this->cpu->tick(3);
                $this->_opcode_bit($this->fetchZp());
                break;
            case self::OPCODE_BIT_ABS:
                $this->cpu->tick(4);
                $this->_opcode_bit($this->fetchAbs());
                break;

            case self::OPCODE_BMI:
                $this->cpu->tick(2);

                $offset = $this->fetchImm();
                if ($this->cpu->flagIsSet(Cpu::P_FLAG_NEGATIVE)) {
                    $this->cpu->branchPc($offset);
                }
                break;
            case self::OPCODE_BNE:
                $this->cpu->tick(2);

                $offset = $this->fetchImm();
                if (!$this->cpu->flagIsSet(Cpu::P_FLAG_ZERO)) {
                    $this->cpu->branchPc($offset);
                }

                break;
            case self::OPCODE_BPL:
                $this->cpu->tick(2);

                $offset = $this->fetchImm();
                if (!$this->cpu->flagIsSet(Cpu::P_FLAG_NEGATIVE)) {
                    $this->cpu->branchPc($offset);
                }
                break;
            case self::OPCODE_BRK:
                $this->cpu->tick(7);

                // Push PC
                $this->cpu->stackPush16($this->cpu->readPc()+1);

                // Push flags with BRK = 1
                $this->cpu->flagSet(Cpu::P_FLAG_BREAK, 1);
                $this->cpu->stackPush8($this->cpu->readP());

                $this->cpu->flagSet(Cpu::P_FLAG_IRQ_DISABLE, 1);

                // Jump to address found in FFFE
                $this->cpu->writePc($this->cpu->memory->read16(Cpu::IRQ_BRK_VECTOR));
                break;
            case self::OPCODE_BVC:
                $this->cpu->tick(2);

                $offset = $this->fetchImm();
                if (!$this->cpu->flagIsSet(Cpu::P_FLAG_OVERFLOW)) {
                    $this->cpu->branchPc($offset);
                }
                break;
            case self::OPCODE_BVS:
                $this->cpu->tick(2);

                $offset = $this->fetchImm();
                if ($this->cpu->flagIsSet(Cpu::P_FLAG_OVERFLOW)) {
                    $this->cpu->branchPc($offset);
                }
                break;

            case self::OPCODE_CLC:
                $this->cpu->tick(2);
                $this->cpu->flagSet(Cpu::P_FLAG_CARRY, 0);
                break;
            case self::OPCODE_CLD:
                $this->cpu->tick(2);
                $this->cpu->flagSet(Cpu::P_FLAG_DECIMAL, 0);
                break;
            case self::OPCODE_CLI:
                $this->cpu->tick(2);
                $this->cpu->flagSet(Cpu::P_FLAG_IRQ_DISABLE, 0);
                break;
            case self::OPCODE_CLV:
                $this->cpu->tick(2);
                $this->cpu->flagSet(Cpu::P_FLAG_OVERFLOW, 0);
                break;

            case self::OPCODE_CMP_IMM:
                $this->cpu->tick(2);
                $this->_opcode_cmp($this->cpu->readA(), $this->fetchImm());
                break;
            case self::OPCODE_CMP_ZP:
                $this->cpu->tick(3);
                $this->_opcode_cmp($this->cpu->readA(), $this->fetchZp());
                break;
            case self::OPCODE_CMP_ZPX:
                $this->cpu->tick(4);
                $this->_opcode_cmp($this->cpu->readA(), $this->fetchZpx());
                break;
            case self::OPCODE_CMP_IZX:
                $this->cpu->tick(6);
                $this->_opcode_cmp($this->cpu->readA(), $this->fetchIdx());
                break;
            case self::OPCODE_CMP_IZY:
                $this->cpu->tick(5);
                $this->_opcode_cmp($this->cpu->readA(), $this->fetchIdy());
                break;
            case self::OPCODE_CMP_ABS:
                $this->cpu->tick(4);
                $this->_opcode_cmp($this->cpu->readA(), $this->fetchAbs());
                break;
            case self::OPCODE_CMP_ABX:
                $this->cpu->tick(4);
                $this->_opcode_cmp($this->cpu->readA(), $this->fetchAbx());
                break;
            case self::OPCODE_CMP_ABY:
                $this->cpu->tick(4);
                $this->_opcode_cmp($this->cpu->readA(), $this->fetchAby());
                break;

            case self::OPCODE_CPX_IMM:
                $this->cpu->tick(2);
                $this->_opcode_cmp($this->cpu->readX(), $this->fetchImm());
                break;
            case self::OPCODE_CPX_ZP:
                $this->cpu->tick(3);
                $this->_opcode_cmp($this->cpu->readX(), $this->fetchZp());
                break;
            case self::OPCODE_CPX_ABS:
                $this->cpu->tick(4);
                $this->_opcode_cmp($this->cpu->readX(), $this->fetchAbs());
                break;

            case self::OPCODE_CPY_IMM:
                $this->cpu->tick(2);
                $this->_opcode_cmp($this->cpu->readY(), $this->fetchImm());
                break;
            case self::OPCODE_CPY_ZP:
                $this->cpu->tick(3);
                $this->_opcode_cmp($this->cpu->readY(), $this->fetchZp());
                break;
            case self::OPCODE_CPY_ABS:
                $this->cpu->tick(4);
                $this->_opcode_cmp($this->cpu->readY(), $this->fetchAbs());
                break;
            case self::OPCODE_DEC_ZP:
                $this->cpu->tick(5);

                $location = 0;
                $value = $this->fetchZp($location);
                $value = $this->_opcode_incdec($value, -1);
                $this->cpu->memory->write8($location, $value);

                break;
            case self::OPCODE_DEC_ZPX:
                $this->cpu->tick(6);

                $location = 0;
                $value = $this->fetchZpx($location);
                $value = $this->_opcode_incdec($value, -1);
                $this->cpu->memory->write8($location, $value);

                break;
            case self::OPCODE_DEC_ABS:
                $this->cpu->tick(6);

                $location = 0;
                $value = $this->fetchAbs($location);
                $value = $this->_opcode_incdec($value, -1);
                $this->cpu->memory->write8($location, $value);
                break;
            case self::OPCODE_DEC_ABX:
                $this->cpu->tick(7);

                $location = 0;
                $value = $this->fetchAbx($location);
                $value = $this->_opcode_incdec($value, -1);
                $this->cpu->memory->write8($location, $value);
                break;

            case self::OPCODE_DEX:
                $this->cpu->tick(2);
                $this->cpu->writeX($this->_opcode_incdec($this->cpu->readX(), -1));
                break;
            case self::OPCODE_DEY:
                $this->cpu->tick(2);
                $this->cpu->writeY($this->_opcode_incdec($this->cpu->readY(), -1));
                break;

            case self::OPCODE_EOR_IMM:
                $this->cpu->tick(2);
                $this->cpu->writeA($this->_opcode_eor($this->cpu->readA(), $this->fetchImm()));
                break;
            case self::OPCODE_EOR_ZP:
                $this->cpu->tick(2);
                $this->cpu->writeA($this->_opcode_eor($this->cpu->readA(), $this->fetchZp()));
                break;
            case self::OPCODE_EOR_ZPX:
                $this->cpu->tick(3);
                $this->cpu->writeA($this->_opcode_eor($this->cpu->readA(), $this->fetchZpx()));
                break;
            case self::OPCODE_EOR_IDX:
                $this->cpu->tick(6);
                $this->cpu->writeA($this->_opcode_eor($this->cpu->readA(), $this->fetchIdx()));
                break;
            case self::OPCODE_EOR_IDY:
                $this->cpu->tick(5);
                $this->cpu->writeA($this->_opcode_eor($this->cpu->readA(), $this->fetchIdy()));
                break;
            case self::OPCODE_EOR_ABS:
                $this->cpu->tick(4);
                $this->cpu->writeA($this->_opcode_eor($this->cpu->readA(), $this->fetchAbs()));
                break;
            case self::OPCODE_EOR_ABX:
                $this->cpu->tick(4);
                $this->cpu->writeA($this->_opcode_eor($this->cpu->readA(), $this->fetchAbx()));
                break;
            case self::OPCODE_EOR_ABY:
                $this->cpu->tick(4);
                $this->cpu->writeA($this->_opcode_eor($this->cpu->readA(), $this->fetchAby()));
                break;
            case self::OPCODE_INC_ZP:
                $this->cpu->tick(5);

                $location = 0;
                $value = $this->fetchZp($location);
                $value = $this->_opcode_incdec($value, 1);
                $this->cpu->memory->write8($location, $value);
                break;
            case self::OPCODE_INC_ZPX:
                $this->cpu->tick(6);

                $location = 0;
                $value = $this->fetchZpx($location);
                $value = $this->_opcode_incdec($value, 1);
                $this->cpu->memory->write8($location, $value);
                break;
            case self::OPCODE_INC_ABS:
                $this->cpu->tick(6);

                $location = 0;
                $value = $this->fetchAbs($location);
                $value = $this->_opcode_incdec($value, 1);
                $this->cpu->memory->write8($location, $value);
                break;
            case self::OPCODE_INC_ABX:
                $this->cpu->tick(7);

                $location = 0;
                $value = $this->fetchAbx($location);
                $value = $this->_opcode_incdec($value, 1);
                $this->cpu->memory->write8($location, $value);
                break;
            case self::OPCODE_INX:
                $this->cpu->tick(2);
                $this->cpu->writeX($this->_opcode_incdec($this->cpu->readX(), 1));
                break;
            case self::OPCODE_INY:
                $this->cpu->tick(2);
                $this->cpu->writeY($this->_opcode_incdec($this->cpu->readY(), 1));
                break;

            case self::OPCODE_JMP_ABS:
                $this->cpu->tick(3);
                $location = 0;
                $this->fetchAbs($location); // We ignore what's on the absolute address, we care only about the location
                $this->cpu->writePc($location);
                break;
            case self::OPCODE_JMP_IND:
                $this->cpu->tick(5);
                // Indirect
                $this->cpu->writePc($this->fetchIndirect());
                break;

            case self::OPCODE_JSR:
                $this->cpu->tick(6);
                // Fetch location before storing the PC
                $location = 0;
                $this->fetchAbs($location);
                $this->cpu->stackPush16($this->cpu->readPc()-1);
                $this->cpu->writePc($location);
                break;

            case self::OPCODE_LDA_IMM:
                $this->cpu->tick(2);
                $this->_opcode_lda($this->fetchImm());
                break;
            case self::OPCODE_LDA_ZP:
                $this->cpu->tick(3);
                $this->_opcode_lda($this->fetchZp());
                break;
            case self::OPCODE_LDA_ZPX:
                $this->cpu->tick(4);
                $this->_opcode_lda($this->fetchZpx());
                break;
            case self::OPCODE_LDA_IDX:
                $this->cpu->tick(6);
                $this->_opcode_lda($this->fetchIdx());
                break;
            case self::OPCODE_LDA_IDY:
                $this->cpu->tick(5);
                $this->_opcode_lda($this->fetchIdy());
                break;
            case self::OPCODE_LDA_ABS:
                $this->cpu->tick(4);
                $this->_opcode_lda($this->fetchAbs());
                break;
            case self::OPCODE_LDA_ABX:
                $this->cpu->tick(4);
                $this->_opcode_lda($this->fetchAbx());
                break;
            case self::OPCODE_LDA_ABY:
                $this->cpu->tick(4);
                $this->_opcode_lda($this->fetchAby());
                break;

            case self::OPCODE_LDX_IMM:
                $this->cpu->tick(2);
                $this->_opcode_ldx($this->fetchImm());
                break;
            case self::OPCODE_LDX_ZP:
                $this->cpu->tick(3);
                $this->_opcode_ldx($this->fetchZp());
                break;
            case self::OPCODE_LDX_ZPY:
                $this->cpu->tick(4);
                $this->_opcode_ldx($this->fetchZpy());
                break;
            case self::OPCODE_LDX_ABS:
                $this->cpu->tick(4);
                $this->_opcode_ldx($this->fetchAbs());
                break;
            case self::OPCODE_LDX_ABY:
                $this->cpu->tick(4);
                $this->_opcode_ldx($this->fetchAby());
                break;
            case self::OPCODE_LDY_IMM:
                $this->cpu->tick(2);
                $this->_opcode_ldy($this->fetchImm());
                break;
            case self::OPCODE_LDY_ZP:
                $this->cpu->tick(3);
                $this->_opcode_ldy($this->fetchZp());
                break;
            case self::OPCODE_LDY_ZPX:
                $this->cpu->tick(4);
                $this->_opcode_ldy($this->fetchZpx());
                break;
            case self::OPCODE_LDY_ABS:
                $this->cpu->tick(4);
                $this->_opcode_ldy($this->fetchAbs());
                break;
            case self::OPCODE_LDY_ABX:
                $this->cpu->tick(4);
                $this->_opcode_ldy($this->fetchAbx());
                break;

            case self::OPCODE_LSR:
                $this->cpu->tick(2);
                $this->cpu->writeA($this->_opcode_lsr($this->cpu->readA()));
                break;
            case self::OPCODE_LSR_ZP:
                $this->cpu->tick(5);

                $location = 0;
                $operand = $this->fetchZp($location);
                $this->cpu->memory->write8($location, $this->_opcode_lsr($operand));
                break;
            case self::OPCODE_LSR_ZPX:
                $this->cpu->tick(6);

                $location = 0;
                $operand = $this->fetchZpx($location);
                $this->cpu->memory->write8($location, $this->_opcode_lsr($operand));
                break;
            case self::OPCODE_LSR_ABS:
                $this->cpu->tick(6);

                $location = 0;
                $operand = $this->fetchAbs($location);
                $this->cpu->memory->write8($location, $this->_opcode_lsr($operand));
                break;
            case self::OPCODE_LSR_ABX:
                $this->cpu->tick(7);

                $location = 0;
                $operand = $this->fetchAbx($location);
                $this->cpu->memory->write8($location, $this->_opcode_lsr($operand));
                break;

            case self::OPCODE_NOP:
            case self::OPCODE_NOP2:
            case self::OPCODE_NOP3:
            case self::OPCODE_NOP4:
                $this->cpu->tick(2);
                break;

            case self::OPCODE_ORA_IMM:
                $this->cpu->tick(2);
                $this->cpu->writeA($this->_opcode_ora($this->cpu->readA(), $this->fetchImm()));
                break;
            case self::OPCODE_ORA_ZP:
                $this->cpu->tick(2);
                $this->cpu->writeA($this->_opcode_ora($this->cpu->readA(), $this->fetchZp()));
                break;
            case self::OPCODE_ORA_ZPX:
                $this->cpu->tick(3);
                $this->cpu->writeA($this->_opcode_ora($this->cpu->readA(), $this->fetchZpx()));
                break;
            case self::OPCODE_ORA_IDX:
                $this->cpu->tick(6);
                $this->cpu->writeA($this->_opcode_ora($this->cpu->readA(), $this->fetchIdx()));
                break;
            case self::OPCODE_ORA_IDY:
                $this->cpu->tick(5);
                $this->cpu->writeA($this->_opcode_ora($this->cpu->readA(), $this->fetchIdy()));
                break;
            case self::OPCODE_ORA_ABS:
                $this->cpu->tick(4);
                $this->cpu->writeA($this->_opcode_ora($this->cpu->readA(), $this->fetchAbs()));
                break;
            case self::OPCODE_ORA_ABX:
                $this->cpu->tick(4);
                $this->cpu->writeA($this->_opcode_ora($this->cpu->readA(), $this->fetchAbx()));
                break;
            case self::OPCODE_ORA_ABY:
                $this->cpu->tick(4);
                $this->cpu->writeA($this->_opcode_ora($this->cpu->readA(), $this->fetchAby()));
                break;

            case self::OPCODE_PHA:
                $this->cpu->tick(3);
                $value = $this->cpu->readA();
                $this->cpu->stackPush8($value);
                break;
            case self::OPCODE_PHP:
                $this->cpu->tick(3);
                // Issue that PHP pushes with BREAK flag set to 1
                $this->cpu->stackPush8($this->cpu->readP() | (1 << Cpu::P_FLAG_BREAK));
                break;
            case self::OPCODE_PLA:
                $this->cpu->tick(4);
                $value = $this->cpu->stackPop8();
                $this->_opcode_lda($value);
                break;
            case self::OPCODE_PLP:
                $this->cpu->tick(4);
                $value = $this->cpu->stackPop8();
                $this->cpu->writeP($value);
                break;

            case self::OPCODE_ROR:
                $this->cpu->tick(2);
                $this->cpu->writeA($this->_opcode_ror($this->cpu->readA()));
                break;
            case self::OPCODE_ROR_ZP:
                $this->cpu->tick(5);

                $location = 0;
                $operand = $this->fetchZp($location);
                $this->cpu->memory->write8($location, $this->_opcode_ror($operand));
                break;
            case self::OPCODE_ROR_ZPX:
                $this->cpu->tick(6);

                $location = 0;
                $operand = $this->fetchZpx($location);
                $this->cpu->memory->write8($location, $this->_opcode_ror($operand));
                break;
            case self::OPCODE_ROR_ABS:
                $this->cpu->tick(6);

                $location = 0;
                $operand = $this->fetchAbs($location);
                $this->cpu->memory->write8($location, $this->_opcode_ror($operand));
                break;
            case self::OPCODE_ROR_ABX:
                $this->cpu->tick(7);

                $location = 0;
                $operand = $this->fetchAbx($location);
                $this->cpu->memory->write8($location, $this->_opcode_ror($operand));
                break;
            
            case self::OPCODE_ROL:
                $this->cpu->tick(2);

                $this->cpu->writeA($this->_opcode_rol($this->cpu->readA()));
                break;
            case self::OPCODE_ROL_ZP:
                $this->cpu->tick(5);

                $location = 0;
                $operand = $this->fetchZp($location);
                $this->cpu->memory->write8($location, $this->_opcode_rol($operand));
                break;
            case self::OPCODE_ROL_ZPX:
                $this->cpu->tick(6);

                $location = 0;
                $operand = $this->fetchZpx($location);
                $this->cpu->memory->write8($location, $this->_opcode_rol($operand));
                break;
            case self::OPCODE_ROL_ABS:
                $this->cpu->tick(6);

                $location = 0;
                $operand = $this->fetchAbs($location);
                $this->cpu->memory->write8($location, $this->_opcode_rol($operand));
                break;
            case self::OPCODE_ROL_ABX:
                $this->cpu->tick(7);

                $location = 0;
                $operand = $this->fetchAbx($location);
                $this->cpu->memory->write8($location, $this->_opcode_rol($operand));
                break;


            case self::OPCODE_RTI:
                $this->cpu->tick(6);

                $this->cpu->writeP($this->cpu->stackPop8());
                $this->cpu->writePc($this->cpu->stackPop16());
                break;
            case self::OPCODE_RTS:
                $this->cpu->tick(6);
                $this->cpu->writePc($this->cpu->stackPop16()+1);
                break;


            case self::OPCODE_SBC_IMM:
                $this->cpu->tick(2);
                $this->_opcode_sbc($this->fetchImm());
                break;
            case self::OPCODE_SBC_ZP:
                $this->cpu->tick(3);
                $this->_opcode_sbc($this->fetchZp());
                break;
            case self::OPCODE_SBC_ZPX:
                $this->cpu->tick(4);
                $this->_opcode_sbc($this->fetchZpx());
                break;
            case self::OPCODE_SBC_ABS:
                $this->cpu->tick(4);
                $this->_opcode_sbc($this->fetchAbs());
                break;
            case self::OPCODE_SBC_ABX:
                $this->cpu->tick(4);
                $this->_opcode_sbc($this->fetchAbx());
                break;
            case self::OPCODE_SBC_ABY:
                $this->cpu->tick(4);
                $this->_opcode_sbc($this->fetchAby());
                break;
            case self::OPCODE_SBC_IDX:
                $this->cpu->tick(6);
                $this->_opcode_sbc($this->fetchIdx());
                break;
            case self::OPCODE_SBC_IDY:
                $this->cpu->tick(5);
                $this->_opcode_sbc($this->fetchIdy());
                break;

            case self::OPCODE_SEC:
                $this->cpu->tick(2);
                $this->cpu->flagSet(Cpu::P_FLAG_CARRY, 1);
                break;
            case self::OPCODE_SED:
                $this->cpu->tick(2);
                $this->cpu->flagSet(Cpu::P_FLAG_DECIMAL, 1);
                break;
            case self::OPCODE_SEI:
                $this->cpu->tick(2);
                $this->cpu->flagSet(Cpu::P_FLAG_IRQ_DISABLE, 1);
                break;

            case self::OPCODE_STA_ZP:
                $this->cpu->tick(3);
                $location = 0;
                $this->fetchZp($location);
                $this->cpu->memory->write8($location, $this->cpu->readA());
                break;
            case self::OPCODE_STA_ZPX:
                $this->cpu->tick(4);
                $location = 0;
                $this->fetchZpx($location);
                $this->cpu->memory->write8($location, $this->cpu->readA());
                break;
            case self::OPCODE_STA_IDX:
                $this->cpu->tick(6);
                $location = 0;
                $this->fetchIdx($location);
                $this->cpu->memory->write8($location, $this->cpu->readA());
                break;
            case self::OPCODE_STA_IDY:
                $this->cpu->tick(6);
                $location = 0;
                $this->fetchIdy($location);
                $this->cpu->memory->write8($location, $this->cpu->readA());
                break;
            case self::OPCODE_STA_ABS:
                $this->cpu->tick(4);
                $location = 0;
                $this->fetchAbs($location);
                $this->cpu->memory->write8($location, $this->cpu->readA());
                break;
            case self::OPCODE_STA_ABX:
                $this->cpu->tick(5);
                $location = 0;
                $this->fetchAbx($location);
                $this->cpu->memory->write8($location, $this->cpu->readA());
                break;
            case self::OPCODE_STA_ABY:
                $this->cpu->tick(5);
                $location = 0;
                $this->fetchAby($location);
                $this->cpu->memory->write8($location, $this->cpu->readA());
                break;

            case self::OPCODE_STX_ZP:
                $this->cpu->tick(3);
                $location = 0;
                $this->fetchZp($location);
                $this->cpu->memory->write8($location, $this->cpu->readX());
                break;
            case self::OPCODE_STX_ZPY:
                $this->cpu->tick(4);
                $location = 0;
                $this->fetchZpy($location);
                $this->cpu->memory->write8($location, $this->cpu->readX());
                break;
            case self::OPCODE_STX_ABS:
                $this->cpu->tick(4);
                $location = 0;
                $this->fetchAbs($location);
                $this->cpu->memory->write8($location, $this->cpu->readX());
                break;

            case self::OPCODE_STY_ZP:
                $this->cpu->tick(3);
                $location = 0;
                $this->fetchZp($location);
                $this->cpu->memory->write8($location, $this->cpu->readY());
                break;
            case self::OPCODE_STY_ZPX:
                $this->cpu->tick(4);
                $location = 0;
                $this->fetchZpx($location);
                $this->cpu->memory->write8($location, $this->cpu->readY());
                break;
            case self::OPCODE_STY_ABS:
                $this->cpu->tick(4);
                $location = 0;
                $this->fetchAbs($location);
                $this->cpu->memory->write8($location, $this->cpu->readY());
                break;

            case self::OPCODE_TAX:
                $this->cpu->tick(2);
                $this->_opcode_ldx($this->cpu->readA());
                break;
            case self::OPCODE_TAY:
                $this->cpu->tick(2);
                $this->_opcode_ldy($this->cpu->readA());
                break;
            case self::OPCODE_TSX:
                $this->cpu->tick(2);
                $this->_opcode_ldx($this->cpu->readS());
                break;
            case self::OPCODE_TXA:
                $this->cpu->tick(2);
                $this->_opcode_lda($this->cpu->readX());
                break;
            case self::OPCODE_TXS:
                $this->cpu->tick(2);
                $this->_opcode_lds($this->cpu->readX());
                break;
            case self::OPCODE_TYA:
                $this->cpu->tick(2);
                $this->_opcode_lda($this->cpu->readY());
                break;

            default:
                $this->cpu->logger->error(sprintf('Invalid opcode %02X detected at %04X', $opcode, $this->cpu->readPc()));
                break;
        }
    }

    /*
     * Opcode helpers.
     */

    protected function _opcode_asl($operand)
    {
        assert($operand <= 0xFF);

        $operand <<= 1;
        $this->cpu->flagSet(Cpu::P_FLAG_CARRY, ($operand > 0xFF));
        $operand &= 0xFF;

        $this->cpu->flagSet(Cpu::P_FLAG_ZERO, ($operand == 0));
        $this->cpu->flagSet(Cpu::P_FLAG_NEGATIVE, ($operand >= 0x80));

        return $operand;
    }

    protected function _opcode_lsr($operand)
    {
        assert($operand <= 0xFF);

        $this->cpu->flagSet(Cpu::P_FLAG_NEGATIVE, 0);
        $this->cpu->flagSet(Cpu::P_FLAG_CARRY, $operand & 0x1 == 0x1);
        $operand >>= 1;
        $operand &= 0xFF;

        $this->cpu->flagSet(Cpu::P_FLAG_ZERO, ($operand == 0));
        return $operand;
    }

    protected function _opcode_rol($operand)
    {
        assert($operand <= 0xFF);

        $carry = $this->cpu->flagIsSet(Cpu::P_FLAG_CARRY) ? 0x01 : 0x00;
        $this->cpu->flagSet(Cpu::P_FLAG_CARRY, ($operand & 0x80) == 0x80);

        $operand <<= 1;
        $operand &= 0xFE;   // Make sure bit 0 is 0
        $operand |= $carry;

        $this->cpu->flagSet(Cpu::P_FLAG_ZERO, ($operand == 0));
        $this->cpu->flagSet(Cpu::P_FLAG_NEGATIVE, ($operand >= 0x80));

        return $operand;
    }

    protected function _opcode_ror($operand)
    {
        assert($operand <= 0xFF);

        $carry = $this->cpu->flagIsSet(Cpu::P_FLAG_CARRY) ? 0x80 : 0x00;
        $this->cpu->flagSet(Cpu::P_FLAG_CARRY, $operand & 0x1 == 0x1);

        $operand >>= 1;
        $operand &= 0x7F;   // Make sure bit 7 is 0
        $operand |= $carry;

        $this->cpu->flagSet(Cpu::P_FLAG_ZERO, ($operand == 0));
        $this->cpu->flagSet(Cpu::P_FLAG_NEGATIVE, ($operand >= 0x80));

        return $operand;
    }


    protected function _opcode_lda($operand)
    {
        assert($operand <= 0xFF);

        $this->cpu->flagSet(Cpu::P_FLAG_ZERO, ($operand == 0));
        $this->cpu->flagSet(Cpu::P_FLAG_NEGATIVE, ($operand >= 0x80));

        $this->cpu->writeA($operand);
    }

    protected function _opcode_ldy($operand)
    {
        assert($operand <= 0xFF);

        $this->cpu->flagSet(Cpu::P_FLAG_ZERO, ($operand == 0));
        $this->cpu->flagSet(Cpu::P_FLAG_NEGATIVE, ($operand >= 0x80));

        $this->cpu->writeY($operand);
    }

    protected function _opcode_ldx($operand)
    {
        assert($operand <= 0xFF);

        $this->cpu->flagSet(Cpu::P_FLAG_ZERO, ($operand == 0));
        $this->cpu->flagSet(Cpu::P_FLAG_NEGATIVE, ($operand >= 0x80));

        $this->cpu->writeX($operand);
    }

    protected function _opcode_lds($operand)
    {
        assert($operand <= 0xFF);

        $this->cpu->writeS($operand);
    }

    protected function _opcode_bit($operand)
    {
        assert($operand <= 0xFF);
        $result = ($this->cpu->readA() & $operand);

        $this->cpu->flagSet(Cpu::P_FLAG_ZERO, ($result == 0));
        $this->cpu->flagSet(Cpu::P_FLAG_OVERFLOW, ($operand & 0x40) == 0x40);
        $this->cpu->flagSet(Cpu::P_FLAG_NEGATIVE, ($operand & 0x80) == 0x80);
    }

    protected function _opcode_incdec($operand, $incr)
    {
        assert($operand <= 0xFF);
        $operand += $incr;

        // Wrap value in case it's < 0 or > 255
        $operand &= 0xFF;

        $this->cpu->flagSet(Cpu::P_FLAG_ZERO, $operand == 0);
        $this->cpu->flagSet(Cpu::P_FLAG_NEGATIVE, ($operand & 0x80) == 0x80);

        return $operand;
    }

    protected function _opcode_adc($operand)
    {
        assert($operand <= 0xFF);

        if ($this->cpu->flagIsSet(Cpu::P_FLAG_DECIMAL)) {
            $value = $this->_opcode_adc_decimal($operand);
        } else {
            $value = $this->_opcode_adc_binary($operand);
        }

        $this->cpu->writeA($value);
    }

    protected function _opcode_sbc($operand)
    {
        assert($operand <= 0xFF);

        if ($this->cpu->flagIsSet(Cpu::P_FLAG_DECIMAL)) {
            $value = $this->_opcode_sbc_decimal($operand);
        } else {
            $value = $this->_opcode_sbc_binary($operand);
        }

        $this->cpu->writeA($value);
    }

    protected function _opcode_adc_decimal($operand)
    {
        assert($operand <= 0xFF);

        $carry = $this->cpu->flagIsSet(Cpu::P_FLAG_CARRY) ? 1 : 0;
        $value = ($this->cpu->readA() & 0x0F) + ($operand & 0x0F) + $carry;
        if ($value > 0x09) {
            $value += 0x06;
        }
        if ($value <= 0x0F) {
            $value = ($value & 0x0F) + ($this->cpu->readA() & 0xF0) + ($operand & 0xF0);
        } else {
            $value = ($value & 0x0F) + ($this->cpu->readA() & 0xF0) + ($operand & 0xF0) + 0x10;
        }

        $this->cpu->flagSet(Cpu::P_FLAG_ZERO, (($this->cpu->readA() + $operand + $carry) & 0xFF) == 0);
        $this->cpu->flagSet(Cpu::P_FLAG_NEGATIVE, ($value & 0x80) == 0x80);
        $overflow = (($this->cpu->readA() ^ $value) & 0x80) && !(($this->cpu->readA() ^ $operand) & 0x80);
        $this->cpu->flagSet(Cpu::P_FLAG_OVERFLOW, $overflow);

        if (($value & 0x1F0) > 0x90) {
            $value += 0x60;
        }
        $this->cpu->flagSet(Cpu::P_FLAG_CARRY, (($value & 0xFF0) > 0xF0));

        return $value & 0xFF;
    }

    protected function _opcode_sbc_decimal($operand)
    {
        assert($operand <= 0xFF);

        $carry = $this->cpu->flagIsSet(Cpu::P_FLAG_CARRY) ? 0 : 1;  // add 1 when carry is NOT set

        // calculate actual value in decimal
        $value_dec = (($this->cpu->readA() & 0x0F) - ($operand & 0x0F) - ($carry)) & 0xFFFF;
        if ($value_dec & 0x10) {
            $value_dec = (($value_dec - 6) & 0x0F) | (($this->cpu->readA() & 0xF0) - ($operand & 0xF0) - 0x10);
        } else {
            $value_dec = ($value_dec & 0x0F) | (($this->cpu->readA() & 0xF0) - ($operand & 0xF0));
        }
        if ($value_dec & 0x100) {
            $value_dec -= 0x60;
        }

        // Flags are still processed by value, not value_dec
        $value = ($this->cpu->readA() - $operand - $carry) & 0xFFFF;
        $this->cpu->flagSet(Cpu::P_FLAG_CARRY, ($value <= 0xFF));
        $this->cpu->flagSet(Cpu::P_FLAG_ZERO, ($value & 0xFF) == 0);
        $this->cpu->flagSet(Cpu::P_FLAG_NEGATIVE, ($value & 0x80) == 0x80);

        $overflow = ((($this->cpu->readA() ^ $value) & 0x80) > 0) && ((($this->cpu->readA() ^ $operand) & 0x80) > 0);
        $this->cpu->flagSet(Cpu::P_FLAG_OVERFLOW, $overflow);

        return $value_dec & 0xFF;
    }

    protected function _opcode_adc_binary($operand)
    {
        assert($operand <= 0xFF);

        $carry = $this->cpu->flagIsSet(Cpu::P_FLAG_CARRY) ? 1 : 0;
        $value = $this->cpu->readA() + $operand + $carry;

        $this->cpu->flagSet(Cpu::P_FLAG_CARRY, ($value > 0xFF));
        $this->cpu->flagSet(Cpu::P_FLAG_ZERO, ($value & 0xFF) == 0);
        $this->cpu->flagSet(Cpu::P_FLAG_NEGATIVE, ($value & 0x80) == 0x80);

        $overflow = !(($this->cpu->readA() ^ $operand) & 0x80) && (($this->cpu->readA() ^ $value) & 0x80);
        $this->cpu->flagSet(Cpu::P_FLAG_OVERFLOW, $overflow);

        return $value & 0xFF;

    }

    protected function _opcode_sbc_binary($operand)
    {
        assert($operand <= 0xFF);

        $carry = $this->cpu->flagIsSet(Cpu::P_FLAG_CARRY) ? 0 : 1;  // add 1 when carry is NOT set
        $value = ($this->cpu->readA() - $operand - $carry) & 0xFFFF;

        $this->cpu->flagSet(Cpu::P_FLAG_CARRY, ($value <= 0xFF));
        $this->cpu->flagSet(Cpu::P_FLAG_ZERO, ($value & 0xFF) == 0);
        $this->cpu->flagSet(Cpu::P_FLAG_NEGATIVE, ($value & 0x80) == 0x80);

        $overflow = ((($this->cpu->readA() ^ $value) & 0x80) > 0) && ((($this->cpu->readA() ^ $operand) & 0x80) > 0);
        $this->cpu->flagSet(Cpu::P_FLAG_OVERFLOW, $overflow);

        return $value & 0xFF;
    }

    protected function _opcode_cmp($lhs, $rhs) {
        assert($lhs <= 0xFF);
        assert($rhs <= 0xFF);

        $this->cpu->flagSet(Cpu::P_FLAG_CARRY, ($lhs >= $rhs));
        $this->cpu->flagSet(Cpu::P_FLAG_ZERO, ($lhs == $rhs));

        $res = ($lhs - $rhs);
        $this->cpu->flagSet(Cpu::P_FLAG_NEGATIVE, ($res & 0x80) == 0x80);
    }

    protected function _opcode_ora($lhs, $rhs) {
        assert($lhs <= 0xFF);
        assert($rhs <= 0xFF);

        $res = ($lhs | $rhs);

        $this->cpu->flagSet(Cpu::P_FLAG_ZERO, ($res == 0));
        $this->cpu->flagSet(Cpu::P_FLAG_NEGATIVE, ($res & 0x80) == 0x80);

        return $res;
    }

    protected function _opcode_and($lhs, $rhs) {
        assert($lhs <= 0xFF);
        assert($rhs <= 0xFF);

        $res = ($lhs & $rhs);

        $this->cpu->flagSet(Cpu::P_FLAG_ZERO, ($res == 0));
        $this->cpu->flagSet(Cpu::P_FLAG_NEGATIVE, ($res & 0x80) == 0x80);

        return $res;
    }

    protected function _opcode_eor($lhs, $rhs) {
        assert($lhs <= 0xFF);
        assert($rhs <= 0xFF);

        $res = ($lhs ^ $rhs);

        $this->cpu->flagSet(Cpu::P_FLAG_ZERO, ($res == 0));
        $this->cpu->flagSet(Cpu::P_FLAG_NEGATIVE, ($res & 0x80) == 0x80);

        return $res;
    }



    /*
     * Addressing modes. Each fetch*() method will fetch data based on data write from the current PC.
     * Some addressing mode can also return a location (by reference). This is often needed to not only use the
     * given value, but also to write the value back to the given location (like when adding a number to an
     * absolute location).
     */


    /**
     * Fetch immediate
     *
     * @return mixed
     */
    protected function fetchImm() {
        return $this->cpu->read8FromPc();
    }

    /**
     * Fetch ZeroPage
     *
     * @param null $location
     * @return int
     */
    protected function fetchZp(&$location = null) {
        $location = Memory::ZERO_PAGE_ADDR + $this->cpu->read8FromPc();
        return $this->cpu->memory->read8($location);
    }

    /**
     * Fetch ZeroPage,X
     *
     * @param null $location
     * @return int
     */
    protected function fetchZpx(&$location = null) {
        // Wrap around if we cross the zero page
        $location = Memory::ZERO_PAGE_ADDR + (($this->cpu->read8FromPc() + $this->cpu->readX()) & 0xFF);
        return $this->cpu->memory->read8($location);
    }

    /**
     * Fetch ZeroPage,Y
     * @param null $location
     * @return int
     */
    protected function fetchZpy(&$location = null) {
        // Wrap around if we cross the zero page
        $location = Memory::ZERO_PAGE_ADDR + (   ($this->cpu->read8FromPc() + $this->cpu->readY()   ) & 0xFF);
        return $this->cpu->memory->read8($location);
    }

    /**
     * Fetch Absolute
     *
     * @param null $location
     * @return int
     */
    protected function fetchAbs(&$location = null) {
        $location = $this->cpu->read16FromPc();
        return $this->cpu->memory->read8($location);
    }

    /**
     * Fetch Absolute,X
     *
     * @param null $location
     * @return int
     */
    protected function fetchAbx(&$location = null) {
        $location = $this->cpu->read16FromPc();
        if ($this->cpu->memory->willCrossPage($location, $this->cpu->readX())) {
            // Increase tick when we cross page border
            $this->cpu->tick(1);
        }

        // Make sure location holds the actual location
        $location += $this->cpu->readX();
        return $this->cpu->memory->read8($location);
    }

    /**
     * Fetch Absolute,Y
     *
     * @param null $location
     * @return int
     */
    protected function fetchAby(&$location = null) {
        $location = $this->cpu->read16FromPc();
        if ($this->cpu->memory->willCrossPage($location, $this->cpu->readY())) {
            // Increase tick when we cross page border
            $this->cpu->tick(1);
        }

        // Make sure location holds the actual location
        $location += $this->cpu->readY();
        return $this->cpu->memory->read8($location);
    }

    /**
     * Fetch Indirect,Indexed
     *
     * @param null $location
     * @return int
     */
    protected function fetchIdx(&$location = null) {
        $location = Memory::ZERO_PAGE_ADDR + (($this->cpu->read8FromPc() + $this->cpu->readX()) & 0xFF);
        $location = $this->cpu->memory->read16($location);

        return $this->cpu->memory->read8($location);
    }

    /**
     * Fetch Indexed, Indirect
     *
     * @param null $location
     * @return int
     */
    protected function fetchIdy(&$location = null) {
        $location = Memory::ZERO_PAGE_ADDR + $this->cpu->read8FromPc();
        $location = $this->cpu->memory->read16($location);

        if ($this->cpu->memory->willCrossPage($location, $this->cpu->readY())) {
            // Increase tick when we cross page border
            $this->cpu->tick(1);
        }

        // Make sure location holds the actual location
        $location += $this->cpu->readY();
        return $this->cpu->memory->read8($location);
    }

    /**
     * Fetch Indirect (like absolute, but returns 16 bits AND wraps around page if needed (only used for JMP)
     *
     * @return int
     */
    protected function fetchIndirect() {
        $location = $this->cpu->read16FromPc();
        return $this->cpu->memory->read16($location, Memory::WRAP_BOUNDARY);
    }

}
