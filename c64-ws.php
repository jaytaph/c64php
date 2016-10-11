<?php

use Mos6510\C64;
use Mos6510\Io\NullIo;
use Mos6510\Logging\FileLogger;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\MessageComponentInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

require_once "./vendor/autoload.php";


/*
 * Trying to see if we can send c64 I/O (monitor, keyboard etc) via websockets. It's probably too slow for a
 * 50hz refresh. Definately if we are sending over 320x200 bytes (and what about borders etc, which makes it ever
 * larger).
 *
 * Does not really work yet..
 */



class c64websocket implements MessageComponentInterface {

    /** @var ConnectionInterface */
    protected $conn = null;

    /** @var C64 */
    protected $c64;

    /**
     * c64websocket constructor.
     * @param C64 $c64
     */
    public function __construct(C64 $c64)
    {
        $this->c64 = $c64;
    }


    function onOpen(ConnectionInterface $conn)
    {
        if ($this->conn != null) {
            echo "No more connections can be established\n";
            throw new \Exception("Connection already present");
        }

        echo "Opened connection";
        $this->conn = $conn;

        /*
        $this->c64->boot();
        while (true) {
            $this->c64->cycle();
            usleep(100 * 1000);
            $this->conn->send(".");
        }
        */


//        while (true) {
            echo "Generating data...";

            $s = "";
            for ($y=0; $y!=200; $y++) {
                for ($x=0; $x!=320; $x++) {
                    $s .= chr(rand(0, 15));
                }
            }

            echo "Sending data...";
            $this->conn->send($s);
//        }
    }

    function onClose(ConnectionInterface $conn)
    {
        $this->conn = null;
        echo "Connection closed";
    }

    function onError(ConnectionInterface $conn, \Exception $e)
    {
        $conn->close();

        $this->conn = null;
        echo "Connection errored";
    }

    function onMessage(ConnectionInterface $from, $msg)
    {
        $this->conn->send("vic returned: ", $msg);
    }
}


$c64 = new C64(new FileLogger("c64-output.log"), new NullIo());

$server = IoServer::factory(new HttpServer(new WsServer(new c64websocket($c64))), 6464);
$server->run();
exit();
