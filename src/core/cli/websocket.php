<?php
declare(strict_types = 1);

namespace apex\core\cli;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\app\msg\ws_server;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;



/**
 * Web socket server class.
 */
class websocket
{


/**
 * Start the web socket server, and begin listening for connections.
 *
 * @param iterable $args The arguments passed to the command cline.
 */
public function process(...$args)
{ 

    // Initialize server
    $http = new HttpServer(new WsServer(new ws_server()));
    $server = IoServer::factory($http, 8194);

    // Echo message
    echo "Listening to web socket connections...\n";

// Run server
$server->run();

}


}

