<?php
use Ratchet\Server\IoServer;
use React\Socket\Server as Reactor;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\LoopInterface;
use React\EventLoop\Factory as LoopFactory;
use MyApp\MessageController;
use MyApp\SyncData;

    require dirname(__DIR__) . '/../vendor/autoload.php';
    
    $loop   = LoopFactory::create();
    $server = new IoServer(
        new HttpServer(
            new WsServer(
                new MessageController($loop)
            )
        ),
        new Reactor('0.0.0.0:8080', $loop),
        $loop
    );

    $server->run();