<?php
declare(strict_types = 1);


namespace apex\app\interfaces\msg;

use apex\app\interfaces\msg\EventMessageInterface;
/**
 * Event response interface
 *
 * This is the response received when dispatching messages 
 * via RPC or one-way direct messages.
 */
interface EventResponseInterface extends EventMessageInterface
{


/**
 * Set the status 
 *
 * @param string $status The overall status of the response.  Must be either 'ok', 'fail', or 'error'
 */
public function set_status(string $status);


/**
 * Add a response 
 *
 * One RPC call can call methods without mltiple packages.  This method is 
 * called after the RPC call is executed against any package, and adds the 
 * response from the package into the overall response object. 
 *
 * @param string $package The package the response came from
 * @param string $class_name The class name that was executed
 * @param string $method The method name that was executed
 * @param mixed The contents of the response
 */
public function add_response(string $package, string $class_name, string $method, $response);


/**
 * Set an exception 
 *
 * When an error occurs, this method is triggered which sets the status of the 
 * response to 'error' and includes the exception within. 
 *
 * @param Exception $e The exaction to add
 */
public function set_exception($e);


/**
 * Get status of the response 
 *
 * @return string The status of the response
 */
public function get_status():string;


/**
 * Get all classes and methods called during execution. 
 *
 * @return array All classes and methods called.
 */
public function get_called():array;


/**
 * Get contents of the response, either a specific package, or all packages 
 * combined in an array. 
 *
 * @param string $package Optional package alias to return response of.  If blank, returns array of all responses from all packages.
 *
 * @return mixed Either response from a specific package if defined, or all responses.
 */
public function get_response(string $package = '');


/**
 * get the exception, only applicable if status is 'error'. 
 *
 * @return exception The exception object
 */
public function get_exception();




}


