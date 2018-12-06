<?php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;

class UserEventListener {
    public const WAITTIME = 0.0075;

    public $connection;
    public $timer;

    private $objects=[];

    public function __construct($connection){
        $this->connection = $connection;
    }

    public function onStart($jsonMsg){
        $this->objects[] = new MoveableObject(["pos"=>["x"=>0,"y"=>$jsonMsg->height/2]]);

        // ゲームループ
        return function() use ($jsonMsg){
            $result = [];
            foreach($this->objects as $obj){
                $tmp = $obj->onUpdate($jsonMsg);
                if($tmp != null){
                    $result[$obj->getTag()] = $obj->onUpdate($jsonMsg);
                }
            }
            $this->connection->send(json_encode($result));
        };
    }

    public function onKeyDown($jsonMsg){
        echo $jsonMsg->key." is Down\n";
        foreach($this->objects as $obj){
            if(is_callable([$obj, "onKeyDown"])){
                $obj->onKeyDown($jsonMsg);
            }
        }
    }

    public function onKeyUp($jsonMsg){
        echo $jsonMsg->key." is UP\n";
        foreach($this->objects as $obj){
            if(is_callable([$obj, "onKeyUp"])){
                $obj->onKeyUp($jsonMsg);
            }
        }
    }
}

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
                $eventListener = new UserEventListener($from);
                $eventListener->timer = $this->loop->addPeriodicTimer(UserEventListener::WAITTIME, $eventListener->onStart($jsonMsg) );
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

