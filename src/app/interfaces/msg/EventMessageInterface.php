<?php
declare(strict_types = 1);


namespace apex\app\interfaces\msg;

/**
 * Event Message Interface.
 *
 * Used for two-way RPC calls, and one-way direct messages.
 *
 */
interface EventMessageInterface 
{


/**
 * Set the message type. 
 *
 * @param string $type Must be either 'rpc' or 'direct'.
 */
public function set_type(string $type);


/**
 * Get the message type. 
 *
 * @return string The message type
 */
public function get_type():string;


/**
 * Get the routing key 
 *
 * @param bool $return_full If true, will return the routing key with function name.  Defaults to false.
 *
 * @return string $routing_key The routing key
 */
public function get_routing_key(bool $return_full = false):string;


/**
 * et the function name. 
 *
 * @return string The function name to call.
 */
public function get_function():string;


/**
 * Get the caller array. 
 *
 * @return array The caller function / class.
 */
public function get_caller():array;


/**
 * Get the contents of the request. 
 *
 * @return array Various information on the request such as URI, area, IP address, etc.
 */
public function get_request():array;


/**
 * Get the params of the request. 
 *
 * @return array The params of the request.
 */
public function get_params();


}



