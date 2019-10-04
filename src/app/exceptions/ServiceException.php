<?php
declare(strict_types = 1);

namespace apex\app\exceptions;

use apex\app;
use apex\app\exceptions\ApexException;


/**
 * Handles the various errors thrown by services / dispatchers, such as 
 * instance not defined, method does not exist, etc. 
 */
class ServiceException   extends ApexException
{



    // Properties
    private $error_codes = array(
    'no_instance' => "No instance has yet been defined for the service {service}",
    'no_method' => "The service {service} does not have the called method, {method}"
    );

/**
 * Construct 
 *
 * @param string $message The exception message
 * @param string $service The service alias being accessed.
 * @param string $method The method alias being accessed.
 */
public function __construct(string $message, $service = '', $method = '')
{ 

    // Set variables
    $vars = array(
        'service' => $service,
        'method' => $method
    );

    // Get message
    $this->log_level = 'alert';
    $this->message = $this->error_codes[$message] ?? $message;
    $this->message = tr($this->message, $vars);

}


}

