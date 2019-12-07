<?php
declare(strict_types = 1);

namespace apex\app\exceptions;

use apex\app;
use apex\app\exceptions\ApexException;


/**
 * Handles all errors within the dependency injection container, 
 * such as unable to find class name / dependency, 
 * unable to load class, etc.
 */
class ContainerException   extends ApexException
{


    // Properties
    private $error_codes = array(
        'no_class_name' => "Unable to determine full class name for name: {name}", 
        'unable_load_class' => "Unable to load the class at: {name}", 
        'invalid_param_type' => "Invalid type for the parameter {name}.  Expecting the type {expected_type}, but has type {type} instead.", 
        'no_param_found' => "Unable to find value for injected parameter {name} with the type {type}"
    );

/**
 * Construct 
 *
 * @param string $message The exception message
 * @param string $name The item name.
 * @param string $type The paramater type / class.
 * @param string $expected_type The expected parameter type / class.
 */
public function __construct(string $message, string $name = '', string $type = '', string $expected_type = '')
{ 

    // Set variables
    $vars = array(
        'name' => $name, 
        'type' => $type, 
        'expected_type' => $expected_type
    );

    // Get message
    $this->log_level = 'alert';
    $this->code = 500;
    $this->message = $this->error_codes[$message] ?? $message;
    $this->message = tr($this->message, $vars);


}


}

