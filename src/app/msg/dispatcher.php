<?php
declare(strict_types = 1);

namespace apex\app\msg;

use apex\app;
use apex\svc\debug;
use apex\app\msg\utils\msg_utils;
use apex\app\interfaces\msg\DispatcherInterface;
use apex\app\interfaces\msg\EventMessageInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


/**
 * Event Dispatcher
 *
 * Service: apex\svc\msg
 *
 * Handles all the two-way RPC calls between Apex and RabbitMQ.  Messages sent 
 * here will not be returned until a response has been received from all 
 * listeners. 
 * 
* This class is available within the services container, meaning its methods can be accessed statically via the 
 * service singleton as shown below.
 *
 * PHP Example
 * --------------------------------------------------
 * 
 * <?php
 *
 * namespace apex;
 *
 * use apex\app;
 * use apex\svc\msg;
 * use apex\app\msg\objects\event_message;
 *
 * // Set some message vars
 * $vars = array(
 *     'name' => 'John', 
 *      'email' => 'john@doe.com'
 * );
 *
 * // Send a RPC call
 * $msg = new event_message('mypackage.rkey.method', $vars);
 * $response = msg::dispatch($msg)->get_response('mypackage');
 *
 */
class dispatcher   extends msg_utils implements DispatcherInterface
{


    private $connection = '';
    private $channel;
    private $callback_queue;
    private $response;
    private $corr_id;
    private $channel_name = 'apex';


/**
 * Construct.  Grab some injected dependencies we need. 
 *
 * @param string $channel_name The channel nqme to send messages though, defaults to 'apex'.
 */
public function __construct(string $channel_name = 'apex')
{ 

    // Set variables
    $this->channel_name = $channel_name;

}

/**
 * Dispatch a message via RPC to all available listeners, and wait for 
 * response. 
 *
 * @param EventMessageInterface $msg The message to dispatch.
 */
public function dispatch(EventMessageInterface $msg)
{ 

    // Debug
    debug::add(4, tr("Sending RPC command to {1}", $msg->get_routing_key(true)));

    // Check for all-in-one server
    if (app::_config('core:server_type') == 'all' || app::_config('core:server_type') == 'app') { 
        return $this->dispatch_locally($msg);
    }

    // Open connection
    if (!$this->connection) { 
        $this->connection = $this->get_rabbitmq_connection();
        $this->channel = $this->connection->channel();
        list($this->callback_queue, ,) = $this->channel->queue_declare('', false, false, true, false);
        $this->channel->basic_consume($this->callback_queue, '', false, false, false, false, array($this, 'onresponse'));
    }

    // Set variables
    $this->response = null;
    $this->corr_id = uniqid();

    // Define message
    if ($msg->get_type() == 'direct') { 

        $msg = new AMQPMessage(
            serialize($msg),
            array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)
        );

    } else { 

        $msg = new AMQPMessage(
            serialize($msg),
            array(
                'correlation_id' => $this->corr_id,
                'reply_to' => $this->callback_queue
            )
        );
    }

    // Publish message
    $this->channel->basic_publish($msg, $this->channel_name, $msg->get_routing_key());

    // Wait for response
    $this->channel->wait(false, false, 5);

    // Return
    return unserialize($response);

}


}

