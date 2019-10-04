<?php
declare(strict_types = 1);

namespace apex\app\msg\objects;

use apex\app;
use apex\app\interfaces\msg\EventMessageInterface;


/**
 * Creates and returns a message_event object, which is used to send direct 
 * and RPC calls via RabbitMQ and other event dispatches used within Apex. 
 */
class event_message implements EventMessageInterface
{


    // Properties
    private $type = 'rpc';
    private $routing_key;
    private $function;
    private $caller = [];
    private $request = [];
    private $params;

/**
 * Create new event message 
 *
 * @param string $routing_key The routing key to dispatch the message to.
 * @param mixed $params Iterable of any params you want to pass.
 */
public function __construct(string $routing_key, ...$params)
{ 

    // Set request array
    $this->request = array(
        'area' => app::get_area(),
        'uri' => app::get_uri(),
        'request_method' => app::get_method(),
        'userid' => app::get_userid(),
        'ip_address' => app::get_ip(),
        'user_agent' => app::get_user_agent()
    );

    // Get caller function / class
    $trace = debug_backtrace();
    $this->caller = array(
        'file' => trim(str_replace(SITE_PATH, '', $trace[0]['file']), '/') ?? '',
        'line' => $trace[0]['line'] ?? 0,
        'function' => $trace[1]['function'] ?? '',
        'class' => $trace[1]['class'] ?? ''
    );

    // Parse routing key
    if (!preg_match("/^(\w+?)\.(\w+?)\.(\w+)$/", strtolower($routing_key), $match)) { 
        echo "Invalid routing key";
    }
    // Define message
    $this->routing_key = $match[1] . '.' . $match[2];
    $this->function = $match[3];
    $this->params = $params;

}

/**
 * Set the message type. 
 *
 * @param string $type Must be either 'rpc' or 'direct'.
 */
public function set_type(string $type) { $this->type = $type; }

/**
 * Get the message type. 
 *
 * @return string The message type
 */
public function get_type():string { return $this->type; }

/**
 * Get the routing key 
 *
 * @param bool $return_full If true, will return the routing key with function name.  Defaults to false.
 *
 * @return string $routing_key The routing key
 */
public function get_routing_key(bool $return_full = false):string
{ 

    // Return
    if ($return_full === true) { 
        return $this->routing_key . '.' . $this->function;
    } else { 
        return $this->routing_key;
    }

}

/**
 * et the function name. 
 *
 * @return string The function name to call.
 */
public function get_function():string { return $this->function; }

/**
 * Get the caller array. 
 *
 * @return array The caller function / class.
 */
public function get_caller():array { return $this->caller; }

/**
 * Get the contents of the request. 
 *
 * @return array Various information on the request such as URI, area, IP address, etc.
 */
public function get_request():array { return $this->request; }

/**
 * Get the params of the request. 
 *
 * @return array The params of the request.
 */
public function get_params()
{ 
    return count($this->params) == 1 ? $this->params[0] : $this->params;
}


}

