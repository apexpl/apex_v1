<?php
declare(strict_types = 1);

namespace apex\core\cli;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\app\msg\listener;
use apex\app\msg\utils\msg_utils;

/**
  * Listen for RPC and one-way direct messages from RabbitMQ
 */
class rpc
{

    /**
     * @Inject
     * @var listener
     */
    private $server;

    /**
     * @Inject
     * @var msg_utils
     */
    private $utils;

/**
 * Start the RPC server, and begin listening.
 *
 * @param iterable $args The arguments passed to the command cline.
 */
public function process(...$args)
{ 

    // Get connection info
    $vars = $this->utils->get_rabbitmq_connection_info();
    echo "Listening for RPC / one-way direct messages from RabbitMQ on " . $vars['host'] . ':' . $vars['port'] . "...\n\n";

    // Start server
    $this->server->listen();

}


}

