<?php
declare(strict_types = 1);

namespace apex\app\msg;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\svc\components;
use apex\app\msg\utils\msg_utils;
use apex\app\interfaces\msg\ListenerInterface;
use apex\app\interfaces\msg\EventMessageInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


/**
 * Listens for any incoming RPC or one-way direct messages.  Used when 
 * horizontal scaling is implemented, and this is a back-end application 
 * server within the cluster. 
 */
class listener extends msg_utils implements ListenerInterface
{

    private $channel_name = '';

/**
 * Listen 
 */
public function listen()
{ 

    // Connect
    $connection = $this->get_rabbitmq_connection();
    $channel = $connection->channel();

    // Define exchange and queue
    //$this->channel->exchange_declare($this->channel_name, 'topic', false, false, false);
    list($callback_queue, ,) = $channel->queue_declare('', false, false, true, false);

    // Only one message at a time per-listener
    $channel->basic_qos(null, 1, null);

    // Define callback message
    $callback = function($request) { 

        // Parse message body
        $msg = unserialize($request->body);
        if (!$msg instanceof EventMessageInterface) { 
            debug::add(1, tr("Invalid RPC call made, did not receive a EventMessageInterface object"), 'alert');
        return false;
        }

        // Get response
        $response = $this->dispatch_locally($msg);

        // Send response
        if ($msg->get_type() == 'rpc') { 
            $rmsg = new AMQPMessage(serialize($response), array('correlation_id' => $request->get('correlation_id')));
            $request->delivery_info['channel']->basic_publish($rmsg, '', $request->get('reply_to'));
        }

        // Send acknowledgement
        $request->delivery_info['channel']->basic_ack($request->delivery_info['delivery_tag']);
    };

    // Consume messages
    $channel->basic_consume($this->channel_name, '', false, false, false, false, $callback);
    while (count($channel->callbacks)) { 
        $channel->wait();
    }

    // Close channel
    $channel->close();
    $connection->close();

}

/**
 * Alias for the 'get_listeners()' method above, simply to add full compliance 
 * with PSR-14.
 *
 * @param EventMessageInterface $msg The message to get listeners for. 
 */
public function getListenersForEvent(EventMessageInterface $msg):iterable { return $this->get_listeners($msg); }


}

