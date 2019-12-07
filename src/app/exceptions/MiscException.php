<?php
declare(strict_types = 1);

namespace apex\app\exceptions;

use apex\app;
use apex\app\exceptions\ApexException;


/**
 * Handles various miscellaneous errors, such as administrator does not exist, 
 * etc. 
 */
class MiscException   extends ApexException
{

    // Properties
    private $error_codes = array(
        'no_admin' => "No administrator exists within the database with ID# {id}"
    );

/**
 * Construct 
 * 
 * @param string $message The exception message.
 * @param string $id The unique ID# of the object / record.
 */
public function __construct($message, $id = 0)
{ 

    // Set variables
    $vars = array(
        'id' => $id
    );

    // Get message
    $this->log_level = 'error';
    $this->code = 500;
    $this->message = $this->error_codes[$message] ?? $message;
    $this->message = tr($this->message, $vars);

}


}

