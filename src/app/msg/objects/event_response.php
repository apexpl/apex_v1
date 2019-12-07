<?php
declare(strict_types = 1);

namespace apex\app\msg\objects;

use apex\app;
use apex\app\msg\objects\event_message;
use apex\app\interfaces\msg\EventMessageInterface;
use apex\app\interfaces\msg\EventResponseInterface;


/**
 * Creates a EventResponseInterface object, which is used to send back to the 
 * front-end software during a RPC call.  This is the response created and 
 * returned by the RPC listeners. 
 */
class event_response extends event_message implements EventResponseInterface
{


    // Request properties
    private $type;
    private $routing_key;
    private $function;
    private $caller = [];
    private $request = [];
    private $params;

    // Response properties
    private $status = 'ok';
    private $called = [];
    private $response = [];
    private $exception;
    private $event_queue = [];

/**
 * Constructor.  Get the EventMessageInterface, and set necessary properties. 
 *
 * @param EventMessageInterface $msg The message that was dispatched.
 */
public function __construct(EventMessageInterface $msg)
{ 

    // Import perties
    $this->import_properties($msg);

}

/**
 * Set the status 
 *
 * @param string $status The overall status of the response.  Must be either 'ok', 'fail', or 'error'
 */
public function set_status(string $status) { $this->status = $status; }

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
public function add_response(string $package, string $class_name, string $method, $response)
{ 

    // Add response
    $this->called[] = array(
        $class_name,
        $method
    );
    $this->response[$package] = $response;

    // Change status, if needed
    if ($this->get_type() == 'direct' && $response !== true) { 
        $this->set_status('fail');
    }
}

/**
 * Set an exception 
 *
 * When an error occurs, this method is triggered which sets the status of the 
 * response to 'error' and includes the exception within. 
 *
 * @param Exception $e The exaction to add
 */
public function set_exception($e)
{ 
    $this->set_status('error');
    $this->exception = $e->getMessage();
}

/**
 * Set the event queue
 *
 * Simply calls the app::get_event_queue() method to retrieve any 
 * actions that occured during process that may change the 
 * output of the request.  This is passed back to the caller for processing.
 */
public function add_event_queue()
{
    $this->event_queue = app::get_event_queue();
}

/**
 * Get status of the response 
 *
 * @return string The status of the response
 */
public function get_status():string { return $this->status; }

/**
 * Get all classes and methods called during execution. 
 *
 * @return array All classes and methods called.
 */
public function get_called():array { return $this->called; }

/**
 * Get contents of the response, either a specific package, or all packages 
 * combined in an array. 
 *
 * @param string $package Optional package alias to return response of.  If blank, returns array of all responses from all packages.
 *
 * @return mixed Either response from a specific package if defined, or all responses.
 */
public function get_response(string $package = '')
{ 

    // Return as needed
    if ($package != '') { 
        return $this->response[$package] ?? null;
    } else { 
        return $this->response;
    }

}

/**
 * get the exception, only applicable if status is 'error'. 
 *
 * @return exception The exception object
 */
public function get_exception() { return $this->exception; }

/**
 * Get the event queue
 *
 * @return array The event queue
 */
public function get_event_queue():array { return $this->event_queue; }

}

