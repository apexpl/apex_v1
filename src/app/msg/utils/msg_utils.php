<?php
declare(strict_types = 1);

namespace apex\app\msg\utils;

use apex\app;
use apex\svc\db;
use apex\svc\debug;
use apex\svc\redis;
use apex\svc\components;
use apex\app\msg\objects\event_response;
use apex\app\interfaces\msg\EventMessageInterface;
use apex\app\interfaces\msg\EventResponseInterface;
use apex\app\exceptions\ApexException;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;


/**
 * Small utilities class for messaging services with Apex, such as connecting 
 * to the RabbitMQ server, and so on. 
 */
class msg_utils
{


    // Properties
    private $rabbitmq_conn = null;


/**
 * Get connection to the RabbitMQ server 
 */
final public function get_rabbitmq_connection()
{ 

    // Check if already connected
    if ($this->rabbitmq_conn != null) { 
        return $this->rabbitmq_conn;
    }

    // Debug
    debug::add(5, "Starting connection to RabbitMQ");

    // Get connection information
    $vars = $this->get_rabbitmq_connection_info();

    // Try to connect
    try {
        $connection = new AMQPStreamConnection($vars['host'], $vars['port'], $vars['user'], $vars['pass']);
    } catch (AMQPConnectionClosedException $e) { 
        throw new ApexException('emergency', "Unable to connect to RabbitMQ.  Check the connection information, and ensure it is correct.");
    }

    // Debug
    debug::add(5, "Successfully established connection to RabbitMQ");

    // Return
    $this->rabbitmq_conn = $connection;
    return $connection;

}

/**
 * Get RabbitMQ connection information 
 *
 * @return array Connection info for RabbitMQ
 */
public function get_rabbitmq_connection_info()
{ 

    // Get connection info
    if (!$vars = redis::hgetall('config:rabbitmq')) { 
        $vars = array(
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'pass' => 'guest'
        );
    }

    // Return
    return $vars;

}

/**
 * Get listeners for a given routing key 
 *
 * @param string $routing_key The routing key to get listeners for.
 *
 * @return array One-dimensional name of class files that act as listeners
 */
final public function get_listeners(string $routing_key):iterable
{ 

    // Parse key
    if (preg_match("/^(.+?)\.(.+?)\..+$/", $routing_key, $match)) { 
        $routing_key = $match[1] . '.' . $match[2];
    }

    // Get listeners
    $redis_key = 'config:worker:' . $routing_key;
    $listeners = redis::smembers($redis_key) ?? [];

    // Return
    return $listeners;

}

/**
 * Dispatch a message locally 
 *
 * @param EventMessageInterface $msg The EventMessageInterface object of the message to dispatch.
 *
 * @return EventResponseInterface The response of the dispatched message
 */
public function dispatch_locally(EventMessageInterface $msg):eventResponseInterface
{ 

    // Initialize
    $function_name = $msg->get_function();
    $response = new event_response($msg);

    // Add message to container
    app::set(EventMessageInterface::class, $msg);

    // Go through workers
    $workers = $this->get_listeners($msg->get_routing_key());
    foreach ($workers as $worker_alias) { 
        list($package, $alias) = explode(':', $worker_alias, 2);

        // Check for exception
        if ($response->get_status() == 'error') { 
            break;
        }

        // Load component
        if (!$worker = components::load('worker', $alias, $package)) { 
            debug::add(1, tr("Unable to load RPC worker, package: {1}, alias: {2}", $package, $alias), 'critical');
            continue;
        }

        // Execute, if method exists
        if (method_exists($worker, $function_name)) { 
            debug::add(5, tr("Executing single RPC call to routing key: {1} for the package: {2}", $msg->get_routing_key(true), $package));

            // Execute method
            try {
                $res = components::call($function_name, 'worker', $alias, $package, '', ['msg' => $msg]);
            } catch (ApexException $e) { 
                $response->set_exception($e);
                $res = false;
            }

            // Set false, if direct message and not true
            if ($msg->get_type() == 'direct' && $res !== true) { 
                $res = false; 
                $response->set_status('fail');
            }

            // Add  response
        $class_name = "apex\\" . $package . "\\worker\\" . $alias;
            $response->add_response($package, $class_name, $function_name, $res);
            debug::add(5, tr("Completed execution of single RPC call to routing key: {1} for the package: {2}", $msg->get_routing_key(true), $package));
        }
    }

    // Get the event queue
    $response->add_event_queue();

    // Return
    return $response;

}


}

