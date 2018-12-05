<?php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;

class UserEventListener {
    public const WAITTIME = 0.0075;

    public $connection;
    public $timer;

    private $pos;
    private $Hz;
    private $velocity;

    public function setHz($Hz){ 
        $this->Hz->Set($Hz, null);
    }

    public function setVelocity($v){ 
        $nowV = $this->velocity->Get();
        $this->velocity->Set((object)["x"=>($nowV->x + $v->x),"y"=>($nowV->y + $v->y)], null);
    }

    public function __construct($connection){
        $this->connection = $connection;
        $this->pos = new SyncData(null);
        $this->velocity = new SyncData((object)["x"=>0,"y"=>0]);
        $this->Hz = new SyncData(10);
    }

    public function onStart($jsonMsg){
        $this->pos->Set((object)["x"=>0,"y"=>$jsonMsg->height/2], null);

        return function() use ($jsonMsg){
            $result = (object)[];
            $nowX = $this->pos->Get()->x + $this->velocity->Get()->x;
            $nowX = $nowX > $jsonMsg->width ? 0 : ($nowX < 0 ? $jsonMsg->width : $nowX);
            $nowY = $this->pos->Get()->y + $this->velocity->Get()->y;
            $nowY = $nowY > $jsonMsg->height ? 0 : ($nowY < 0 ? $jsonMsg->height : $nowY);
            $result->Pos = (object)["x"=>$nowX, "y"=>$nowY];
            $this->pos->Set((object)["x"=>$nowX,"y"=>$nowY], null);

            $this->connection->send(json_encode($result));
        };
    }

    public function onKeyDown($jsonMsg){
        $keydownV = (object)["x"=>0,"y"=>0];
        switch($jsonMsg->key){
            case "ArrowUp":
                $keydownV->y = -1;
                $this->setVelocity($keydownV);
                break;
            case "ArrowRight":
                $keydownV->x = 1;
                $this->setVelocity($keydownV);
                break;
            case "ArrowDown":
                $keydownV->y = 1;
                $this->setVelocity($keydownV);
                break;
            case "ArrowLeft":
                $keydownV->x = -1;
                $this->setVelocity($keydownV);
                break;
            default:
        }
    }

    public function onKeyUp($jsonMsg){
        $keydownV = (object)["x"=>0,"y"=>0];
        switch($jsonMsg->key){
            case "ArrowUp":
                $keydownV->y = 1;
                $this->setVelocity($keydownV);
                break;
            case "ArrowRight":
                $keydownV->x = -1;
                $this->setVelocity($keydownV);
                break;
            case "ArrowDown":
                $keydownV->y = -1;
                $this->setVelocity($keydownV);
                break;
            case "ArrowLeft":
                $keydownV->x = 1;
                $this->setVelocity($keydownV);
                break;
            default:
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
        $numRecv = count($this->clients) - 1;
        // echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
        //     , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');
        
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

