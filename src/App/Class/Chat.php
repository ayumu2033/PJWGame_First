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
    private $Hz = 10;
    public function setHz($Hz){ 
        $this->Hz = $Hz;
    }

    public function __construct($connection){
        $this->connection = $connection;
    }

    public function onStart($jsonMsg){
        $this->pos = (object)["x"=>0,"y"=>$jsonMsg->height/2];

        return function() use ($jsonMsg){
            $result = (object)[];
                $result->prePos = $this->pos;
                $nowX = $this->pos->x+1;
                $nowX = $nowX > $jsonMsg->width ? 0 : $nowX;
                $nowY = sin($nowX*$this->Hz/100)*($jsonMsg->height/2-20) + $jsonMsg->height/2;
                $result->Pos = (object)["x"=>$nowX, "y"=>$nowY];
                $this->connection->send(json_encode($result));
                $this->pos->x = $nowX; $this->pos->y = $nowY;
        };
    }
}

class Chat implements MessageComponentInterface {
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
        if($jsonMsg->app === "start"){
            echo "start\n";
            $eventListener = new UserEventListener($from);
            $eventListener->timer = $this->loop->addPeriodicTimer(UserEventListener::WAITTIME, $eventListener->onStart($jsonMsg) );
            $this->clients->offsetSet($from, $eventListener);
        }else if($jsonMsg->app === "changeHz"){
            echo "changeHz\n";
            $eventListener = $this->clients->offsetGet($from);
            $eventListener->setHz($jsonMsg->Hz);
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

