<?php

class Vic1541 {
    protected $data;

    protected $track_offsets = array(
        0x00000, 0x01500, 0x02A00, 0x03F00, 0x05400, 0x06900, 0x07E00, 0x09300, 0x0A800, 0x0BD00,
        0x0D200, 0x0E700, 0x0FC00, 0x11100, 0x12600, 0x13B00, 0x15000, 0x16500, 0x17800, 0x18B00,
        0x19E00, 0x1B100, 0x1C400, 0x1D700, 0x1EA00, 0x1FC00, 0x20E00, 0x22000, 0x23200, 0x24400,
        0x25600, 0x26700, 0x27800, 0x28900, 0x29A00, 0x2AB00, 0x2BC00, 0x2CD00, 0x2DE00, 0x2EF00,
    );

    protected $track_sizes = array(
        21, 21, 21, 21, 21, 21, 21, 21, 21, 21,
        21, 21, 21, 21, 21, 21, 21, 19, 19, 19,
        19, 19, 19, 19, 18, 18, 18, 18, 18, 18,
        17, 17, 17, 17, 17, 17, 17, 17, 17, 17,
    );

    function __construct($data) {
        $this->data = $data;
    }

    function writeProgram($track, $sector, $filename) {
        $f = fopen($filename, "w");

        while ($track) {
            print "Reading track $track / sector $sector\n";
            $data = $this->readSector($track, $sector);

            $track = ord($data[0]);
            $sector = ord($data[1]);

            if ($track) {
                fwrite($f, substr($data, 2));
            }
        }

        fclose($f);
    }

    function dir() {
        $sector = $this->readSector(18, 1);

        $diskname = "";
        for ($j=0x90; $j!=0x9F; $j++) {
            if (ord($sector[$j]) != 0xA0 && ord($sector[$j]) != 0x00) {
                $diskname .= $sector[$j];
            } else {
                $diskname .= " ";
            }
        }

        printf("\"%-16s\"      %02X %02X\n", $diskname, ord($sector[0xA2]), ord($sector[0xA3]));


        $next_track = 18;
        $next_sector = 1;

        while ($next_track) {
            $sector = $this->readSector($next_track, $next_sector);

            for ($i=0; $i!=8; $i++) {
                $entry = substr($sector, $i * 32, 32);

                $type = (ord($entry[2]) & 0x0F);
                switch ($type) {
                    case 0x00 :
                        $type = "DEL";
                        break;
                    case 0x01 :
                        $type = "SEQ";
                        break;
                    case 0x02 :
                        $type = "PRG";
                        break;
                    case 0x03 :
                        $type = "USR";
                        break;
                    case 0x04 :
                        $type = "REL";
                        break;
                    default:
                        $type = "???";
                }

                $closed = (ord($entry[2]) & 0x40) == 0x40;
                $locked = (ord($entry[2]) & 0x80) == 0x80;

                $name = "";
                for ($j=0x5; $j!=0x14; $j++) {
                    if (ord($entry[$j]) != 0xA0) {
                        $name .= $entry[$j];
                    }
                }
                $size = ord($entry[30]) + ord($entry[31]) * 256;

                $start_track = ord($entry[3]);
                $start_sector = ord($entry[4]);

                printf("%03d \"%-16s\"    %s%s%s (%02d/%02d)\n", $size, $name, $closed ? "*" : " ", $type, $locked ? "<" : " ", $start_track, $start_sector);
            }
            printf("000 BLOCKS FREE\n");

            $next_track = ord($sector[0]);
            $next_sector = ord($sector[1]);
        }
    }

    protected function readSector($track, $sector) {
        $offset = $this->track_offsets[$track - 1];
        if ($sector >= $this->track_sizes[$track - 1]) {
            throw new \Exception("Sector too large for track");
        }

        return substr($this->data, $offset + ($sector * 256), 256);
    }

    protected function readTrack($track) {
        $offset = $this->track_offsets[$track - 1];
        $size = $this->track_sizes[$track - 1] * 256;

        printf("Reading offset %08X\n", $offset);

        return substr($this->data, $offset, $size);
    }

    protected function dump($data, $length = -1) {
        $o = 0;

        if ($length == -1) {
            $length = strlen($data);
        }

        $ascii = "";
        for ($i=0; $i!=$length; $i++) {
            if ($o % 16 == 0) {
                printf("%04X  | ", $o);
            }
            printf("%02X ", ord($data[$i]));
            if (ord($data[$i]) >= 32 && ord($data[$i]) <= 127) {
                $ascii .= $data[$i];
            } else {
                $ascii .= ".";
            }

            if ($o % 4 == 3) {
                printf(" | ");
            }

            if ($o % 16 == 15) {
                printf("$ascii\n", $o);
                $ascii = "";
            }
            $o++;
        }
    }
}


$drive = new Vic1541(file_get_contents($argv[1]), FILE_BINARY);
$drive->dir();

$drive->writeProgram(17, 0, "hello.prg");

