<?php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;

class MessageController implements MessageComponentInterface {
    protected $clients;
    private $loop;
    
    public function __construct($loop) {
        $this->clients = new \SplObjectStorage;
        $this->loop = $loop;
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {        
        $jsonMsg = json_decode($msg);
        switch($jsonMsg->app){
            case "start":
                echo "start\n";
                $eventListener = new GameLoopObject($from);
                $eventListener->timer = $this->loop->addPeriodicTimer(GameLoopObject::WAITTIME, $eventListener->onStart($jsonMsg) );
                $this->clients->offsetSet($from, $eventListener);
                break;
            case "keydown":
                $eventListener = $this->clients->offsetGet($from);
                $eventListener->onKeyDown($jsonMsg);
                break;
            case "keyup":
                $eventListener = $this->clients->offsetGet($from);
                $eventListener->onKeyUp($jsonMsg);
                break;
            default:
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->loop->cancelTimer($this->clients->offsetGet($conn)->timer);
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}

